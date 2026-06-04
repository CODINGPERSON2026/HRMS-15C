<?php
session_start();
require_once "auth.php";
require_user();
require_once 'connect.php';

/* ================= AUTH ================= */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header("location:logout.php");
    exit;
}

/* ================= INPUT ================= */
$store    = trim($_SESSION['unit']);
$grant_id = (int)($_GET['grant_id'] ?? 0);

if ($grant_id <= 0) {
    die("Invalid Grant Selection");
}

/* ================= FETCH GRANT ================= */
$g = $connect->prepare("
    SELECT grant_type, grant_name 
    FROM grants_master 
    WHERE id = ?
");
$g->bind_param("i", $grant_id);
$g->execute();
$grant = $g->get_result()->fetch_assoc();

if (!$grant) {
    die("Grant not found");
}

$grant_type = $grant['grant_type'];

/* ================= FLAGS ================= */
$is_tech   = in_array($grant_type, [
    'TECH GRANT','TECH/ORD/ACSFP','ORD','ACSFP','SECT','LOAN'
]);
$show_cost = in_array($grant_type, [
    'PUBLIC GRANT','REGTL PROPERTIES','PRIVATE GRANT'
]);

/* ================= FINAL QUERY ================= */
$sql = "
SELECT
    em.id AS equipment_id,
    em.lp_no,
    em.cat_part_no,
    em.equipment_name,
    em.au,
    em.cost,

    COALESCE(SUM(
        CASE 
            WHEN et.txn_type='ISSUE' THEN et.qty
            ELSE 0
        END
    ),0) AS total_qty,

    (
        COALESCE(SUM(CASE WHEN et.txn_type='ISSUE' THEN et.qty ELSE 0 END),0)
        -
        COALESCE(SUM(CASE WHEN et.txn_type='RETURN' THEN et.qty ELSE 0 END),0)
        -
        COALESCE(
            (
                SELECT SUM(rr.return_qty)
                FROM return_requests rr
                WHERE rr.equipment_id = em.id
                  AND rr.status='PENDING'
                  AND rr.store_name = ?
            ),0
        )
    ) AS available_qty,

    MIN(
        CASE 
            WHEN et.txn_type='ISSUE' THEN et.issue_date
        END
    ) AS issue_date

FROM equipment_txn et
JOIN equipment_master em ON em.id = et.equipment_id

WHERE TRIM(UPPER(et.unit_name)) = TRIM(UPPER(?))
  AND em.grant_id = ?

GROUP BY em.id
ORDER BY issue_date DESC
";


$stmt = $connect->prepare($sql);
$stmt->bind_param("ssi", $store, $store, $grant_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<title>Issued Items</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
/* ===== BASE ===== */
body{
    background:#e8efe6;
    font-family:"Segoe UI", Arial, sans-serif;
    padding:20px;
}

/* ===== PAGE NUMBER ===== */
@page{
    size:A4;
    margin:15mm;

    @top-right{
        content: "Page " counter(page);
        font-size:11px;
        font-weight:bold;
    }
}


/* ===== CARD ===== */
.army-card{
    max-width:1200px;
    margin:auto;
    background:#fff;
    padding:15px;
}

/* remove outer border in print */
@media print{
    .army-card{ border:none !important; padding:0; }
}

/* ===== HEADER ===== */
.army-header{
    background:#2f4f2f;
    color:#fff;
    padding:10px;
    text-align:center;
    font-weight:700;
    letter-spacing:1px;
}

/* ===== SUB HEADER ===== */
.army-sub{
    text-align:center;
    font-size:13px;
    margin:8px 0 12px;
    color:#333;
    font-weight:600;
}

/* ===== TABLE ===== */
.army-table{
    table-layout:fixed;
}

.army-table th{
    background:#3b5323;
    color:#fff;
    font-size:12px;
    padding:6px;
    border:1px solid #2f4f2f;
}

.army-table td{
    font-size:12px;
    padding:6px;
    border:1px solid #777;
}

.army-table tbody tr:nth-child(even){
    background:#f4f8f1;
}

/* ===== COLUMN WIDTH ===== */
.col-sno{ width:45px; }
.col-lp{ width:120px; }
.col-cat{ width:90px; }
.col-nomen{ width:380px; }
.col-au{ width:60px; }
.col-qty{ width:70px; }
.col-cost{ width:90px; }
.col-date{ width:95px; }
.col-action{ width:80px; }
.col-remarks{ width:120px; }

/* ===== TEXT ===== */
.nomenclature{
    white-space:normal;
    word-wrap:break-word;
}
.qty-bold{ font-weight:700; }

/* ===== BUTTON ===== */
.btn-army{
    background:#556b2f;
    color:#fff;
    border:1px solid #2f4f2f;
    font-size:11px;
    padding:4px 8px;
}
.btn-army:hover{
    background:#2f4f2f;
    color:#fff;
}

/* ===== PRINT RULES ===== */
@media print{

    /* hide action column */
    .col-action,
    td.action-cell{
        display:none !important;
    }

    /* show remarks column */
    .remarks-cell{
        display:table-cell !important;
    }

    /* hide back button */
    .footer-btn{
        display:none !important;
    }
}
</style>
</head>

<body>

<!-- PAGE NUMBER -->
<div class="page-number"></div>

<div class="army-card">

<div class="army-header">
    <?= htmlspecialchars($grant['grant_name']) ?>
</div>

<div class="army-sub">
    <?= htmlspecialchars($grant_type) ?> | UNIT : <?= htmlspecialchars($store) ?>
</div>

<table class="table table-sm army-table text-center">
<thead>
<tr>
    <th class="col-sno">S.NO</th>
    <th class="col-lp">LP NO</th>

    <?php if($is_tech): ?>
        <th class="col-cat">CAT / PART</th>
    <?php endif; ?>

    <th class="col-nomen">NOMENCLATURE</th>
    <th class="col-au">A/U</th>
    <th class="col-qty">TOTAL QTY</th>
<th class="col-qty">AVAILABLE QTY</th>

    <?php if($show_cost): ?>
        <th class="col-cost">COST (₹)</th>
    <?php endif; ?>

    <th class="col-date">ISSUE DATE</th>
    <th class="col-remarks">REMARKS</th>
    <th class="col-action">ACTION</th>
</tr>
</thead>

<tbody>
<?php
$i = 1;

if ($result->num_rows == 0) {
    $cols = 8 + ($is_tech ? 1 : 0) + ($show_cost ? 1 : 0);
    echo "<tr>
        <td colspan='{$cols}' class='text-danger fw-bold'>
            NO ITEMS HELD UNDER THIS GRANT
        </td>
    </tr>";
}

while ($row = $result->fetch_assoc()) {

    $issueDate = $row['issue_date']
        ? date('d-m-Y', strtotime($row['issue_date']))
        : '-';

    echo "<tr>
        <td>{$i}</td>
        <td>".htmlspecialchars($row['lp_no'])."</td>";

    if ($is_tech) {
        echo "<td>".htmlspecialchars($row['cat_part_no'])."</td>";
    }

    echo "
        <td class='nomenclature text-start'>".htmlspecialchars($row['equipment_name'])."</td>
        <td>".htmlspecialchars($row['au'])."</td>
        <td class='qty-bold'>{$row['total_qty']}</td>
        <td class='qty-bold text-primary'>{$row['available_qty']}</td>";

    if ($show_cost) {
        echo "<td>".number_format($row['cost'],2)."</td>";
    }

    echo "
        <td>{$issueDate}</td>
        <td class='remarks-cell'></td>
        <td class='action-cell'>";

    if ($row['available_qty'] > 0) {
        echo "<a href='user_return_voucher.php?equipment_id={$row['equipment_id']}&grant_id={$grant_id}' 
                class='btn btn-army'>RETURN</a>";
    } else {
        echo "<span class='text-danger'>PENDING</span>";
    }

    echo "</td></tr>";

    $i++;
}
?>
</tbody>
</table>

<a href="user_dboard.php" class="btn btn-secondary footer-btn mt-3">⬅ BACK</a>

</div>

</body>
</html>
