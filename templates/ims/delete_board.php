<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* 🔐 VALIDATE ID */
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error'] = "❌ Invalid Board ID";
    header("Location: board_list.php");
    exit;
}

/* 🔍 CHECK IF BOARD EXISTS */
$check = $connect->prepare("SELECT id FROM ord_board WHERE id=?");
$check->bind_param("i", $id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows === 0) {
    $_SESSION['error'] = "❌ Board not found";
    header("Location: board_list.php");
    exit;
}

/* 🗑️ DELETE BOARD */
$del = $connect->prepare("DELETE FROM ord_board WHERE id=?");
$del->bind_param("i", $id);

if ($del->execute()) {
    $_SESSION['success'] = "🗑️ Board deleted successfully";
} else {
    $_SESSION['error'] = "❌ Delete failed : " . $del->error;
}

/* 🔁 REDIRECT */
header("Location: board_list.php");
exit;
