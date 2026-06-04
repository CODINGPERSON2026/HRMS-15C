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

/* ALLOW ONLY POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid Request");
}

/* GET & SANITIZE INPUTS */
$id              = intval($_POST['id'] ?? 0);
$nfs_category    = trim($_POST['nfs_category'] ?? '');
$equipment_name  = trim($_POST['equipment_name'] ?? '');
$make            = trim($_POST['make'] ?? '');
$serial_no       = trim($_POST['serial_no'] ?? '');
$au              = trim($_POST['au'] ?? '');
$qty_received    = intval($_POST['qty_received'] ?? 0);
$unit_cost       = floatval($_POST['unit_cost'] ?? 0);
$total_cost      = floatval($_POST['total_cost'] ?? 0);
$received_date   = trim($_POST['received_date'] ?? '');
$remarks         = trim($_POST['remarks'] ?? '');

/* BASIC VALIDATION */
if (
    $id <= 0 ||
    $nfs_category === '' ||
    $equipment_name === '' ||
    $qty_received <= 0 ||
    $received_date === ''
) {
    die("Required fields missing");
}

/* AUTO TOTAL COST (IF EMPTY) */
if ($total_cost <= 0 && $unit_cost > 0) {
    $total_cost = $unit_cost * $qty_received;
}

/* START TRANSACTION */
$connect->begin_transaction();

try {

    /* 🔎 CHECK EXISTING RECORD */
    $stmt = $connect->prepare(
        "SELECT qty_received FROM nfs_equipment WHERE id = ? FOR UPDATE"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($old_qty);
    $stmt->fetch();
    $stmt->close();

    if ($old_qty === null) {
        throw new Exception("Equipment not found");
    }

    /* 🚫 SAFETY CHECK
       (If equipment already issued, qty_received should not go below issued qty)
       Currently we only ensure qty_received >= 0
    */

    if ($qty_received < 0) {
        throw new Exception("Quantity cannot be negative");
    }

    /* 🔄 UPDATE RECORD */
    $stmt = $connect->prepare(
        "UPDATE nfs_equipment SET
            nfs_category   = ?,
            equipment_name = ?,
            make           = ?,
            serial_no      = ?,
            au             = ?,
            qty_received   = ?,
            unit_cost      = ?,
            total_cost     = ?,
            received_date  = ?,
            remarks        = ?
         WHERE id = ?"
    );

    $stmt->bind_param(
        "sssssiddssi",
        $nfs_category,
        $equipment_name,
        $make,
        $serial_no,
        $au,
        $qty_received,
        $unit_cost,
        $total_cost,
        $received_date,
        $remarks,
        $id
    );

    if (!$stmt->execute()) {
        throw new Exception("Update failed: " . $stmt->error);
    }

    $stmt->close();

    /* ✅ COMMIT */
    $connect->commit();

    header("Location: nfs_new_arrival.php?updated=1");
    exit;

} catch (Exception $e) {

    /* ❌ ROLLBACK ON ERROR */
    $connect->rollback();
    die("Error: " . $e->getMessage());
}

$connect->close();
