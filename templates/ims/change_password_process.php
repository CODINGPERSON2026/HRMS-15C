<?php
session_start();
require_once "connect.php";

if (empty($_SESSION['id'])) {
    header("location:logout.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("location:change_password.php");
    exit;
}

$user_id = (int)$_SESSION['id'];

$old     = trim($_POST['old_password'] ?? '');
$new     = trim($_POST['new_password'] ?? '');
$confirm = trim($_POST['confirm_password'] ?? '');

/* ================= VALIDATION ================= */

if ($old === '' || $new === '' || $confirm === '') {
    die("<script>alert('⚠ All fields are required');history.back();</script>");
}

if ($new !== $confirm) {
    die("<script>alert('❌ New password & confirm password do not match');history.back();</script>");
}

if (strlen($new) < 6) {
    die("<script>alert('⚠ Password must be at least 6 characters');history.back();</script>");
}

/* ================= FETCH CURRENT HASH ================= */

$stmt = $connect->prepare("SELECT password FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    die("<script>alert('❌ User not found');window.location='logout.php';</script>");
}

$stmt->bind_result($db_hash);
$stmt->fetch();
$stmt->close();

/* ================= VERIFY OLD PASSWORD ================= */

if (!password_verify($old, $db_hash)) {
    die("<script>alert('❌ Wrong current password');history.back();</script>");
}

/* ================= UPDATE PASSWORD ================= */

$new_hash = password_hash($new, PASSWORD_DEFAULT);

$stmt = $connect->prepare("UPDATE users SET password=? WHERE id=?");
$stmt->bind_param("si", $new_hash, $user_id);

if (!$stmt->execute()) {
    $stmt->close();
    die("<script>alert('❌ Password update failed');history.back();</script>");
}

$stmt->close();

/* ================= FORCE LOGOUT ================= */

echo "<script>
alert('✅ Password updated successfully. Please login again.');
window.location='logout.php';
</script>";
exit;
?>