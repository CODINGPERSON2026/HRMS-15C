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

/* FETCH AVAILABLE EQUIPMENT */
$sql = "
SELECT 
    id,
    nfs_category,
    equipment_name,
    make,
    serial_no,
    qty_received
FROM nfs_equipment
ORDER BY equipment_name ASC
";
$result = $connect->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>Issue NFS Equipment</title>

<link rel="stylesheet" href="../../css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
body{
    background:#eef5ff;
    padding:20px;
    font-family:Segoe UI;
}

/* CARD */
.card{
    max-width:900px;
    margin:auto;
    background:#fff;
    border-radius:18px;
    box-shadow:0 8px 20px rgba(0,0,0,.15);
    padding:24px;
}

/* TITLE */
.page-title{
    font-size:24px;
    font-weight:800;
    color:#004d40;
    display:flex;
    align-items:center;
    gap:10px;
}

/* GRID */
.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
}
.full{grid-column:1/3}

/* INFO */
.info-box{
    background:#f9f9f9;
    border-radius:12px;
    padding:12px;
    font-size:14px;
}
</style>
</head>

<body>

<div class="card">

    <div class="page-title">
        <i class="fa fa-share-square"></i> ISSUE NFS EQUIPMENT
    </div>
    <hr>

    <form method="POST" action="insert_nfs_issue_equipment.php">

        <div class="grid">

            <!-- EQUIPMENT SELECT -->
            <div class="full">
                <label>Select Equipment</label>
                <select name="equipment_id" class="form-control" required>
                    <option value="">-- Select --</option>
                    <?php while($row = $result->fetch_assoc()){ ?>
                        <option value="<?= $row['id'] ?>">
                            <?= $row['equipment_name'] ?> |
                            <?= $row['serial_no'] ?> |
                            <?= $row['nfs_category'] ?> |
                            Available: <?= $row['qty_received'] ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <!-- ISSUE DETAILS -->
            <div>
                <label>Issue Quantity</label>
                <input type="number" name="issue_qty" min="1" class="form-control" required>
            </div>

            <div>
                <label>Issue Date</label>
                <input type="date" name="issue_date" class="form-control" required>
            </div>

            <div class="full">
                <label>Issued To (Unit / Section)</label>
                <input type="text" name="issued_to" class="form-control" required>
            </div>

            <div class="full">
                <label>Purpose / Remarks</label>
                <textarea name="remarks" class="form-control" rows="3"></textarea>
            </div>

        </div>

        <button class="btn btn-danger mt-4 w-100">
            <i class="fa fa-check"></i> ISSUE EQUIPMENT
        </button>

        <a href="nfs_dashboard.php" class="btn btn-secondary mt-3 w-100">
            ⬅ Back to NFS Dashboard
        </a>

    </form>

    <div class="info-box mt-3">
        ⚠️ <b>Note:</b> Issued quantity should not exceed available stock.
    </div>

</div>

</body>
</html>
