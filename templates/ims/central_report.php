<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

if (!isset($_SESSION['id'])) {
    header("location:logout.php");
    exit;
}

/* ================= SEARCH ================= */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where  = "1=1";

if ($search !== '') {

    $search = mysqli_real_escape_string($connect, $search);

    $where .= " AND (
        gm.grant_type LIKE '%$search%' OR
        gm.grant_name LIKE '%$search%' OR
        gm.sub_grant  LIKE '%$search%' OR
        em.equipment_name LIKE '%$search%' OR
        em.lp_no LIKE '%$search%' OR
        em.cat_part_no LIKE '%$search%' OR

        /* 🔥 THIS IS THE MISSING LOGIC 🔥 */
        EXISTS (
            SELECT 1 
            FROM equipment_txn et2
            WHERE et2.equipment_id = em.id
              AND et2.txn_type = 'ISSUE'
              AND et2.unit_name LIKE '%$search%'
        )
    )";
}

/* ================= QUERY ================= */
$sql = "
SELECT 
    gm.grant_type,
    gm.grant_name,
    gm.sub_grant,
    em.lp_no,
    em.cat_part_no,
    em.equipment_name,
    em.au,

    em.qty_received,

    IFNULL(SUM(CASE WHEN et.txn_type='ISSUE' THEN et.qty ELSE 0 END),0) AS total_issue,

    IFNULL(SUM(CASE WHEN et.txn_type='RETURN' THEN et.qty ELSE 0 END),0) AS total_return,

    em.received_date,
    em.cost,

    GROUP_CONCAT(
        CONCAT(
            et.unit_name,' : ',et.qty,
            ' (',DATE_FORMAT(COALESCE(et.issue_date, et.created_at),'%d-%m-%Y'),')'
        ) SEPARATOR ', '
    ) AS distribution

FROM equipment_master em
JOIN grants_master gm ON em.grant_id = gm.id
LEFT JOIN equipment_txn et 
    ON et.equipment_id = em.id

WHERE $where
GROUP BY em.id
ORDER BY em.id
";


$result = mysqli_query($connect, $sql);
$total  = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html>
<head>
<title>CENTRAL EQUIPMENT VIEW</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:20px;
    font-family:Segoe UI, Arial, sans-serif;
}
.card{
    background:#fff;
    padding:16px;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
}
h5, th{
    text-transform:uppercase;
    letter-spacing:.4px;
}

/* TABLE */
table{
    width:100%;
    table-layout:fixed;
    border-collapse:collapse;
}
th{
    background:#f1f3f6;
    text-align:center;
    font-size:0.95rem;
    padding:6px;
    font-weight:700;
}
td{
    font-size:0.75rem;
    padding:6px;
    text-align:center;
    vertical-align:middle;
    word-break:break-word;
}

/* COLUMN WIDTHS */
th:nth-child(1), td:nth-child(1){ width:38px; }
th:nth-child(2), td:nth-child(2){ width:110px; }
th:nth-child(3), td:nth-child(3){ width:110px; }
th:nth-child(4), td:nth-child(4){ width:90px; }
th:nth-child(5), td:nth-child(5){ width:70px; }
th:nth-child(6), td:nth-child(6){ width:90px; }

thead th:nth-child(7){
    font-weight:700;   /* header bold */
    text-align:center;
     width:240px;
}
tbody td:nth-child(7){
    font-weight:400;   /* text normal */
    text-align:left;
    width:240px;
}     
th:nth-child(8), td:nth-child(8){ width:50px; }
th:nth-child(9), td:nth-child(9){ width:60px; }
th:nth-child(10), td:nth-child(10){ width:60px; }
th:nth-child(11), td:nth-child(11){ width:90px; }
th:nth-child(12), td:nth-child(12){ width:80px; }

th:nth-child(13), td:nth-child(13){
    width:200px;
    text-align:left;
}

/* DISTRIBUTION – VERTICAL */
.dist-vertical{
    text-align:left;
    font-size:0.72rem;
    line-height:1.4;
}

/* TOP BAR */
.top-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:8px;
}
.search-box{width:260px}

/* PRINT */
@media print{
    @page{
        size:A4 landscape;
        margin:8mm 6mm 10mm 6mm;
    }
    body{background:#fff;padding:0;}
    .no-print{display:none;}
    table{
        width:98%;
        font-size:9.5px;
    }
    th,td{padding:4px 3px;}
    thead{display:table-header-group;}
    tr{page-break-inside:avoid;}
}
</style>
</head>

<body>

<div class="card">

<div class="top-bar no-print">
    <a href="dboard.php" class="btn btn-secondary btn-sm">⬅ BACK</a>
    <h5>CENTRAL EQUIPMENT VIEW</h5>
    <button onclick="window.print()" class="btn btn-outline-primary btn-sm">🖨 PRINT</button>
</div>

<form method="GET" class="d-flex justify-content-center no-print mb-2">
    <input type="text" name="search"
           value="<?= htmlspecialchars($search) ?>"
           class="form-control form-control-sm search-box"
           placeholder="SEARCH ANYTHING">
</form>

<div class="text-end fw-bold mb-1">
TOTAL RECORDS : <?= $total ?>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead>
<tr>
    <th>S.NO</th>
    <th>GRANT TYPE</th>
    <th>GRANT</th>
    <th>SUB</th>
    <th>LP</th>
    <th>CAT</th>
    <th>NOMENCLATURE</th>
    <th>A/U</th>
    <th>REC</th>
    <th>AVL</th>
    <th>DATE</th>
    <th>COST</th>
    <th>DISTRIBUTION</th>
</tr>
</thead>
<tbody>

<?php
$sn = 1;
if ($total == 0) {
    echo "<tr><td colspan='13' class='text-center'>NO RECORDS FOUND</td></tr>";
}

while ($row = mysqli_fetch_assoc($result)) {

    $received    = $row['qty_received'];
    $totalIssue  = $row['total_issue'];
    $totalReturn = $row['total_return'];

    $available = $received - ($totalIssue - $totalReturn);
$available = max(0, $available);


    $dist = $row['distribution']
        ? nl2br("• ".str_replace(", ", "\n• ", $row['distribution']))
        : "NOT ISSUED";

    echo "<tr>
        <td>{$sn}</td>
        <td>{$row['grant_type']}</td>
        <td>{$row['grant_name']}</td>
        <td>{$row['sub_grant']}</td>
        <td>{$row['lp_no']}</td>
        <td>{$row['cat_part_no']}</td>
        <td>".htmlspecialchars($row['equipment_name'])."</td>
        <td>{$row['au']}</td>
        <td>{$totalIssue}</td>
        <td>{$available}</td>
        <td>{$row['received_date']}</td>
        <td>{$row['cost']}</td>
        <td class='dist-vertical'>{$dist}</td>
    </tr>";

    $sn++;
}

?>


</tbody>
</table>
</div>

</div>
</body>
</html>
