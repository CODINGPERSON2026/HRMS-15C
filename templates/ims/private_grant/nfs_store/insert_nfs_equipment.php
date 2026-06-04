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

/* GET & SANITIZE INPUTS */
$nfs_category   = trim($_POST['nfs_category'] ?? '');
$equipment_name = trim($_POST['equipment_name'] ?? '');
$make           = trim($_POST['make'] ?? '');
$serial_no      = trim($_POST['serial_no'] ?? '');
$au             = trim($_POST['au'] ?? '');
$qty_received   = intval($_POST['qty_received'] ?? 0);
$unit_cost      = floatval($_POST['unit_cost'] ?? 0);
$total_cost     = floatval($_POST['total_cost'] ?? 0);
$received_date  = trim($_POST['received_date'] ?? '');
$remarks        = trim($_POST['remarks'] ?? '');

/* BASIC VALIDATION */
if (
    $nfs_category === '' ||
    $equipment_name === '' ||
    $qty_received <= 0 ||
    $received_date === ''
) {
    die("Required fields missing");
}

/* DATE FORMAT FIX (DD-MM-YYYY → YYYY-MM-DD) */
$dateObj = DateTime::createFromFormat('d-m-Y', $received_date);
if (!$dateObj) {
    die("Invalid Date Format");
}
$received_date_db = $dateObj->format('Y-m-d');

/* AUTO TOTAL COST (IF NOT ENTERED) */
if ($total_cost <= 0 && $unit_cost > 0) {
    $total_cost = $unit_cost * $qty_received;
}

/* INSERT QUERY */
$sql = "INSERT INTO nfs_equipment
(
    nfs_category,
    equipment_name,
    make,
    serial_no,
    au,
    qty_received,
    unit_cost,
    total_cost,
    received_date,
    remarks,
    created_at
)
VALUES (?,?,?,?,?,?,?,?,?,?,NOW())";

$stmt = $connect->prepare($sql);
if (!$stmt) {
    die("Prepare Failed : " . $connect->error);
}

$stmt->bind_param(
    "sssssiddds",
    $nfs_category,
    $equipment_name,
    $make,
    $serial_no,
    $au,
    $qty_received,
    $unit_cost,
    $total_cost,
    $received_date_db,
    $remarks
);

if ($stmt->execute()) {
    header("Location: add_nfs_equipment.php?success=1");
    exit;
} else {
    die("Insert Failed : " . $stmt->error);
}

$stmt->close();
$connect->close();
