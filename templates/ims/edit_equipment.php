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
    die("Invalid Equipment ID");
}

/* FETCH EQUIPMENT */
$stmt = $connect->prepare("
SELECT 
    em.*,
    gm.grant_type,
    gm.grant_name,
    gm.sub_grant
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

/* UPDATE FORM */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $equipment_name = trim($_POST['equipment_name']);
    $lp_no          = trim($_POST['lp_no']);
    $cat_part_no    = trim($_POST['cat_part_no'] ?? '');
    $au             = trim($_POST['au']);
    $qty_received   = (int)$_POST['qty_received'];
    $cost           = (float)($_POST['cost'] ?? 0);
    $received_date  = $_POST['received_date'];

    if ($equipment_name === '' || $au === '' || $received_date === '' || $qty_received <= 0) {
        die("❌ Invalid form data");
    }

    /* ISSUED SAFETY */
    $issued_qty = $data['qty_received'] - $data['qty_available'];
    if ($qty_received < $issued_qty) {
        die("❌ Qty Received cannot be less than already issued quantity");
    }

    $new_available = $qty_received - $issued_qty;

    $stmt = $connect->prepare("
        UPDATE equipment_master
        SET equipment_name=?,
            lp_no=?,
            cat_part_no=?,
            au=?,
            qty_received=?,
            qty_available=?,
            cost=?,
            received_date=?
        WHERE id=?
    ");
    $stmt->bind_param(
        "ssssiiisi",
        $equipment_name,
        $lp_no,
        $cat_part_no,
        $au,
        $qty_received,
        $new_available,
        $cost,
        $received_date,
        $id
    );
    $stmt->execute();
    $stmt->close();

    echo "<script>
        alert('✅ Equipment Updated Successfully');
        window.location='central_view.php';
    </script>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit Equipment</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:20px;
    font-family:Segoe UI;
}
.card{
    max-width:800px;
    margin:auto;
    background:#fff;
    padding:24px;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
}
label{font-weight:600;}
</style>
</head>

<body>

<div class="card">
<h3 class="text-center mb-3">✏ Edit Equipment</h3>
<hr>

<form method="POST">

<div class="row mb-3">
    <div class="col-md-4">
        <label>Grant Type</label>
        <input id="grantType" class="form-control" value="<?= $data['grant_type'] ?>" readonly>
    </div>
    <div class="col-md-4">
        <label>Grant</label>
        <input class="form-control" value="<?= $data['grant_name'] ?>" readonly>
    </div>
    <div class="col-md-4">
        <label>Sub Grant</label>
        <input class="form-control" value="<?= $data['sub_grant'] ?>" readonly>
    </div>
</div>

<label>Equipment Name</label>
<input name="equipment_name" class="form-control mb-2"
       value="<?= htmlspecialchars($data['equipment_name']) ?>" required>

<div class="row mb-2">
    <div class="col-md-6">
        <label>LP No</label>
        <input name="lp_no" class="form-control"
               value="<?= htmlspecialchars($data['lp_no']) ?>">
    </div>

    <div class="col-md-6" id="catPartDiv">
        <label>Cat / Part No</label>
        <input name="cat_part_no" class="form-control"
               value="<?= htmlspecialchars($data['cat_part_no']) ?>">
    </div>
</div>

<div class="row mb-2">
    <div class="col-md-4">
        <label>A/U</label>
        <select name="au" class="form-control">
            <?php foreach(["Nos","Set","Pair","Mtrs"] as $u){
                $sel = $data['au']==$u?'selected':'';
                echo "<option $sel>$u</option>";
            }?>
        </select>
    </div>

    <div class="col-md-4">
        <label>Qty Received</label>
        <input type="number" name="qty_received" class="form-control"
               min="<?= ($data['qty_received']-$data['qty_available']) ?>"
               value="<?= $data['qty_received'] ?>" required>
    </div>

    <div class="col-md-4" id="costDiv">
        <label>Cost</label>
        <input type="number" step="0.01" name="cost"
               class="form-control"
               value="<?= $data['cost'] ?>">
    </div>
</div>

<label>Date Received</label>
<input type="date" name="received_date" class="form-control mb-3"
       value="<?= $data['received_date'] ?>" required>

<button class="btn btn-success w-100 mt-3">💾 Update Equipment</button>
<a href="central_view.php" class="btn btn-secondary w-100 mt-2">⬅ Back</a>

</form>
</div>

<script>
const grantType = document.getElementById("grantType").value;
const costDiv   = document.getElementById("costDiv");
const catDiv    = document.getElementById("catPartDiv");

/* SAME LOGIC AS add_central.php */
if (grantType === "TECH/ORD/ACSFP") {
    costDiv.style.display = "none";
} else {
    catDiv.style.display = "none";
}
</script>

</body>
</html>
