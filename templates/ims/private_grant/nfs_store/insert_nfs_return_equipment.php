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

/* GET INPUTS */
$issue_id      = intval($_POST['issue_id'] ?? 0);
$return_qty   = intval($_POST['return_qty'] ?? 0);
$return_date  = $_POST['return_date'] ?? '';
$returned_from= trim($_POST['returned_from'] ?? '');
$remarks      = trim($_POST['remarks'] ?? '');

/* BASIC VALIDATION */
if (
    $issue_id <= 0 ||
    $return_qty <= 0 ||
    empty($return_date) ||
    empty($returned_from)
) {
    die("Required fields missing");
}

/* START TRANSACTION */
$connect->begin_transaction();

try {

    /* 🔎 FETCH ISSUE DETAILS (LOCK ROW) */
    $stmt = $connect->prepare(
        "SELECT equipment_id, issue_qty
         FROM nfs_issue_equipment
         WHERE id = ? FOR UPDATE"
    );
    $stmt->bind_param("i", $issue_id);
    $stmt->execute();
    $stmt->bind_result($equipment_id, $issued_qty);
    $stmt->fetch();
    $stmt->close();

    if ($equipment_id === null) {
        throw new Exception("Issued record not found");
    }

    if ($return_qty > $issued_qty) {
        throw new Exception("Return quantity exceeds issued quantity");
    }

    /* 🔎 FETCH CURRENT STOCK (LOCK ROW) */
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

    /* ➕ ADD BACK STOCK */
    $new_stock = $current_stock + $return_qty;

    $stmt = $connect->prepare(
        "UPDATE nfs_equipment
         SET qty_received = ?
         WHERE id = ?"
    );
    $stmt->bind_param("ii", $new_stock, $equipment_id);
    $stmt->execute();
    $stmt->close();

    /* 🧾 INSERT RETURN RECORD */
    $stmt = $connect->prepare(
        "INSERT INTO nfs_return_equipment
        (equipment_id, return_qty, return_date, returned_from, remarks, created_at)
        VALUES (?,?,?,?,?,NOW())"
    );
    $stmt->bind_param(
        "iisss",
        $equipment_id,
        $return_qty,
        $return_date,
        $returned_from,
        $remarks
    );
    $stmt->execute();
    $stmt->close();

    /* ✅ COMMIT */
    $connect->commit();

    header("Location: nfs_return_equipment.php?success=1");
    exit;

} catch (Exception $e) {

    /* ❌ ROLLBACK ON ERROR */
    $connect->rollback();
    die("Error: " . $e->getMessage());
}

$connect->close();
