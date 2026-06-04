<?php 
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

if (!isset($_SESSION['id'])) {
    header("location:logout.php");
    exit;
}

/* OPWKS FILTER */
$GRANT_TYPE = "OPWKS";
$GRANT_NAME = "OPWKS";

/* FETCH DATA */
$stmt = $connect->prepare("
    SELECT 
        em.id,
        em.equipment_name,
        em.lp_no,
        em.au,
        em.qty_received,
        em.qty_available,
        em.received_date,
        em.cost,

        IFNULL(SUM(CASE WHEN et.txn_type='ISSUE' THEN et.qty ELSE 0 END),0) AS total_issue,
        IFNULL(SUM(CASE WHEN et.txn_type='RETURN' THEN et.qty ELSE 0 END),0) AS total_return,

        GROUP_CONCAT(
            CASE 
                WHEN et.txn_type='ISSUE' 
                THEN CONCAT(et.unit_name,' - ',et.qty)
            END
            SEPARATOR '<br>'
        ) AS distribution_details

    FROM equipment_master em
    JOIN grants_master gm ON em.grant_id = gm.id
    LEFT JOIN equipment_txn et 
        ON et.equipment_id = em.id

    WHERE gm.grant_type = ?
      AND gm.grant_name = ?

    GROUP BY em.id
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
<title>OPWKS</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{background:#eef5ff;margin:0;padding:15px;font-family:Segoe UI;}
.card{
    min-height:calc(100vh - 30px);
    background:#fff;
    padding:20px;
    border-radius:16px;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
    display:flex;
    flex-direction:column;
}
.badge-grant{background:#000;color:#fff;padding:6px 18px;border-radius:20px;}
.appx-box{position:absolute;top:20px;right:25px;font-weight:600;}
.editable{min-width:90px;display:inline-block;padding:2px 6px;outline:none;}
.top-bar{display:flex;justify-content:space-between;align-items:center;margin:12px 0;}
.search-box{width:300px;}
table{width:100%;table-layout:fixed;font-size:0.8rem;}
thead th{
    background:#f1f3f6;
    text-align:center;
    font-weight:700;
    padding:8px 6px;
}
td{
    text-align:center;
    vertical-align:middle;
    padding:6px;
    font-size:17px;
}
th:nth-child(1),td:nth-child(1){width:45px;}
th:nth-child(2),td:nth-child(2){width:120px;}
 thead th:nth-child(3){
    font-weight:700;   /* header bold */
    text-align:center;
     width:240px;
}
tbody td:nth-child(3){
    font-weight:400;   /* text normal */
    text-align:left;
    width:240px;
}
th:nth-child(4),td:nth-child(4){width:70px;}
th:nth-child(5),td:nth-child(5){width:80px;}
th:nth-child(6),td:nth-child(6){width:80px;}
th:nth-child(7),td:nth-child(7){width:100px;}
th:nth-child(8),td:nth-child(8){width:90px;}
th:nth-child(9),td:nth-child(9){width:180px;text-align:left;}
.low-stock{background:#f8d7da!important;font-weight:700;}
.medium-stock{background:#fff3cd!important;font-weight:700;}
.page-sign{
    display:flex;
    justify-content:space-between;
    margin-top:auto;   /* 🔥 KEY LINE */
}
.print-spacer{height:12mm;}
.table-responsive{
    flex:1;
}
@media print{
    @page{size:A4 landscape;margin:10mm;}
    body{background:#fff;padding:0;}
    .top-bar,.btn{display:none!important;}
    table{font-size:11px;}
    thead th{background:#000!important;color:#fff!important;}
    table tr,.page-sign{page-break-inside:avoid;}
}
</style>
</head>

<body>
<div class="card">

<div class="appx-box">
    Appx : <span contenteditable="true" class="editable"></span>
</div>

<div class="text-center">
    <h4>🏗 OPWKS</h4>
    <span class="badge-grant">OPWKS</span>
</div>

<div class="top-bar">
    <a href="dboard.php" class="btn btn-secondary">⬅ Back</a>
    <input type="text" id="searchInput" class="form-control search-box" placeholder="🔍 Search anything...">
    <button onclick="window.print()" class="btn btn-outline-primary">🖨 Print</button>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped table-sm" id="equipTable">
<thead>
<tr>
    <th>SER NO</th>
    <th>JOB NO / NAR NO</th>
    <th>NOMENCLATURE</th>
    <th>A/U</th>
    <th>QTY REC</th>
    <th>QTY AVL</th>
    <th>REC DATE</th>
    <th>COST</th>
    <th>DISTRIBUTION</th>
</tr>
</thead>
<tbody>

<?php
$i=1;
while($row=$result->fetch_assoc()){

    $received  = (int)$row['qty_received'];
    $issue     = (int)$row['total_issue'];
    $return    = (int)$row['total_return'];

    $available = $received - ($issue - $return);
    if($available < 0) $available = 0;

    $cls = $available<=5?'low-stock':($available<=10?'medium-stock':'');

    echo "<tr>
        <td>{$i}</td>
        <td>{$row['lp_no']}</td>
        <td>".htmlspecialchars($row['equipment_name'])."</td>
        <td>{$row['au']}</td>
        <td>{$received}</td>
        <td class='{$cls}'>{$available}</td>
        <td>{$row['received_date']}</td>
        <td>{$row['cost']}</td>
        <td>".($row['distribution_details'] ?: "<span class='text-muted'>Not Issued</span>")."</td>
    </tr>";

    $i++;
}
?>

</tbody>
</table>
</div>

<div class="print-spacer"></div>

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