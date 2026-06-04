<?php
session_start();

/* 🔥 PATH FIX */
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

/* GET ID */
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid Equipment ID");
}

/* FETCH DATA */
$stmt = $connect->prepare(
    "SELECT * FROM nfs_equipment WHERE id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Equipment not found");
}

$data = $result->fetch_assoc();
$stmt->close();

/* NFS CATEGORIES */
$NFS_CATEGORIES = [
    "GOFNMS","IPMPLS","MW","SATL","DWDM","HC-MCEU","LC-MCEU"
];
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit NFS Equipment</title>
<link rel="stylesheet" href="../../css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:20px;
    font-family:Segoe UI;
}
.card{
    max-width:900px;
    margin:auto;
    padding:26px;
    border-radius:16px;
    background:#fff;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
}
.grid{
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:16px;
}
.full{grid-column:1/4}
</style>
</head>

<body>

<div class="card">
<h3 class="text-center">✏ Edit NFS Equipment</h3>
<hr>

<form method="POST" action="update_nfs_equipment.php">

<input type="hidden" name="id" value="<?= $data['id'] ?>">

<div class="grid">

<!-- CATEGORY -->
<div>
<label>NFS Category</label>
<select name="nfs_category" class="form-control" required>
<?php foreach($NFS_CATEGORIES as $cat){ 
    $sel = ($data['nfs_category'] === $cat) ? 'selected' : '';
?>
<option <?= $sel ?>><?= $cat ?></option>
<?php } ?>
</select>
</div>

<!-- EQUIPMENT -->
<div class="full">
<label>Equipment Name</label>
<input type="text" name="equipment_name"
       class="form-control"
       value="<?= htmlspecialchars($data['equipment_name']) ?>"
       required>
</div>

<div>
<label>OEM / Make</label>
<input type="text" name="make"
       class="form-control"
       value="<?= htmlspecialchars($data['make']) ?>">
</div>

<div>
<label>Serial No</label>
<input type="text" name="serial_no"
       class="form-control"
       value="<?= htmlspecialchars($data['serial_no']) ?>">
</div>

<div>
<label>A/U</label>
<select name="au" class="form-control">
<?php
$auList = ['Nos','Set','Pair','Mtrs'];
foreach($auList as $au){
    $sel = ($data['au']===$au)?'selected':'';
    echo "<option $sel>$au</option>";
}
?>
</select>
</div>

<div>
<label>Qty Received</label>
<input type="number" name="qty_received" min="1"
       class="form-control"
       value="<?= $data['qty_received'] ?>" required>
</div>

<div>
<label>Unit Cost</label>
<input type="number" step="0.01" name="unit_cost"
       class="form-control"
       value="<?= $data['unit_cost'] ?>">
</div>

<div>
<label>Total Cost</label>
<input type="number" step="0.01" name="total_cost"
       class="form-control"
       value="<?= $data['total_cost'] ?>">
</div>

<div>
<label>Date of Receipt</label>
<input type="date" name="received_date"
       class="form-control"
       value="<?= $data['received_date'] ?>" required>
</div>

<div class="full">
<label>Remarks</label>
<textarea name="remarks" class="form-control" rows="3"><?= htmlspecialchars($data['remarks']) ?></textarea>
</div>

</div>

<button class="btn btn-success mt-4 w-100">💾 Update Equipment</button>

<a href="nfs_new_arrival.php" class="btn btn-secondary mt-3 w-100">
⬅ Back to New Arrival
</a>

</form>
</div>

</body>
</html>
