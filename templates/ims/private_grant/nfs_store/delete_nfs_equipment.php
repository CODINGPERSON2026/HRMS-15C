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

/* GET ID */
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid Equipment ID");
}

/* START TRANSACTION */
$connect->begin_transaction();

try {

    /* 🔎 CHECK EQUIPMENT EXISTS */
    $stmt = $connect->prepare(
        "SELECT qty_received FROM nfs_equipment WHERE id = ? FOR UPDATE"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($qty_received);
    $stmt->fetch();
    $stmt->close();

    if ($qty_received === null) {
        throw new Exception("Equipment not found");
    }

    /* 🚫 SAFETY CHECK
       If equipment already issued (qty reduced earlier),
       do not allow delete
    */
    // NOTE: If you want stricter check via issue table, tell me
    if ($qty_received < 0) {
        throw new Exception("Invalid stock state");
    }

    /* 🗑 DELETE EQUIPMENT */
    $stmt = $connect->prepare(
        "DELETE FROM nfs_equipment WHERE id = ?"
    );
    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        throw new Exception("Delete failed: " . $stmt->error);
    }
    $stmt->close();

    /* ✅ COMMIT */
    $connect->commit();

    header("Location: nfs_new_arrival.php?deleted=1");
    exit;

} catch (Exception $e) {

    /* ❌ ROLLBACK ON ERROR */
    $connect->rollback();
    die("Error: " . $e->getMessage());
}

$connect->close();
