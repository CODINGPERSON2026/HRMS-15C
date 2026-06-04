<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

if (!isset($_SESSION['id'])) {
    die("Unauthorized Access");
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("Invalid Issue ID");
}

/* ================= FETCH ISSUE ================= */
$q = mysqli_query($connect,"
    SELECT et.*, em.equipment_name, em.lp_no
    FROM equipment_txn et
    JOIN equipment_master em ON em.id = et.equipment_id
    WHERE et.id = $id
      AND et.txn_type = 'ISSUE'
");

if (mysqli_num_rows($q) == 0) {
    die("Issue record not found");
}

$old = mysqli_fetch_assoc($q);

/* ================= UPDATE ================= */
if (isset($_POST['save'])) {

    $new_unit = mysqli_real_escape_string($connect, $_POST['unit']);
    $new_qty  = (int)$_POST['qty'];
    $new_date = $_POST['date'];

    if ($new_qty <= 0) {
        die("Invalid Quantity");
    }

    /* Reverse Old Stock */
    mysqli_query($connect,"
        UPDATE equipment_master
        SET qty_available = qty_available + {$old['qty']}
        WHERE id = {$old['equipment_id']}
    ");

    /* Apply New Stock */
    mysqli_query($connect,"
        UPDATE equipment_master
        SET qty_available = qty_available - $new_qty
        WHERE id = {$old['equipment_id']}
    ");

    /* Update Transaction */
    mysqli_query($connect,"
        UPDATE equipment_txn
        SET qty = $new_qty,
            unit_name = '$new_unit',
            created_at = '$new_date'
        WHERE id = $id
    ");

    header("location:issue_list.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Issue</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#dfe6ee;
    font-family:Segoe UI;
}

.card-box{
    max-width:750px;
    margin:60px auto;
    background:#e9ecef;
    padding:30px;
    border-radius:15px;
    box-shadow:0 4px 12px rgba(0,0,0,.2);
}

.card-box h4{
    text-align:center;
    margin-bottom:25px;
}

.form-control{
    background:#fff;
}

.btn-update{
    background:#198754;
    color:#fff;
    font-weight:600;
}

.btn-back{
    background:#6c757d;
    color:#fff;
}
</style>
</head>

<body>

<div class="card-box">

<h4>✏ EDIT ISSUE</h4>
<hr>

<div class="row mb-3">
    <div class="col-md-6">
        <label>EQUIPMENT NAME</label>
        <input type="text" class="form-control"
        value="<?= htmlspecialchars($old['equipment_name']) ?>" readonly>
    </div>

    <div class="col-md-6">
        <label>LP No</label>
        <input type="text" class="form-control"
        value="<?= htmlspecialchars($old['lp_no']) ?>" readonly>
    </div>
</div>

<form method="POST">

<div class="row mb-3">
    <div class="col-md-6">
        <label>STORE / UNIT</label>
        <input type="text" name="unit"
        class="form-control"
        value="<?= htmlspecialchars($old['unit_name']) ?>"
        required>
    </div>

    <div class="col-md-6">
        <label>QUANTITY</label>
        <input type="number" name="qty"
        class="form-control"
        value="<?= $old['qty'] ?>"
        required>
    </div>
</div>

<div class="mb-3">
    <label>DATE</label>
    <input type="date" name="date"
    class="form-control"
    value="<?= date('Y-m-d', strtotime($old['created_at'])) ?>"
    required>
</div>

<button type="submit" name="save" class="btn btn-update w-100">
💾 UPDATE ISSUE
</button>

<a href="issue_list.php" class="btn btn-back w-100 mt-2">
⬅ BACK
</a>

</form>
</div>

</body>
</html>
