<?php
session_start();
require_once "auth.php";
require_super_admin();
require_once "connect.php";

$id     = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id <= 0) {
    die("Invalid User ID");
}

/* ================= FETCH TARGET USER ================= */
$chk = $connect->prepare("SELECT role FROM users WHERE id = ?");
$chk->bind_param("i", $id);
$chk->execute();
$target = $chk->get_result()->fetch_assoc();

if (!$target) {
    die("User not found");
}

/* 🚫 NEVER MODIFY SUPER ADMIN */
if ($target['role'] === 'super_admin') {
    die("❌ Super Admin cannot be modified");
}

/* ================= ACTION HANDLER ================= */
switch ($action) {

    case 'make_admin':
        $stmt = $connect->prepare("
            UPDATE users SET role = 'admin' WHERE id = ?
        ");
        break;

    case 'make_user':
        $stmt = $connect->prepare("
            UPDATE users SET role = 'user' WHERE id = ?
        ");
        break;

    case 'block':
        $stmt = $connect->prepare("
            UPDATE users SET status = 'BLOCKED' WHERE id = ?
        ");
        break;

    case 'unblock':
        $stmt = $connect->prepare("
            UPDATE users SET status = 'ACTIVE' WHERE id = ?
        ");
        break;

    default:
        die("Invalid Action");
}

$stmt->bind_param("i", $id);
$stmt->execute();

/* ================= REDIRECT ================= */
header("Location: super_admin_dboard.php");
exit;
