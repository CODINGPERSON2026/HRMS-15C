<?php
session_start();

/* CACHE DISABLE */
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/* LOGIN CHECK */
if (!isset($_SESSION['id'])) {
    header("Location: edit/user_loginform.php");
    exit;
}

/* ROLE FUNCTIONS */
function require_admin() {
    if (!in_array($_SESSION['role'], ['admin', 'super_admin'])) {
        header("Location: edit/admin_loginform.php");
        exit;
    }
}

function require_super_admin() {
    if ($_SESSION['role'] !== 'super_admin') {
        header("Location: edit/super_admin_loginform.php");
        exit;
    }
}

function require_user() {
    if ($_SESSION['role'] !== 'user') {
        header("Location: edit/user_loginform.php");
        exit;
    }
}
?>