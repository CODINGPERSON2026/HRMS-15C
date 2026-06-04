<?php 
session_start();
require_once __DIR__ . "/../auth.php";
require_admin();
require_once "../connect.php";

if (!isset($_SESSION['id'])) {
    header("location:../logout.php");
    exit;
}

/* FIXED GRANT FILTER – ACSFP (NO SUB GRANT) */
$GRANT_TYPE = "TECH/ORD/ACSFP";
$GRANT_NAME = "ACSFP";

/* FETCH EQUIPMENTS + DISTRIBUTION DETAILS */
$stmt = $connect->prepare("
    SELECT 
        em.id,
        em.equipment_name,
        em.lp_no,
        em.cat_part_no,
        em.au,
        em.received_date,

        IFNULL(tx.total_issue,0) AS total_issue,
        IFNULL(tx.total_return,0) AS total_return,

        tx.distribution_details

    FROM equipment_master em
    JOIN grants_master gm ON em.grant_id = gm.id

    LEFT JOIN (
        SELECT 
            equipment_id,

            SUM(CASE WHEN txn_type='ISSUE' THEN qty ELSE 0 END) AS total_issue,

            SUM(CASE WHEN txn_type='RETURN' THEN qty ELSE 0 END) AS total_return,

            GROUP_CONCAT(
    CASE 
        WHEN txn_type='ISSUE' THEN
            CONCAT(
                unit_name,
                ' - ',
                qty
            )
    END
    ORDER BY created_at
    SEPARATOR '<br>'
) AS distribution_details

        FROM equipment_txn
        GROUP BY equipment_id

    ) tx ON tx.equipment_id = em.id

    WHERE gm.grant_type = ?
      AND gm.grant_name = ?

    ORDER BY em.id DESC
");

$stmt->bind_param("ss", $GRANT_TYPE, $GRANT_NAME);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
<title>ACSFP</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">

<style>
/* ================= SCREEN ================= */
body{
    background:#eef5ff;
    margin:0;
    padding:15px;
    font-family:Segoe UI;
}
.card{
    min-height:calc(100vh - 30px);
    background:#fff;
    padding:20px;
    border-radius:16px;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
    display:flex;
    flex-direction:column;
    position:relative;
}
h4{font-size:1.4rem;font-weight:700;}
.badge-grant{
    background:#004d40;
    color:#fff;
    padding:6px 18px;
    border-radius:20px;
}

/* ===== APPX TOP RIGHT ===== */
.appx-box{
    position:absolute;
    top:20px;
    right:25px;
    font-weight:600;
}

/* ===== EDITABLE (NO UNDERLINE) ===== */
.editable{
    min-width:80px;
    display:inline-block;
    padding:2px 6px;
    outline:none;
}

/* TOP BAR */
.top-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin:15px 0;
}
.search-box{width:300px;}

/* TABLE */
.table-responsive{flex:1;overflow:auto;}
table{width:100%;table-layout:fixed;font-size:0.95rem;}
thead th{
    position:sticky;top:0;
    background:#004d40;color:#fff;
    text-align:center;padding:10px 6px;
}
td{text-align:center;padding:9px 6px;}

/* COLUMN WIDTH */
.th-serno{width:72px;}
.th-lp{width:80px;}
.th-cat{width:160px;}
.th-au{width:60px;}
.th-qty{width:90px;}
.th-date{width:120px;}
.dist-col{text-align:left;font-size:0.9rem;}
/* NOMENCLATURE LEFT ALIGN */
#equipTable tbody td:nth-child(4){
    text-align:left;
}

.low-stock{background:#f8d7da!important;font-weight:700;}
.medium-stock{background:#fff3cd!important;font-weight:700;}

/* ===== PAGE BOTTOM SIGN ===== */
.page-sign{
    margin-top:30px;
    display:flex;
    justify-content:space-between;
    font-weight:600;
}

/* ================= PRINT ================= */
@media print{
    @page{size:A4 landscape;margin:10mm;}
    .top-bar{display:none;}
    table{font-size:11px;}
}
</style>
</head>

<body>

<div class="card">

<!-- APPX -->
<div class="appx-box">
    Appx : <span contenteditable="true" class="editable"></span>
</div>

<div class="text-center">
    <h4>🛠 ACSFP</h4>
    <span class="badge-grant">TECH / ORD / ACSFP</span>
</div>

<div class="top-bar">
    <a href="../dboard.php" class="btn btn-secondary">⬅ Back</a>
    <input type="text" id="searchInput" class="form-control search-box" placeholder="🔍 Search anything...">
    <button onclick="window.print()" class="btn btn-outline-primary">🖨 Print</button>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped" id="equipTable">
<thead>
<th class="th-serno">SER.NO</th>
    <th class="th-lp">LP NO</th>
    <th class="th-cat">CAT / PART NO</th>
    <th>NOMENCLATURE</th>
    <th class="th-au">A/U</th>
    <th class="th-qty">QTY REC</th>
    <th class="th-qty">QTY AVL</th>
    <th class="th-date">DATE OF REC</th>
    <th>DISTRIBUTION DETAILS</th>
</tr>
</thead>
<tbody>

<?php
$i=1;
while($row=$result->fetch_assoc()){

   $totalIssue  = (int)$row['total_issue'];
    $totalReturn = (int)$row['total_return'];

    $received  = $totalIssue;   // show distribution total
    $available = 0;             // force zero

    $cls = 'low-stock';
    
    echo "<tr>
        <td>{$i}</td>
        <td>{$row['lp_no']}</td>
        <td>{$row['cat_part_no']}</td>
        <td>".htmlspecialchars($row['equipment_name'])."</td>
        <td>{$row['au']}</td>
        <td>{$received}</td>
        <td class='{$cls}'>{$available}</td>
        <td>{$row['received_date']}</td>
        <td class='dist-col'>".($row['distribution_details'] ?: "<span class='text-muted'>Not Issued</span>")."</td>
    </tr>";

    $i++;
}

?>

</tbody>
</table>
</div>

<!-- BOTTOM SIGN -->
<div class="page-sign">
    <div>PO : <span contenteditable="true" class="editable"></span></div>
    <div>M1 : <span contenteditable="true" class="editable"></span></div>
    <div>M2 : <span contenteditable="true" class="editable"></span></div>
</div>

</div>

<script>
document.getElementById("searchInput").addEventListener("keyup",function(){
  let f=this.value.toLowerCase();
  document.querySelectorAll("#equipTable tbody tr").forEach(r=>{
    r.style.display=r.innerText.toLowerCase().includes(f)?"":"none";
  });
});
</script>

</body>
</html>
