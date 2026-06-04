<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

if (!isset($_SESSION['id'])) {
    header("location:logout.php");
    exit;
}

/* ================= FILTERS ================= */
$from   = trim($_GET['from'] ?? '');
$to     = trim($_GET['to'] ?? '');
$store  = trim($_GET['store'] ?? '');
$search = trim($_GET['q'] ?? '');

$where = "et.txn_type='ISSUE'";

if ($from !== '') {
    $from = mysqli_real_escape_string($connect, $from);
    $where .= " AND et.created_at >= '$from 00:00:00'";
}
if ($to !== '') {
    $to = mysqli_real_escape_string($connect, $to);
    $where .= " AND et.created_at < DATE_ADD('$to', INTERVAL 1 DAY)";
}
if ($store !== '') {
    $store = mysqli_real_escape_string($connect, $store);
    $where .= " AND et.unit_name = '$store'";
}
if ($search !== '') {
    $s = mysqli_real_escape_string($connect, $search);
    $where .= " AND (
        em.equipment_name LIKE '%$s%' OR
        em.lp_no LIKE '%$s%' OR
        em.cat_part_no LIKE '%$s%'
    )";
}

/* ================= QUERY ================= */
/* 🔥 et.id REQUIRED FOR EDIT / DELETE */
$sql = "
SELECT
    et.id,
    et.issue_date AS issue_date,
    et.unit_name,
    em.lp_no,
    em.cat_part_no,
    em.equipment_name,
    em.au,
    et.qty,
    em.cost
FROM equipment_txn et
JOIN equipment_master em ON em.id = et.equipment_id
WHERE $where
ORDER BY et.issue_date DESC
";


$res   = mysqli_query($connect, $sql);
$total = mysqli_num_rows($res);
?>
<!DOCTYPE html>
<html>
<head>
<title>STORE WISE ISSUE REPORT</title>
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

/* HEADER */
h5, th{
    text-transform:uppercase;
    letter-spacing:.4px;
}

/* TABLE BASE */
table{
    table-layout:fixed;
    width:100%;
}
th{
    background:#f1f3f6;
    text-align:center;
    font-size:0.95rem;
    padding:6px;
    vertical-align:middle;
}
td{
    font-size:0.75rem;
    padding:6px;
    text-align:center;
    vertical-align:middle;
    white-space:normal;
    word-break:break-word;
}

/* ===== PERFECTLY ALIGNED COLUMN WIDTHS (10 COLUMNS) ===== */
th:nth-child(1),  td:nth-child(1){  width:45px; }   /* S.NO */
th:nth-child(2),  td:nth-child(2){  width:65px; }   /* LP */
th:nth-child(3),  td:nth-child(3){  width:70px; }   /* CAT */

thead th:nth-child(4){
    font-weight:700;   /* header bold */
    text-align:center;
     width:240px;
}
tbody td:nth-child(4){
    font-weight:400;   /* text normal */
    text-align:left;
    width:240px;
}     

th:nth-child(5),  td:nth-child(5){  width:55px; }   /* A/U */
th:nth-child(6),  td:nth-child(6){  width:60px; }   /* QTY */
th:nth-child(7),  td:nth-child(7){  width:80px; }   /* COST */
th:nth-child(8),  td:nth-child(8){
    width:120px;                                   /* STORE */
    text-align:left;
}
th:nth-child(9),  td:nth-child(9){  width:95px; }   /* DATE */
th:nth-child(10), td:nth-child(10){ width:110px; }  /* ACTION */

/* TOP BAR */
.top-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:8px;
}
.filter-bar label{
    font-size:0.75rem;
    font-weight:600;
}

/* PRINT */
@media print{
    .no-print{display:none !important;}
    body{background:#fff;padding:0;}
}
</style>
</head>

<body>

<div class="card">

<!-- TOP BAR -->
<div class="top-bar no-print">
    <a href="dboard.php" class="btn btn-secondary btn-sm">⬅ BACK</a>
    <h5>STORE WISE ISSUE REPORT</h5>
    <button onclick="window.print()" class="btn btn-outline-primary btn-sm">🖨 PRINT</button>
</div>

<!-- FILTER BAR -->
<form method="GET" class="row g-2 filter-bar no-print mb-2">

<div class="col-md-2">
<label>FROM DATE</label>
<input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control form-control-sm">
</div>

<div class="col-md-2">
<label>TO DATE</label>
<input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control form-control-sm">
</div>

<div class="col-md-3">
<label>STORE</label>
<select name="store" class="form-control form-control-sm">
<option value="">ALL STORES</option>
<?php
$stores = mysqli_query($connect,"SELECT DISTINCT unit_name FROM equipment_txn WHERE txn_type='ISSUE'");
while($st=mysqli_fetch_assoc($stores)){
    $sel = ($store === $st['unit_name']) ? 'selected' : '';
    echo "<option $sel>".htmlspecialchars($st['unit_name'])."</option>";
}
?>
</select>
</div>

<div class="col-md-3">
<label>SEARCH</label>
<input type="text" name="q"
value="<?= htmlspecialchars($search) ?>"
placeholder="LP / CAT / EQUIPMENT"
class="form-control form-control-sm">
</div>

<div class="col-md-2 text-end">
<label>&nbsp;</label><br>
<button class="btn btn-primary btn-sm">FILTER</button>
<a href="report_store_date_wise.php" class="btn btn-secondary btn-sm">RESET</a>
</div>

</form>

<div class="text-end fw-bold mb-1">
TOTAL RECORDS : <?= $total ?>
</div>

<!-- TABLE -->
<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead>
<tr>
    <th>S.NO</th>
    <th>LP</th>
    <th>CAT</th>
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
$sn = 1;
if ($total == 0) {
    echo "<tr><td colspan='10' class='text-center'>NO RECORDS FOUND</td></tr>";
}
while ($row = mysqli_fetch_assoc($res)) {
    echo "<tr>
        <td>{$sn}</td>
        <td>{$row['lp_no']}</td>
        <td>{$row['cat_part_no']}</td>
        <td>".htmlspecialchars($row['equipment_name'])."</td>
        <td>{$row['au']}</td>
        <td>{$row['qty']}</td>
        <td>{$row['cost']}</td>
        <td>".htmlspecialchars($row['unit_name'])."</td>
        <td>{$row['issue_date']}</td>
        <td class='no-print'>
            <a href='issue_edit.php?id={$row['id']}' class='btn btn-warning btn-sm'>Edit</a>
            <a href='issue_delete.php?id={$row['id']}'
               onclick=\"return confirm('Delete this issue entry?')\"
               class='btn btn-danger btn-sm'>Delete</a>
        </td>
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
