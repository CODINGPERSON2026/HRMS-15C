<?php
session_start();
require_once "connect.php";

/* ADMIN GUARD */
if (
    !isset($_SESSION['id']) ||
    !in_array($_SESSION['role'], ['admin','super_admin'])
) {
    header("location:logout.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("Invalid Voucher ID");
}

mysqli_begin_transaction($connect);

try {

    /* DELETE CHILD ITEMS FIRST */
    mysqli_query($connect, "
        DELETE FROM return_vouchers_items
        WHERE return_voucher_id = $id
    ");

    /* DELETE MAIN VOUCHER */
    mysqli_query($connect, "
        DELETE FROM return_vouchers
        WHERE id = $id
    ");

    mysqli_commit($connect);

    header("Location: view_return_voucher.php?msg=deleted");
    exit;

} catch (Exception $e) {

    mysqli_rollback($connect);
    die("Delete failed: " . $e->getMessage());
}
?>