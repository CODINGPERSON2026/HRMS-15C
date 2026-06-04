<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Invalid request");
}

if (empty($_SESSION['VOUCHER_ITEMS'])) {
    exit("No voucher items found");
}

$voucher_type = "LOAN RENEWAL";

$iv_no   = $_POST['iv_no']   ?? '';
$iv_unit = $_POST['iv_unit'] ?? '';
$iv_stn  = $_POST['iv_stn']  ?? '';
$iv_date = !empty($_POST['iv_date']) ? $_POST['iv_date'] : NULL;

$rv_no   = $_POST['rv_no']   ?? '';
$rv_unit = $_POST['rv_unit'] ?? '';
$rv_stn  = $_POST['rv_stn']  ?? '';
$rv_date = !empty($_POST['rv_date']) ? $_POST['rv_date'] : NULL;

$issued_to = $_POST['issued_to'] ?? '';
$auth      = $_POST['auth'] ?? '';
$note      = $_POST['note'] ?? '';

$created_by = $_SESSION['id'];

$connect->begin_transaction();

try {

    /* ================= INSERT MASTER ================= */

    $stmt = $connect->prepare("
        INSERT INTO voucher_master
        (voucher_type, iv_no, iv_unit, iv_stn, iv_date,
         rv_no, rv_unit, rv_stn, rv_date,
         issued_to, auth, note, created_by, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())
    ");

    $stmt->bind_param(
        "ssssssssssssi",
        $voucher_type,
        $iv_no, $iv_unit, $iv_stn, $iv_date,
        $rv_no, $rv_unit, $rv_stn, $rv_date,
        $issued_to, $auth, $note,
        $created_by
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $voucher_id = $stmt->insert_id;
    $stmt->close();


   /* ================= INSERT ITEMS ================= */

$item_stmt = $connect->prepare("
    INSERT INTO voucher_items
    (voucher_id, lp_no, cat_part_no, nomenclature, au, qty, cost, remarks, barcode)
    VALUES (?,?,?,?,?,?,?,?,?)
");

foreach ($_SESSION['VOUCHER_ITEMS'] as $it) {

    $lp   = $it['lp'];
    $cat  = $it['cat'] ?? '';
    $name = $it['name'];
    $au   = $it['au'];
    $qty  = (int)$it['qty'];
    $cost = $it['cost'] ?? 0;
    $remarks = $it['remarks'] ?? '';

    /* ================= BARCODE GENERATION FIX ================= */

    $barcode_array = [];

    // ✅ If already exists in session → use it
    if (!empty($it['barcodes']) && is_array($it['barcodes'])) {

        $barcode_array = $it['barcodes'];

    } else {

        // 🔥 Generate qty wise barcode automatically
        for ($i = 1; $i <= $qty; $i++) {

            $barcode_array[] = "V".$voucher_id."_".uniqid();
        }
    }

    $barcode_data = json_encode($barcode_array);

    /* ================= INSERT ================= */

    $item_stmt->bind_param(
        "issssidss",
        $voucher_id,
        $lp,
        $cat,
        $name,
        $au,
        $qty,
        $cost,
        $remarks,
        $barcode_data
    );

    if (!$item_stmt->execute()) {
        throw new Exception($item_stmt->error);
    }
}

$item_stmt->close();

    $connect->commit();

    unset($_SESSION['VOUCHER_ITEMS']);
    unset($_SESSION['VOUCHER_HEADER']);

    echo "SUCCESS|" . $voucher_id;
    exit;

} catch (Exception $e) {

    $connect->rollback();
    echo "Voucher save failed : " . $e->getMessage();
    exit;
}