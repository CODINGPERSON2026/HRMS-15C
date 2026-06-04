<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* 🔐 ROLE CHECK */
if (
    !isset($_SESSION['id']) ||
    !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin','super_admin'])
) {
    header("location:user_dboard.php");
    exit;
}

/* ===== FETCH ISSUE VOUCHERS ===== */
$result = $connect->query("
    SELECT 
        id,
        voucher_no,
        issued_to,
        total_qty,
        DATE_FORMAT(created_at,'%d-%m-%Y') AS v_date
    FROM opwks_issue_voucher
    ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<title>OPWKS Issue Voucher List</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:20px;
    font-family:Segoe UI;
}
.card{
    max-width:1200px;
    margin:auto;
    padding:20px;
    background:#fff;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
}
table th, table td{
    text-align:center;
    vertical-align:middle;
}
.search-box{
    max-width:260px;
}
</style>
</head>

<body>

<div class="card">

<h4 class="text-center mb-3">📄 OPWKS ISSUE VOUCHERS</h4>

<div class="d-flex justify-content-between mb-2">
    <a href="opwks.php" class="btn btn-secondary btn-sm">⬅ Back</a>

    <input type="text"
           id="searchInput"
           class="form-control form-control-sm search-box"
           placeholder="🔍 Search Voucher...">
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped" id="voucherTable">

<thead class="table-dark">
<tr>
    <th>SER</th>
    <th>Voucher No</th>
    <th>Date</th>
    <th>Issued To</th>
    <th>Total Qty</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php
$i = 1;
if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        echo "
        <tr>
            <td>{$i}</td>
            <td><b>{$r['voucher_no']}</b></td>
            <td>{$r['v_date']}</td>
            <td>{$r['issued_to']}</td>
            <td>{$r['total_qty']}</td>
            <td>
                <a href='opwks_issue_voucher_view.php?id={$r['id']}'
                   class='btn btn-primary btn-sm'>
                   👁 View / Print
                </a>
            </td>
        </tr>";
        $i++;
    }
} else {
    echo "<tr><td colspan='6'>No Vouchers Found</td></tr>";
}
?>
</tbody>

</table>
</div>
</div>

<script>
/* 🔍 SEARCH FILTER */
document.getElementById("searchInput").addEventListener("keyup", function(){
    let value = this.value.toLowerCase();
    document.querySelectorAll("#voucherTable tbody tr").forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
    });
});
</script>

</body>
</html>
