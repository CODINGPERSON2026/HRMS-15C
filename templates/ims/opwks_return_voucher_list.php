<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

$result = $connect->query("
    SELECT id, voucher_no, returned_from, total_qty,
           DATE_FORMAT(created_at,'%d-%m-%Y') AS v_date
    FROM opwks_return_voucher
    ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<title>OPWKS Return Voucher List</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
</head>

<body style="background:#eef5ff;padding:20px">

<div class="card p-3">
<h4 class="text-center mb-3">📄 OPWKS RETURN VOUCHERS</h4>

<a href="opwks.php" class="btn btn-secondary btn-sm mb-2">⬅ Back</a>

<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
    <th>SER</th>
    <th>Voucher No</th>
    <th>Date</th>
    <th>Returned From</th>
    <th>Total Qty</th>
    <th>View</th>
</tr>
</thead>
<tbody>
<?php
$i=1;
while($r=$result->fetch_assoc()){
echo "
<tr>
<td>$i</td>
<td>{$r['voucher_no']}</td>
<td>{$r['v_date']}</td>
<td>{$r['returned_from']}</td>
<td>{$r['total_qty']}</td>
<td>
<a href='opwks_return_voucher_view.php?id={$r['id']}'
   class='btn btn-primary btn-sm'>👁 View / Print</a>
</td>
</tr>";
$i++;
}
?>
</tbody>
</table>
</div>

</body>
</html>
