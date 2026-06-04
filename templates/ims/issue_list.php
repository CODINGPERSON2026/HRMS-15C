<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

if (!isset($_SESSION['id'])) {
    header("location:logout.php");
    exit;
}

$sql = "
SELECT
    et.id,
    DATE(et.created_at) AS issue_date,
    et.unit_name,
    em.lp_no,
    em.cat_part_no,
    em.equipment_name,
    em.au,
    et.qty,
    em.cost
FROM equipment_txn et
JOIN equipment_master em ON em.id = et.equipment_id
WHERE et.txn_type='ISSUE'
ORDER BY et.created_at DESC
";

$res   = mysqli_query($connect,$sql);
$total = mysqli_num_rows($res);
?>

<!DOCTYPE html>
<html>
<head>
<title>ISSUED ITEMS REGISTER</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:25px;
    font-family:Segoe UI, Arial, sans-serif;
}

.card{
    background:#fff;
    padding:20px;
    border-radius:14px;
    box-shadow:0 8px 18px rgba(0,0,0,.12);
}

h5{
    font-weight:700;
    margin-bottom:15px;
}

/* TABLE */
table{
    table-layout:fixed;
    width:100%;
}

th{
    background:#f1f3f6;
    font-size:13px;
    text-align:center;
    padding:8px;
}

td{
    font-size:12.5px;
    padding:6px;
    text-align:center;
    vertical-align:middle;
    word-break:break-word;
}

/* NOMENCLATURE LEFT */
td:nth-child(4){
    text-align:left;
    font-weight:600;
}

/* COLUMN WIDTHS (NOW PERFECT 10 COLUMNS) */
th:nth-child(1), td:nth-child(1){width:50px;}   /* S.NO */
th:nth-child(2), td:nth-child(2){width:90px;}   /* LP */
th:nth-child(3), td:nth-child(3){width:100px;}  /* CAT */
th:nth-child(4), td:nth-child(4){width:240px;}  /* NOM */
th:nth-child(5), td:nth-child(5){width:70px;}   /* AU */
th:nth-child(6), td:nth-child(6){width:70px;}   /* QTY */
th:nth-child(7), td:nth-child(7){width:90px;}   /* COST */
th:nth-child(8), td:nth-child(8){width:140px;}  /* STORE */
th:nth-child(9), td:nth-child(9){width:110px;}  /* DATE */
th:nth-child(10), td:nth-child(10){width:120px;}/* ACTION */

.no-print{white-space:nowrap;}

@media print{
    .no-print{display:none !important;}
    body{background:#fff;padding:0;}
}
</style>
</head>
<body>

<div class="card">

<div class="d-flex justify-content-between align-items-center mb-2">
    <h5>ISSUED ITEMS REGISTER</h5>
    <div class="fw-bold">TOTAL RECORDS : <?= $total ?></div>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead>
<tr>
<th>S.NO</th>
<th>LP NO</th>
<th>CAT NO</th>
<th>NOMENCLATURE</th>
<th>A/U</th>
<th>QTY</th>
<th>COST</th>
<th>STORE</th>
<th>DATE</th>
<th class="no-print">ACTION</th>
</tr>
</thead>
<tbody>

<?php
$i=1;
if($total==0){
    echo "<tr><td colspan='10' class='text-center'>NO RECORDS FOUND</td></tr>";
}

while($r=mysqli_fetch_assoc($res)){
?>
<tr>
<td><?= $i++ ?></td>
<td><?= htmlspecialchars($r['lp_no']) ?></td>
<td><?= htmlspecialchars($r['cat_part_no']) ?></td>
<td><?= htmlspecialchars($r['equipment_name']) ?></td>
<td><?= htmlspecialchars($r['au']) ?></td>
<td><?= $r['qty'] ?></td>
<td><?= number_format($r['cost'],2) ?></td>
<td><?= htmlspecialchars($r['unit_name']) ?></td>
<td><?= $r['issue_date'] ?></td>
<td class="no-print">
    <a href="issue_edit.php?id=<?= $r['id'] ?>" 
       class="btn btn-warning btn-sm">EDIT</a>
    <a href="issue_delete.php?id=<?= $r['id'] ?>" 
       onclick="return confirm('Delete this issue entry?')" 
       class="btn btn-danger btn-sm">DELETE</a>
</td>
</tr>
<?php } ?>

</tbody>
</table>
</div>

</div>
</body>
</html>
