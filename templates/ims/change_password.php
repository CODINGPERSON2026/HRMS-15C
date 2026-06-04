<?php
session_start();
require_once "connect.php";

if (empty($_SESSION['id'])) {
    header("location:logout.php");
    exit;
}

$role = $_SESSION['role'] ?? '';
$dashboard = ($role === 'admin' || $role === 'super_admin')
    ? 'dboard.php'
    : 'user_dboard.php';
?>

<!DOCTYPE html>
<html>
<head>
<title>Change Password</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<style>
body{
    background:linear-gradient(135deg,#e3f2fd,#c8e6c9);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family:Segoe UI;
}
.card{
    width:400px;
    border-radius:15px;
    box-shadow:0 8px 20px rgba(0,0,0,.25);
}
</style>
</head>

<body>

<div class="card p-4">
<h4 class="text-center mb-3">🔐 CHANGE PASSWORD</h4>

<form method="POST" action="change_password_process.php">

<input type="password"
       name="old_password"
       class="form-control mb-2"
       placeholder="Current Password"
       required>

<input type="password"
       name="new_password"
       class="form-control mb-2"
       placeholder="New Password"
       required>

<input type="password"
       name="confirm_password"
       class="form-control mb-3"
       placeholder="Confirm New Password"
       required>

<button type="submit" class="btn btn-primary w-100 mb-2">
Update Password
</button>

<a href="<?= $dashboard ?>" class="btn btn-secondary w-100">
⬅ Back
</a>

</form>
</div>

</body>
</html>