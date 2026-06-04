<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid access");
}

/* ===== FORM ARRAYS ===== */
$job_no   = $_POST['job_no'] ?? [];
$name     = $_POST['equipment_name'] ?? [];
$au       = $_POST['au'] ?? [];
$qty      = $_POST['qty'] ?? [];
$cost     = $_POST['cost'] ?? [];

$rowCount = count($name);

if ($rowCount === 0) {
    die("No data received");
}

/* ===== PREPARED STATEMENT ===== */
$stmt = $connect->prepare("
    INSERT INTO opwks_equipment_master
    (
        job_no,
        equipment_name,
        au,
        qty_received,
        cost_each,
        total_amount,
        created_by
    )
    VALUES (?,?,?,?,?,?,?)
");

/* ===== INSERT LOOP ===== */
for ($i = 0; $i < $rowCount; $i++) {

    if (empty($name[$i]) || empty($qty[$i])) {
        continue;
    }

    $job   = $job_no[$i] ?? '';
    $eq    = $name[$i];
    $unit  = $au[$i] ?? 'Nos';
    $q     = (int)$qty[$i];
    $c     = (float)($cost[$i] ?? 0);
    $total = $q * $c;
    $user  = $_SESSION['id'];

    $stmt->bind_param(
        "sssiddi",
        $job,
        $eq,
        $unit,
        $q,
        $c,
        $total,
        $user
    );

    $stmt->execute();
}

$stmt->close();

/* ===== REDIRECT ===== */
header("Location: opwks_equipment_table_view.php?success=1");
exit;
