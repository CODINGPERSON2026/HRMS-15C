<?php
session_start();
require_once "auth.php";
require_admin();

require_once "connect.php";

if (!isset($_SESSION['id'])) {
    header("location:logout.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid Request");
}

/* FETCH EQUIPMENT */
$stmt = $connect->prepare("
SELECT 
    em.id,
    em.equipment_name,
    em.qty_received,
    em.qty_available,
    gm.grant_name
FROM equipment_master em
JOIN grants_master gm ON em.grant_id = gm.id
WHERE em.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Equipment not found");
}

/* SAFETY CHECK */
$issued_qty = $data['qty_received'] - $data['qty_available'];

if ($issued_qty > 0) {
    echo "<script>
        alert('❌ Cannot delete! Equipment already issued. Please return first.');
        window.location='central_view.php';
    </script>";
    exit;
}

/* DELETE CONFIRMED */
if (isset($_POST['confirm']) && $_POST['confirm'] === 'YES') {

    $equipment_name = $data['equipment_name'];

    /* DELETE TRANSACTIONS */
    $stmt = $connect->prepare(
        "DELETE FROM equipment_txn WHERE equipment_id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    /* DELETE EQUIPMENT */
    $stmt = $connect->prepare(
        "DELETE FROM equipment_master WHERE id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    /* REMOVE OLD ADD NOTIFICATION */
    $connect->query("
        DELETE FROM notifications 
        WHERE module='EQUIPMENT' 
        AND reference_id=$id
    ");

    /* INSERT DELETE NOTIFICATION */
    $title   = "Equipment Removed";
    $message = "Equipment '{$equipment_name}' deleted successfully";

    $stmt = $connect->prepare("
        INSERT INTO notifications
        (title, message, type, module, reference_id, created_at, is_read)
        VALUES (?, ?, 'danger', 'EQUIPMENT', ?, NOW(), 0)
    ");
    $stmt->bind_param("ssi", $title, $message, $id);
    $stmt->execute();
    $stmt->close();

    echo "<script>
        alert('🗑 Equipment deleted successfully');
        window.location='central_view.php';
    </script>";
    exit;
}
    
?>
<!DOCTYPE html>
<html>
<head>
<title>Delete Equipment</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#ffecec;
    padding:30px;
    font-family:Segoe UI;
}
.card{
    max-width:520px;
    margin:auto;
    background:#fff;
    padding:25px;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,.2);
}
h4{
    color:#c62828;
    font-weight:700;
}
.info{
    background:#f5f5f5;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
}
</style>
</head>

<body>

<div class="card text-center">
<h4>⚠ Confirm Delete</h4>
<hr>

<div class="info text-left">
<b>Grant :</b> <?= htmlspecialchars($data['grant_name']) ?><br>
<b>Equipment :</b> <?= htmlspecialchars($data['equipment_name']) ?><br>
<b>Qty Received :</b> <?= $data['qty_received'] ?><br>
<b>Qty Available :</b> <?= $data['qty_available'] ?>
</div>

<p class="text-danger">
This equipment will be permanently deleted.<br>
Are you sure?
</p>

<form method="POST">
    <button name="confirm" value="YES" class="btn btn-danger w-100 mb-2">
        🗑 Yes, Delete
    </button>
    <a href="central_view.php" class="btn btn-secondary w-100">
        ❌ Cancel
    </a>
</form>

</div>

</body>
</html>
