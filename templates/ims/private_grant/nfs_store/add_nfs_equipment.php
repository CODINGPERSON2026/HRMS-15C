<?php
session_start();

/* 🔥 ABSOLUTE PATH FIX – NOTHING WILL BREAK */
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

/* NFS CATEGORIES */
$NFS_CATEGORIES = [
    "GOFNMS",
    "IPMPLS",
    "MW",
    "SATL",
    "DWDM",
    "HC-MCEU",
    "LC-MCEU"
];
?>
<!DOCTYPE html>
<html>
<head>
<title>Add NFS Equipment</title>

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
<h3 class="text-center">➕ Add NFS Equipment</h3>
<hr>

<form method="POST" action="insert_nfs_equipment.php">

<div class="grid">

<!-- CATEGORY -->
<div>
<label>NFS Category</label>
<select name="nfs_category" class="form-control" required>
<option value="">-- Select --</option>
<?php foreach($NFS_CATEGORIES as $cat){ ?>
<option value="<?= $cat ?>"><?= $cat ?></option>
<?php } ?>
</select>
</div>

<!-- EQUIPMENT -->
<div class="full">
<label>Equipment Name</label>
<input type="text" name="equipment_name" class="form-control" required>
</div>

<div>
<label>OEM / Make</label>
<input type="text" name="make" class="form-control">
</div>

<div>
<label>Serial No</label>
<input type="text" name="serial_no" class="form-control">
</div>

<div>
<label>A/U</label>
<select name="au" class="form-control">
<option>Nos</option>
<option>Set</option>
<option>Pair</option>
<option>Mtrs</option>
</select>
</div>

<div>
<label>Qty Received</label>
<input type="number" name="qty_received" min="1" class="form-control" required>
</div>

<div>
<label>Unit Cost</label>
<input type="number" step="0.01" name="unit_cost" class="form-control">
</div>

<div>
<label>Total Cost</label>
<input type="number" step="0.01" name="total_cost" class="form-control">
</div>

<div>
<label>Date of Receipt (DD-MM-YYYY)</label>
<input type="text" name="received_date" class="form-control"
       placeholder="dd-mm-yyyy"
       pattern="\d{2}-\d{2}-\d{4}" required>
</div>

<div class="full">
<label>Remarks</label>
<textarea name="remarks" class="form-control" rows="3"></textarea>
</div>

</div>

<button class="btn btn-success mt-4 w-100">✅ Add NFS Equipment</button>

<a href="nfs_dashboard.php" class="btn btn-secondary mt-3 w-100">
⬅ Back to NFS Dashboard
</a>

</form>
</div>

</body>
</html>
