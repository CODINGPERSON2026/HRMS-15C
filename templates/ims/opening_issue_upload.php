<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";
require_once "vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

$rows = [];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_FILES['excel']) &&
    isset($_POST['preview'])
) {
    if ($_FILES['excel']['error'] !== 0) {
        die("Excel upload failed");
    }

    $spreadsheet = IOFactory::load($_FILES['excel']['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();

    // A,B,C indexing
    $rows = $sheet->toArray(null, true, true, true);

    if (count($rows) <= 1) {
        die("Excel file has no data");
    }

    $_SESSION['OPENING_ISSUE_EXCEL'] = $rows;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Opening Issue Upload</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<style>
body{background:#f4f6fb}
.table td,.table th{font-size:0.8rem}
</style>
</head>

<body>
<div class="container mt-4">
<div class="card shadow p-4">

<h4 class="text-center mb-3">📤 Opening Issue Upload</h4>

<form method="POST" enctype="multipart/form-data">
    <label class="fw-bold">Select Excel File</label>
    <input type="file" name="excel" class="form-control" accept=".xls,.xlsx" required>

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

<form method="POST" action="opening_issue_import.php">
    <input type="hidden" name="do_import" value="1">
    <button class="btn btn-success w-100 mt-2">
        ✅ Confirm & Import
    </button>
</form>

<?php } ?>

<a href="dboard.php" class="btn btn-secondary w-100 mt-3">⬅ Back</a>

</div>
</div>
</body>
</html>
