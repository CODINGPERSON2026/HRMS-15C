<?php
session_start();
require_once "connect.php";

if (!isset($_SESSION['id'])) {
    header("location:logout.php");
    exit;
}

/* ================= FILTER VALUES ================= */
$f_date   = $_GET['f_date']   ?? '';
$f_store  = $_GET['f_store']  ?? '';
$f_grant  = $_GET['f_grant']  ?? '';

/* ================= BUILD QUERY ================= */
$sql = "
SELECT 
    v.id,
    v.voucher_type,
    v.iv_no,
    v.rv_no,
    v.issued_to,
    DATE(v.created_at) as v_date
FROM voucher_master v
WHERE 1=1
";

$params = [];
$types  = "";

/* Date filter */
if ($f_date != '') {
    $sql .= " AND DATE(v.created_at) = ? ";
    $params[] = $f_date;
    $types .= "s";
}

/* Store / Issued To filter */
if ($f_store != '') {
    $sql .= " AND v.issued_to LIKE ? ";
    $params[] = "%$f_store%";
    $types .= "s";
}

/* Grant / Voucher Type filter */
if ($f_grant != '') {
    $sql .= " AND v.voucher_type = ? ";
    $params[] = $f_grant;
    $types .= "s";
}

$sql .= " ORDER BY v.id DESC";

/* ================= EXECUTE ================= */
$stmt = $connect->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<title>Voucher List</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:15px;
    font-family:Segoe UI;
}

.card{
    background:#fff;
    padding:15px;
    border-radius:12px;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
}

h5{
    text-align:center;
    font-weight:700;
    margin-bottom:15px;
}

table{
    font-size:0.85rem;
}

thead th{
    background:#004d40;
    color:#fff;
    text-align:center;
}

td{
    text-align:center;
    vertical-align:middle;
}

.action-btns{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:6px;
}

.action-btns a{
    min-width:70px;
    padding:5px 10px;
    font-size:0.75rem;
    font-weight:600;
    border-radius:6px;
    text-decoration:none;
    transition:0.2s ease-in-out;
}

/* View Button */
.action-btns .btn-info{
    background:#17a2b8;
    color:#fff;
    border:none;
}

.action-btns .btn-info:hover{
    background:#138496;
    transform:scale(1.05);
}

/* Delete Button */
.action-btns .btn-danger{
    background:#dc3545;
    color:#fff;
    border:none;
}

.action-btns .btn-danger:hover{
    background:#b02a37;
    transform:scale(1.05);
}

.filter-box{
    margin-bottom:12px;
}

@media print{
    .no-print{ display:none!important; }
}
</style>
</head>

<body>

<div class="card">

<!-- BACK BUTTON (TOP) -->
<div class="no-print mb-2">
    <a href="dboard.php" class="btn btn-secondary btn-sm">⬅ Back</a>
</div>

<h5>📄 SAVED VOUCHERS</h5>

<!-- FILTERS -->
<form method="get" class="row filter-box no-print">
    <div class="col-md-3">
        <label>Date</label>
        <input type="date" name="f_date" value="<?= htmlspecialchars($f_date) ?>" class="form-control form-control-sm">
    </div>

    <div class="col-md-3">
        <label>Store / Issued To</label>
        <input type="text" name="f_store" value="<?= htmlspecialchars($f_store) ?>" class="form-control form-control-sm">
    </div>

    <div class="col-md-3">
        <label>Grant / Voucher Type</label>
        <select name="f_grant" class="form-control form-control-sm">
            <option value="">-- All --</option>
            <option value="LOAN RENEWAL" <?= ($f_grant=="LOAN RENEWAL")?'selected':'' ?>>LOAN RENEWAL</option>
        </select>
    </div>

    <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-success btn-sm me-2">🔍 Filter</button>
        <a href="voucher_list.php" class="btn btn-warning btn-sm">♻ Reset</a>
    </div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-striped">
<thead>
<tr>
    <th>SER NO</th>
    <th>VOUCHER TYPE</th>
    <th>IV NO</th>
    <th>RV NO</th>
    <th>ISSUED TO</th>
    <th>DATE</th>
    <th class="no-print">ACTION</th>
</tr>
</thead>
<tbody>

<?php
if ($result && $result->num_rows > 0) {
    $i=1;
    while($row = $result->fetch_assoc()){
    $id = $row['id'];

    echo "
    <tr>
        <td>{$i}</td>
        <td>{$row['voucher_type']}</td>
        <td>{$row['iv_no']}</td>
        <td>{$row['rv_no']}</td>
        <td>{$row['issued_to']}</td>
        <td>{$row['v_date']}</td>

        <td class='action-btns no-print'>
            <a href='voucher_view.php?id={$id}' 
               class='btn btn-info btn-sm'
               target='_blank'>
               👁 View
            </a>

            <a href='delete_voucher.php?id={$id}' 
               class='btn btn-danger btn-sm'
               onclick=\"return confirm('Are you sure you want to delete this voucher?')\">
               🗑 Delete
            </a>
        </td>
    </tr>";
    $i++;
}
}else{
    echo "<tr><td colspan='7'>No vouchers found</td></tr>";
}
?>

</tbody>
</table>
</div>

</div>

</body>
</html>
