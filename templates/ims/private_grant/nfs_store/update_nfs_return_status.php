<?php
session_start();

/* 🔥 PATH FIX */
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../connect.php";

require_admin();

/* ROLE GUARD */
if (
    !isset($_SESSION['id']) ||
    !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin','super_admin'])
) {
    header('Location: ../../user_dboard.php');
    exit;
}

/* GET PARAMS */
$return_id = intval($_GET['id'] ?? 0);
$status    = $_GET['status'] ?? '';

if ($return_id <= 0 || !in_array($status, ['APPROVED','REJECTED'])) {
    die("Invalid Request");
}

/* START TRANSACTION */
$connect->begin_transaction();

try {

    /* 🔎 FETCH RETURN RECORD (LOCK) */
    $stmt = $connect->prepare(
        "SELECT equipment_id, return_qty, status
         FROM nfs_return_equipment
         WHERE id = ? FOR UPDATE"
    );
    $stmt->bind_param("i", $return_id);
    $stmt->execute();
    $stmt->bind_result($equipment_id, $return_qty, $current_status);
    $stmt->fetch();
    $stmt->close();

    if ($equipment_id === null) {
        throw new Exception("Return record not found");
    }

    /* 🚫 PREVENT DOUBLE ACTION */
    if ($current_status !== 'PENDING') {
        throw new Exception("This return is already processed");
    }

    /* ✅ IF APPROVED → ADD BACK STOCK */
    if ($status === 'APPROVED') {

        /* 🔎 LOCK EQUIPMENT ROW */
        $stmt = $connect->prepare(
            "SELECT qty_received
             FROM nfs_equipment
             WHERE id = ? FOR UPDATE"
        );
        $stmt->bind_param("i", $equipment_id);
        $stmt->execute();
        $stmt->bind_result($current_stock);
        $stmt->fetch();
        $stmt->close();

        if ($current_stock === null) {
            throw new Exception("Equipment not found");
        }

        $new_stock = $current_stock + $return_qty;

        /* ➕ UPDATE STOCK */
        $stmt = $connect->prepare(
            "UPDATE nfs_equipment
             SET qty_received = ?
             WHERE id = ?"
        );
        $stmt->bind_param("ii", $new_stock, $equipment_id);
        $stmt->execute();
        $stmt->close();
    }

    /* 🔄 UPDATE RETURN STATUS */
    $stmt = $connect->prepare(
        "UPDATE nfs_return_equipment
         SET status = ?
         WHERE id = ?"
    );
    $stmt->bind_param("si", $status, $return_id);
    $stmt->execute();
    $stmt->close();

    /* ✅ COMMIT */
    $connect->commit();

    header("Location: nfs_return_approval.php?updated=1");
    exit;

} catch (Exception $e) {

    /* ❌ ROLLBACK */
    $connect->rollback();
    die("Error: " . $e->getMessage());
}

$connect->close();
