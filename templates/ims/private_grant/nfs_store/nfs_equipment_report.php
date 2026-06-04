<?php
session_start();

/* PATH FIX */
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../connect.php";

require_admin();

/* ROLE GUARD */
if (
    !isset($_SESSION['id']) ||
    !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin','super_admin'])
) {
    header('Location: ../../user_dboard.php');
    exit;
}

/* FILTERS */
$category = trim($_GET['category'] ?? '');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate   = trim($_GET['to_date'] ?? '');

$where = [];
$params = [];
$types  = "";

/* ONLY NFS */
$where[] = "gm.grant_type = 'NFS'";

/* CATEGORY FILTER */
if ($category !== '') {
    $where[] = "gm.grant_name = ?";
    $params[] = $category;
    $types   .= "s";
}

/* DATE FILTER */
if ($fromDate && $toDate) {
    $where[] = "em.received_date BETWEEN ? AND ?";
    $params[] = $fromDate;
    $params[] = $toDate;
    $types   .= "ss";
}

$whereSql = implode(" AND ", $where);

/* QUERY */
$sql = "
SELECT
    gm.grant_name,
    em.equipment_name,
    em.serial_no,
    em.au,
    em.qty_received,
    em.qty_available,
    em.cost,
    em.received_date,
    MAX(DATE(et.created_at)) AS last_issue_date,
    GROUP_CONCAT(
        CONCAT(et.unit_name,' : ',et.qty)
        SEPARATOR '\n'
    ) AS distribution
FROM equipment_master em
JOIN grants_master gm ON gm.id = em.grant_id
LEFT JOIN equipment_txn et ON et.equipment_id = em.id
WHERE $whereSql
GROUP BY em.id
ORDER BY gm.grant_name, em.equipment_name
";

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
<title>NFS Equipment Report</title>

<link rel="stylesheet" href="../../css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:20px;
    font-family:Segoe UI;
}
.card{
    background:#fff;
    border-radius:18px;
    box-shadow:0 8px 20px rgba(0,0,0,.15);
    padding:20px;
}
.page-title{
    font-size:22px;
    font-weight:800;
    color:#004d40;
    margin-bottom:15px;
}
.filter-box{
    background:#f9f9f9;
    border-radius:14px;
    padding:15px;
    margin-bottom:20px;
}
.table thead{
    background:#004d40;
    color:#fff;
}
.table th, .table td{
    vertical-align:middle;
    text-align:center;
    font-size:0.78rem;
}
.td-name{
    text-align:left;
    font-weight:600;
}
.dist{
    text-align:left;
    font-size:0.7rem;
    line-height:1.4;
    white-space:pre-line;
}

/* ===== PRINT ===== */
@media print{
    @page{
        size:A4 landscape;
        margin:8mm;
    }
    body{
        background:#fff;
        padding:0;
    }
    .no-print{
        display:none !important;
    }
    table{
        font-size:9px;
    }
}
</style>
</head>

<body>

<div class="card">

<div class="page-title">📊 NFS EQUIPMENT REPORT</div>

<!-- FILTER -->
<form method="GET" class="filter-box row g-2 no-print">

    <div class="col-md-3">
        <label>NFS Category</label>
        <select name="category" class="form-control">
            <option value="">All</option>
            <?php
            $gq = mysqli_query(
                $connect,
                "SELECT grant_name FROM grants_master WHERE grant_type='NFS' ORDER BY grant_name"
            );
            while ($g = mysqli_fetch_assoc($gq)) {
                $sel = ($category === $g['grant_name']) ? 'selected' : '';
                echo "<option $sel>{$g['grant_name']}</option>";
            }
            ?>
        </select>
    </div>

    <div class="col-md-3">
        <label>From Date</label>
        <input type="date" name="from_date"
               value="<?= htmlspecialchars($fromDate) ?>"
               class="form-control">
    </div>

    <div class="col-md-3">
        <label>To Date</label>
        <input type="date" name="to_date"
               value="<?= htmlspecialchars($toDate) ?>"
               class="form-control">
    </div>

    <div class="col-md-3 d-flex align-items-end gap-2">
        <button class="btn btn-success w-50">Filter</button>
        <a href="nfs_equipment_report.php"
           class="btn btn-secondary w-50">Reset</a>
    </div>

</form>

<!-- PRINT BUTTON -->
<div class="text-end mb-2 no-print">
    <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
        🖨 Print
    </button>
</div>

<!-- TABLE -->
<div class="table-responsive">
<table class="table table-bordered table-striped table-hover">
<thead>
<tr>
    <th>#</th>
    <th>Category</th>
    <th>Equipment Name</th>
    <th>Serial No</th>
    <th>A/U</th>
    <th>Qty Rec</th>
    <th>Qty Avl</th>
    <th>Issue Date</th>
    <th>Cost</th>
    <th>Distribution</th>
</tr>
</thead>
<tbody>

<?php
$i = 1;
if ($result->num_rows == 0) {
    echo "<tr>
        <td colspan='10' class='text-danger fw-bold'>
            No Records Found
        </td>
    </tr>";
} else {
    while ($row = $result->fetch_assoc()) {

        $dist = $row['distribution']
            ? nl2br("• ".str_replace("\n","\n• ",$row['distribution']))
            : '—';

        echo "<tr>
            <td>{$i}</td>
            <td>{$row['grant_name']}</td>
            <td class='td-name'>".htmlspecialchars($row['equipment_name'])."</td>
            <td>".($row['serial_no'] ?: '—')."</td>
            <td>{$row['au']}</td>
            <td>{$row['qty_received']}</td>
            <td><b>{$row['qty_available']}</b></td>
            <td>".($row['last_issue_date'] ?: '—')."</td>
            <td>{$row['cost']}</td>
            <td class='dist'>{$dist}</td>
        </tr>";
        $i++;
    }
}
?>

</tbody>
</table>
</div>

<a href="nfs_dashboard.php" class="btn btn-secondary mt-3 no-print">
⬅ Back to NFS Dashboard
</a>

</div>

</body>
</html>
