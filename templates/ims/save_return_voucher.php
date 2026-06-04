<?php
session_start();
require_once "connect.php";

$voucher_id = (int)($_GET['id'] ?? 0);

if ($voucher_id <= 0) {
    die("Invalid Voucher ID");
}

$iv_no         = $_POST['iv_no'] ?? '';
$iv_unit       = $_POST['iv_unit'] ?? '';
$iv_stn        = $_POST['iv_stn'] ?? '';
$iv_date = !empty($_POST['iv_date']) ? $_POST['iv_date'] : date('Y-m-d');


$rv_no         = $_POST['rv_no'] ?? '';
$returned_from = $_POST['returned_from'] ?? '';
$rv_stn        = $_POST['rv_stn'] ?? '';
$rv_date = !empty($_POST['rv_date']) ? $_POST['rv_date'] : date('Y-m-d');
$auth_text     = $_POST['auth_text'] ?? '';

/* date validation */
if (!empty($iv_date) && !strtotime($iv_date)) {
    die("Invalid IV Date");
}

if (!empty($rv_date) && !strtotime($rv_date)) {
    die("Invalid RV Date");
}

$stmt = $connect->prepare("
    UPDATE return_vouchers
    SET
        iv_no = ?,
        iv_unit = ?,
        iv_stn = ?,
        iv_date = ?,
        rv_no = ?,
        returned_from = ?,
        rv_stn = ?,
        rv_date = ?,
        auth_text = ?,
        is_saved = 1
    WHERE id = ?
");

$stmt->bind_param(
    "sssssssssi",
    $iv_no,
    $iv_unit,
    $iv_stn,
    $iv_date,
    $rv_no,
    $returned_from,
    $rv_stn,
    $rv_date,
    $auth_text,
    $voucher_id
);

if($stmt->execute()){
    echo "SUCCESS";
}else{
    echo "Save Failed: " . $stmt->error;
}
?>