<?php
session_start();
require_once "auth.php";
require_super_admin();
require_once "connect.php";

/* SAFETY CHECK */
if (!isset($_SESSION['VOUCHER_ITEMS'])) {
    die("Voucher session not found");
}

/* LOCK CHECK (after save / print) */
if (!empty($_SESSION['VOUCHER_LOCKED'])) {
    die("Voucher already saved / printed. Return disabled.");
}

$index = isset($_GET['index']) ? (int)$_GET['index'] : -1;

if ($index < 0 || !isset($_SESSION['VOUCHER_ITEMS'][$index])) {
    die("Invalid item index");
}

$item = $_SESSION['VOUCHER_ITEMS'][$index];

/* 1️⃣ RETURN QTY TO CENTRAL STOCK */
$stmt = $connect->prepare("
    UPDATE equipment_master
    SET qty_available = qty_available + ?
    WHERE id = ?
");
$stmt->bind_param("ii", $item['qty'], $item['equipment_id']);
$stmt->execute();
$stmt->close();

/* 2️⃣ TRANSACTION LOG (RETURN) */
$stmt = $connect->prepare("
    INSERT INTO equipment_txn
    (equipment_id, txn_type, qty, created_by)
    VALUES (?, 'RETURN', ?, ?)
");
$stmt->bind_param(
    "iii",
    $item['equipment_id'],
    $item['qty'],
    $_SESSION['id']
);
$stmt->execute();
$stmt->close();

/* 3️⃣ REMOVE FROM VOUCHER SESSION */
unset($_SESSION['VOUCHER_ITEMS'][$index]);

/* REINDEX ARRAY */
$_SESSION['VOUCHER_ITEMS'] = array_values($_SESSION['VOUCHER_ITEMS']);

/* 4️⃣ REDIRECT BACK TO VOUCHER */
header("Location: voucher_editable.php");
exit;
