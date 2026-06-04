<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";
require_once "vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

/* LOAD NFS GRANTS */
$grants = mysqli_query(
    $connect,
    "SELECT id, grant_name
     FROM grants_master
     WHERE grant_type='NFS'
     ORDER BY grant_name"
);

$rows = [];
$error = "";
$selectedGrantId = $_POST['grant_id'] ?? '';

/* UPLOAD & PREVIEW */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview'])) {

    if (empty($selectedGrantId)) {
        $error = "Please select NFS Grant";
    }
    elseif (!isset($_FILES['excel']) || $_FILES['excel']['error'] !== 0) {
        $error = "Please select valid Excel file";
    }
    else {
        $spreadsheet = IOFactory::load($_FILES['excel']['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) <= 1) {
            $error = "Excel has no data";
            $rows = [];
        } else {
            $_SESSION['OPENING_ISSUE_EXCEL']    = $rows;
            $_SESSION['OPENING_ISSUE_GRANT_ID'] = $selectedGrantId;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>NFS Opening Issue Upload</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<style>
body{background:#f4f6fb;font-family:Segoe UI,Arial}
.card{border-radius:14px}
.table td,.table th{font-size:0.8rem}
</style>
</head>

<body>
<div class="container mt-4">
<div class="card shadow p-4">

<h4 class="text-center mb-2">📤 NFS Opening Issue Upload</h4>
<p class="text-center text-muted">
Opening Issue = already issued stock (Available Qty = 0)
</p>

<?php if ($error) { ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php } ?>

<?php if (isset($_GET['success'])) { ?>
<div class="alert alert-success">✅ Import successful</div>
<?php } ?>

<form method="POST" enctype="multipart/form-data">

<label class="fw-bold">Select NFS Grant</label>
<select name="grant_id" class="form-control" required>
<option value="">-- Select Grant --</option>
<?php while($g = mysqli_fetch_assoc($grants)) { ?>
<option value="<?= $g['id'] ?>"
<?= ($selectedGrantId == $g['id'] ? 'selected' : '') ?>>
<?= htmlspecialchars($g['grant_name']) ?>
</option>
<?php } ?>
</select>

<label class="fw-bold mt-2">Excel File</label>
<input type="file" name="excel" class="form-control"
       accept=".xls,.xlsx" required>

<button name="preview" class="btn btn-primary w-100 mt-3">
🔍 Upload & Preview
</button>
</form>

<?php if (!empty($rows)) { ?>

<hr>
<h6 class="text-muted">📄 Preview</h6>

<div class="table-responsive">
<table class="table table-bordered table-sm">
<thead class="table-dark">
<tr>
<?php foreach ($rows[1] as $h) echo "<th>".htmlspecialchars($h)."</th>"; ?>
</tr>
</thead>
<tbody>
<?php
foreach ($rows as $i=>$r){
    if($i==1) continue;
    echo "<tr>";
    foreach($r as $c) echo "<td>".htmlspecialchars($c)."</td>";
    echo "</tr>";
}
?>
</tbody>
</table>
</div>

<form method="POST" action="nfs_opening_issue_import.php">
<button class="btn btn-success w-100 mt-2">
✅ Confirm & Import
</button>
</form>

<?php } ?>

<a href="nfs_dashboard.php" class="btn btn-secondary w-100 mt-3">
⬅ Back to Dashboard
</a>

</div>
</div>
</body>
</html>
