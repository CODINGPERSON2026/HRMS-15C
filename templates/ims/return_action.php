<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

if (!in_array($_SESSION['role'], ['admin','super_admin'])) {
    exit('Unauthorized');
}

$id     = (int)($_GET['id'] ?? 0);
$action = $_GET['a'] ?? '';

if (!$id || !in_array($action, ['approve','reject'])) {
    exit('Invalid request');
}

/* 🔹 Fetch return request */
$stmt = $connect->prepare("
    SELECT rr.*, em.qty_available, em.qty_received
    FROM return_requests rr
    JOIN equipment_master em ON em.id = rr.equipment_id
    WHERE rr.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();

if (!$r) {
    exit('Return request not found');
}

$connect->begin_transaction();

try {

    if ($action === 'approve') {

        /* 🔐 SAFETY CHECKS */

        // ❌ Over return check
        if ($r['qty'] <= 0) {
            throw new Exception("Invalid return quantity");
        }

        // ❌ Stock overflow check
        if (($r['qty_available'] + $r['qty']) > $r['qty_received']) {
            throw new Exception("Return exceeds received quantity");
        }

        /* 🔁 Update stock */
        $stmtStock = $connect->prepare("
            UPDATE equipment_master
            SET qty_available = qty_available + ?
            WHERE id = ?
        ");
        $stmtStock->bind_param("ii", $r['qty'], $r['equipment_id']);
        $stmtStock->execute();

        /* 🧾 Insert RETURN txn */
        $stmtTxn = $connect->prepare("
            INSERT INTO equipment_txn
            (equipment_id, txn_type, qty, issue_type, unit_name, remarks, created_by)
            VALUES (?, 'RETURN', ?, 'NORMAL', ?, ?, ?)
        ");

        $remarks = 'Approved return';
        $stmtTxn->bind_param(
            "iissi",
            $r['equipment_id'],
            $r['qty'],
            $r['unit_name'],
            $remarks,
            $_SESSION['id']
        );
        $stmtTxn->execute();

        /* ✅ Mark approved */
        $stmtApprove = $connect->prepare("
            UPDATE return_requests
            SET status='APPROVED', approved_at=NOW()
            WHERE id=?
        ");
        $stmtApprove->bind_param("i", $id);
        $stmtApprove->execute();
    }

    else { // reject
        $stmtReject = $connect->prepare("
            UPDATE return_requests
            SET status='REJECTED'
            WHERE id=?
        ");
        $stmtReject->bind_param("i", $id);
        $stmtReject->execute();
    }

    $connect->commit();

} catch (Exception $e) {
    $connect->rollback();
    die("Return process failed: " . $e->getMessage());
}

header("Location: admin_return_approval.php?done=1");
exit;
