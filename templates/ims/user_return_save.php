<?php
session_start();
require_once "auth.php";
require_user();
require_once 'connect.php';

if ($_SESSION['role'] !== 'user') exit;

$qty = (int)$_POST['qty'];
$equipment_id = (int)$_POST['equipment_id'];
$grant_id = (int)$_POST['grant_id'];
$user_id = $_SESSION['id'];
$unit = trim($_SESSION['unit']);

$stmt = $connect->prepare("
INSERT INTO return_requests 
(equipment_id, grant_id, unit_name, qty, requested_by)
VALUES (?,?,?,?,?)
");

$stmt->bind_param("iisii",
    $equipment_id,
    $grant_id,
    $unit,
    $qty,
    $user_id
);

$stmt->execute();

header("Location:user_return.php");
