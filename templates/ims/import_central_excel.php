<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

if (!isset($_SESSION['EXCEL_DATA'])) {
    die("No Excel Data Found");
}

$data = $_SESSION['EXCEL_DATA'];
unset($_SESSION['EXCEL_DATA']);

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

for ($i = 1; $i < count($data); $i++) {

    [
        $grant_type,   // Excel col 1 (ignore / mapping ke liye)
        $grant_id,     // Excel col 2  ✅ MUST BE grant_id (INT)
        $sub_grant,    // ignore
        $name,
        $lp_no,
        $cat_part,
        $au,
        $date,
        $qty,
        $cost
    ] = array_pad($data[$i], 10, null);

    if (trim($name) === '' || $qty <= 0) {
        continue;
    }

    /* qty_available same as qty_received on first entry */
    $qty_available = $qty;

    /* Date convert if Excel numeric */
    if (is_numeric($date)) {
        $date = date(
            'Y-m-d',
            \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($date)
        );
    }

    $stmt->bind_param(
        "issssidid",
        $grant_id,
        $name,
        $lp_no,
        $cat_part,
        $au,
        $qty,
        $qty_available,
        $cost,
        $date
    );

    $stmt->execute();
}

$stmt->close();

header("Location: central_view.php?excel_import=success");
exit;
