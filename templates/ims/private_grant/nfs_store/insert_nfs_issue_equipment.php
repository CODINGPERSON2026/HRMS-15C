<?php
session_start();

/* 🔥 ABSOLUTE PATH FIX */
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
$equipment_id = intval($_POST['equipment_id'] ?? 0);
$issue_qty    = intval($_POST['issue_qty'] ?? 0);
$issue_date   = $_POST['issue_date'] ?? '';
$issued_to    = trim($_POST['issued_to'] ?? '');
$remarks      = trim($_POST['remarks'] ?? '');

/* BASIC VALIDATION */
if (
    $equipment_id <= 0 ||
    $issue_qty <= 0 ||
    empty($issue_date) ||
    empty($issued_to)
) {
    die("Required fields missing");
}

/* START TRANSACTION */
$connect->begin_transaction();

try {

    /* 🔎 CHECK CURRENT STOCK */
    $stmt = $connect->prepare(
        "SELECT qty_received FROM nfs_equipment WHERE id = ? FOR UPDATE"
    );
    $stmt->bind_param("i", $equipment_id);
    $stmt->execute();
    $stmt->bind_result($available_qty);
    $stmt->fetch();
    $stmt->close();

    if ($available_qty === null) {
        throw new Exception("Equipment not found");
    }

    if ($issue_qty > $available_qty) {
        throw new Exception("Issue quantity exceeds available stock");
    }

    /* 📉 UPDATE STOCK */
    $new_qty = $available_qty - $issue_qty;

    $stmt = $connect->prepare(
        "UPDATE nfs_equipment SET qty_received = ? WHERE id = ?"
    );
    $stmt->bind_param("ii", $new_qty, $equipment_id);
    $stmt->execute();
    $stmt->close();

    /* 🧾 INSERT ISSUE RECORD */
    $stmt = $connect->prepare(
        "INSERT INTO nfs_issue_equipment
        (equipment_id, issue_qty, issue_date, issued_to, remarks, created_at)
        VALUES (?,?,?,?,?,NOW())"
    );
    $stmt->bind_param(
        "iisss",
        $equipment_id,
        $issue_qty,
        $issue_date,
        $issued_to,
        $remarks
    );
    $stmt->execute();
    $stmt->close();

    /* ✅ COMMIT */
    $connect->commit();

    header("Location: nfs_issue_equipment.php?success=1");
    exit;

} catch (Exception $e) {

    /* ❌ ROLLBACK ON ERROR */
    $connect->rollback();
    die("Error: " . $e->getMessage());
}

$connect->close();
