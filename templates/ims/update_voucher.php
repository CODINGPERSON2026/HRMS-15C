<?php
require_once "connect.php";

$id = (int)$_GET['id'];

$iv_no     = $_POST['iv_no'];
$iv_unit   = $_POST['iv_unit'];
$iv_stn    = $_POST['iv_stn'];
$iv_date   = $_POST['iv_date'];

$rv_no     = $_POST['rv_no'];
$rv_unit   = $_POST['rv_unit'];
$rv_stn    = $_POST['rv_stn'];
$rv_date   = $_POST['rv_date'];

$issued_to = $_POST['issued_to'];
$auth      = $_POST['auth'];
$note      = $_POST['note'];

$stmt = $connect->prepare("
    UPDATE voucher_master
    SET
        iv_no=?,
        iv_unit=?,
        iv_stn=?,
        iv_date=?,
        rv_no=?,
        rv_unit=?,
        rv_stn=?,
        rv_date=?,
        issued_to=?,
        auth=?,
        note=?
    WHERE id=?
");

$stmt->bind_param(
    "sssssssssssi",
    $iv_no,
    $iv_unit,
    $iv_stn,
    $iv_date,
    $rv_no,
    $rv_unit,
    $rv_stn,
    $rv_date,
    $issued_to,
    $auth,
    $note,
    $id
);

$stmt->execute();
$stmt->close();

header("Location: voucher_view.php?id=".$id);
exit;
?>