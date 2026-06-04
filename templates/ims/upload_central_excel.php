<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";
require_once "vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/* ================= INIT ================= */
$sheet = null;

/* ================= HANDLE UPLOAD ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel'])) {

    if ($_FILES['excel']['error'] !== 0) {
        die("Excel upload failed");
    }

    $spreadsheet = IOFactory::load($_FILES['excel']['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();

    $_SESSION['EXCEL_SHEET'] = serialize($spreadsheet);
}

/* ================= HANDLE IMPORT ================= */
if (isset($_POST['import']) && isset($_SESSION['EXCEL_SHEET'])) {

    $spreadsheet = unserialize($_SESSION['EXCEL_SHEET']);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();

    $stmt = $connect->prepare("
        INSERT INTO equipment_master
        (
            grant_id,
            equipment_name,
            lp_no,
            cat_part_no,
            au,
            qty_received,
            qty_available,
            cost,
            received_date
        )
        VALUES (?,?,?,?,?,?,?,?,?)
    ");

    for ($row = 2; $row <= $highestRow; $row++) {

        $grant_id = trim($sheet->getCell("B$row")->getValue());
        $name     = trim($sheet->getCell("D$row")->getValue());
        $lp_no    = trim($sheet->getCell("E$row")->getValue());
        $cat_part = trim($sheet->getCell("F$row")->getValue());
        $au       = trim($sheet->getCell("G$row")->getValue());
        $qty      = (int)$sheet->getCell("I$row")->getValue();
        $cost     = (float)$sheet->getCell("J$row")->getValue();

        /* ================= DATE – FINAL & SAFE ================= */

        $rawValue = $sheet->getCell("H$row")->getValue();           // RAW
        $dispVal  = trim($sheet->getCell("H$row")->getFormattedValue()); // DISPLAY

        $received_date = null;

        // Case 1: REAL excel date (numeric)
        if (is_numeric($rawValue)) {
            $received_date = date(
                'Y-m-d',
                ExcelDate::excelToTimestamp($rawValue)
            );
        }
        // Case 2: Text date
        elseif ($dispVal !== '') {

            $dt =
                DateTime::createFromFormat('Y-m-d', $dispVal) ?:
                DateTime::createFromFormat('Y/m/d', $dispVal) ?:
                DateTime::createFromFormat('d-m-Y', $dispVal) ?:
                DateTime::createFromFormat('d/m/Y', $dispVal);

            if ($dt) {
                $received_date = $dt->format('Y-m-d');
            }
        }

        /* ❗ VERY IMPORTANT: allow NULL (no fallback date) */
        if (!$received_date) {
            $received_date = null;
        }

        if (!$grant_id || $name === '' || $qty <= 0) {
            continue;
        }

        $qty_available = $qty;
        $cost = $cost ?: 0;


        $stmt->bind_param(
    "issssidis",
    $grant_id,
    $name,
    $lp_no,
    $cat_part,
    $au,
    $qty,
    $qty_available,
    $cost,
    $received_date   // STRING
);

        $stmt->execute();
    }

    $stmt->close();
    unset($_SESSION['EXCEL_SHEET']);

    header("Location: central_view.php?excel_import=success");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Excel Upload – Central Equipment</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
</head>

<body class="bg-light">
<div class="container mt-4">
<div class="card shadow p-4">

<h4 class="text-center mb-3">📤 Excel Upload – Central Equipment</h4>

<form method="POST" enctype="multipart/form-data">
    <label>Select Excel File</label>
    <input type="file" name="excel" class="form-control" required>

    <div class="d-flex gap-2 mt-3">
        <button name="preview" class="btn btn-primary w-50">🔍 Preview</button>
        <button name="import"  class="btn btn-success w-50">✅ Import</button>
    </div>
</form>

<?php if ($sheet) { ?>

<hr>
<h6 class="text-muted">Preview (Excel Data)</h6>

<div class="table-responsive">
<table class="table table-bordered table-sm">
<thead class="table-dark">
<tr>
<?php
$highestCol = $sheet->getHighestColumn();
foreach ($sheet->rangeToArray("A1:$highestCol"."1")[0] as $head) {
    echo "<th>".htmlspecialchars($head)."</th>";
}
?>
</tr>
</thead>
<tbody>
<?php
$highestRow = $sheet->getHighestRow();
for ($r=2; $r <= $highestRow; $r++) {
    echo "<tr>";
    foreach ($sheet->rangeToArray("A$r:$highestCol$r")[0] as $cell) {
        echo "<td>".htmlspecialchars($cell)."</td>";
    }
    echo "</tr>";
}
?>
</tbody>
</table>
</div>

<div class="alert alert-info mt-2">
• Excel date auto-detected (numeric / text)<br>
• Stored safely as <b>YYYY-MM-DD</b><br>
• No more <code>0000-00-00</code>
</div>

<?php } ?>

<a href="dboard.php" class="btn btn-secondary w-100 mt-3">⬅ Back</a>

</div>
</div>
</body>
</html>
