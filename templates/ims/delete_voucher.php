<?php
session_start();
require_once "connect.php";

if (!isset($_GET['id'])) {
    die("Voucher ID missing");
}

$voucher_id = (int)$_GET['id'];

/* First delete voucher items */
$stmt1 = $connect->prepare("DELETE FROM voucher_items WHERE voucher_id = ?");
$stmt1->bind_param("i", $voucher_id);
$stmt1->execute();
$stmt1->close();

/* Then delete voucher master */
$stmt2 = $connect->prepare("DELETE FROM voucher_master WHERE id = ?");
$stmt2->bind_param("i", $voucher_id);
$stmt2->execute();
$stmt2->close();

header("Location: voucher_list.php");
exit;
?>