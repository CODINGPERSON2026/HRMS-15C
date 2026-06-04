<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* ADMIN GUARD */
if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['admin','super_admin'])) {
    header("location:logout.php");
    exit;
}

/* FILTER INPUTS */
$user     = trim($_GET['user'] ?? '');
$module   = trim($_GET['module'] ?? '');
$action   = trim($_GET['action'] ?? '');
$fromDate = trim($_GET['from'] ?? '');
$toDate   = trim($_GET['to'] ?? '');

/* BUILD QUERY */
$where = [];
$params = [];
$types  = '';

if ($user !== '') {
    $where[] = "username LIKE ?";
    $params[] = "%$user%";
    $types .= 's';
}
if ($module !== '') {
    $where[] = "module = ?";
    $params[] = $module;
    $types .= 's';
}
if ($action !== '') {
    $where[] = "action = ?";
    $params[] = $action;
    $types .= 's';
}
if ($fromDate !== '') {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $fromDate;
    $types .= 's';
}
if ($toDate !== '') {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $toDate;
    $types .= 's';
}

$sql = "SELECT * FROM audit_logs";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC LIMIT 500";

$stmt = $connect->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<title>Audit Logs</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<style>
body{background:#eef5ff;padding:25px;font-family:Segoe UI}
.card{
    max-width:1400px;margin:auto;background:#fff;
    padding:20px;border-radius:15px;
    box-shadow:0 6px 15px rgba(0,0,0,.15)
}
</style>
</head>
<body>

<div class="card">
<h4 class="text-center mb-3">📜 Audit Log Viewer</h4>

<form class="row g-2 mb-3">
    <div class="col">
        <input type="text" name="user" value="<?= htmlspecialchars($user) ?>"
               class="form-control" placeholder="Username">
    </div>
    <div class="col">
        <select name="module" class="form-control">
            <option value="">All Modules</option>
            <option <?= $module=='EQUIPMENT'?'selected':'' ?>>EQUIPMENT</option>
            <option <?= $module=='RETURN'?'selected':'' ?>>RETURN</option>
            <option <?= $module=='ISSUE'?'selected':'' ?>>ISSUE</option>
            <option <?= $module=='LOGIN'?'selected':'' ?>>LOGIN</option>
        </select>
    </div>
    <div class="col">
        <select name="action" class="form-control">
            <option value="">All Actions</option>
            <option <?= $action=='ADD'?'selected':'' ?>>ADD</option>
            <option <?= $action=='UPDATE'?'selected':'' ?>>UPDATE</option>
            <option <?= $action=='APPROVE'?'selected':'' ?>>APPROVE</option>
            <option <?= $action=='REJECT'?'selected':'' ?>>REJECT</option>
        </select>
    </div>
    <div class="col">
        <input type="date" name="from" value="<?= $fromDate ?>" class="form-control">
    </div>
    <div class="col">
        <input type="date" name="to" value="<?= $toDate ?>" class="form-control">
    </div>
    <div class="col">
        <button class="btn btn-primary w-100">🔍 Filter</button>
    </div>
</form>

<a href="audit_logs_export.php?<?= http_build_query($_GET) ?>"
   class="btn btn-success mb-2">⬇ Export CSV</a>

<table class="table table-bordered table-sm text-center align-middle">
<thead class="table-light">
<tr>
    <th>#</th>
    <th>Date</th>
    <th>User</th>
    <th>Action</th>
    <th>Module</th>
    <th>Description</th>
    <th>IP</th>
</tr>
</thead>
<tbody>
<?php
$i=1;
while($row=$result->fetch_assoc()){
    echo "<tr>
        <td>{$i}</td>
        <td>".date('d-m-Y H:i',strtotime($row['created_at']))."</td>
        <td>{$row['username']}</td>
        <td><b>{$row['action']}</b></td>
        <td>{$row['module']}</td>
        <td class='text-start'>{$row['description']}</td>
        <td>{$row['ip_address']}</td>
    </tr>";
    $i++;
}
if($i==1){
    echo "<tr><td colspan='7' class='text-danger'>No logs found</td></tr>";
}
?>
</tbody>
</table>

<a href="dboard.php" class="btn btn-secondary mt-2">⬅ Back</a>
</div>

</body>
</html>
