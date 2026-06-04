<?php
session_start();
require_once "connect.php";

$sql = "
SELECT 
    rvi.id,
    rv.id as voucher_id,
    rv.rv_no,
    rv.rv_date,
    rv.returned_from,
    rvi.lp_no,
    rvi.cat_part_no,
    rvi.nomenclature,
    rvi.au,
    rvi.qty,
    rvi.remarks
FROM return_vouchers_items rvi
JOIN return_vouchers rv ON rv.id = rvi.return_voucher_id
WHERE rv.status = 'APPROVED'
ORDER BY rvi.id DESC
";

$result = mysqli_query($connect, $sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Returned Equipment</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background: linear-gradient(135deg,#eef5ff,#dbeafe);
    padding:30px;
    font-family:'Segoe UI',sans-serif;
}

.card{
    max-width:1400px;
    margin:auto;
    border:none;
    border-radius:18px;
    box-shadow:0 10px 30px rgba(0,0,0,0.12);
    background:#fff;
    animation:fadeIn 0.4s ease;
}

h3{
    font-weight:700;
    color:#1d4ed8;
}

.table{
    border-radius:12px;
    overflow:hidden;
}

.table th{
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:#fff;
    font-size:14px;
    padding:12px;
    vertical-align:middle;
}

.table td{
    padding:10px;
    font-size:14px;
    vertical-align:middle;
}

.table tbody tr:nth-child(even){
    background:#f8fbff;
}

.table tbody tr:hover{
    background:#eaf2ff;
    transition:0.2s ease;
}

.btn-view{
    background:#16a34a;
    color:#fff;
    border:none;
    padding:6px 12px;
    border-radius:8px;
    font-size:13px;
    font-weight:600;
    text-decoration:none;
}

.btn-view:hover{
    background:#15803d;
    color:#fff;
}

.btn-back{
    margin-top:15px;
    padding:8px 16px;
    border-radius:8px;
    font-weight:600;
}

@keyframes fadeIn{
    from{
        opacity:0;
        transform:translateY(15px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}
</style>
</head>
<body>

<div class="card p-4">
<h3 class="text-center mb-4">📦 Returned Equipment List</h3>

<div class="table-responsive">
<table class="table table-bordered text-center align-middle">
<thead>
<tr>
    <th>RV No</th>
    <th>Date</th>
    <th>Store</th>
    <th>LP No</th>
    <th>Part No</th>
    <th>Nomenclature</th>
    <th>A/U</th>
    <th>Qty</th>
    <th>Remarks</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php
if(mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){
?>
<tr>
    <td><b><?= $row['rv_no'] ?></b></td>
    <td><?= date('d-m-Y', strtotime($row['rv_date'])) ?></td>
    <td><?= htmlspecialchars($row['returned_from']) ?></td>
    <td><?= htmlspecialchars($row['lp_no']) ?></td>
    <td><?= htmlspecialchars($row['cat_part_no']) ?></td>
    <td><?= htmlspecialchars($row['nomenclature']) ?></td>
    <td><?= htmlspecialchars($row['au']) ?></td>
    <td><b><?= $row['qty'] ?></b></td>
    <td><?= htmlspecialchars($row['remarks']) ?></td>
    <td>
        <a href="print_return_voucher.php?id=<?= $row['voucher_id'] ?>" 
           target="_blank" 
           class="btn-view">
           👁 View
        </a>
    </td>
</tr>
<?php
    }
}else{
?>
<tr>
    <td colspan="10" class="text-danger fw-bold">
        No Approved Returned Equipment Found
    </td>
</tr>
<?php } ?>

</tbody>
</table>
</div>

<a href="view_return_voucher.php" class="btn btn-secondary btn-back">⬅ Back</a>
</div>

</body>
</html>