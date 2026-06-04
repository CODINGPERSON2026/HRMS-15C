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

/* ================= FETCH ISSUED EQUIPMENTS WITH BALANCE ================= */
$sql = "
SELECT 
    ie.id AS issue_id,
    ne.nfs_category,
    ne.equipment_name,
    ne.serial_no,
    ie.issue_qty,
    ie.issued_to,

    IFNULL(SUM(
        CASE 
            WHEN re.status='APPROVED' THEN re.return_qty
            ELSE 0
        END
    ),0) AS returned_qty,

    (ie.issue_qty -
        IFNULL(SUM(
            CASE 
                WHEN re.status='APPROVED' THEN re.return_qty
                ELSE 0
            END
        ),0)
    ) AS balance_qty

FROM nfs_issue_equipment ie
JOIN nfs_equipment ne ON ne.id = ie.equipment_id
LEFT JOIN nfs_return_equipment re ON re.issue_id = ie.id

GROUP BY ie.id
HAVING balance_qty > 0
ORDER BY ie.issue_date DESC
";

$result = $connect->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>Return NFS Equipment</title>

<link rel="stylesheet" href="../../css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
body{
    background:#eef5ff;
    padding:20px;
    font-family:Segoe UI;
}
.card{
    max-width:900px;
    margin:auto;
    background:#fff;
    border-radius:18px;
    box-shadow:0 8px 20px rgba(0,0,0,.15);
    padding:24px;
}
.page-title{
    font-size:24px;
    font-weight:800;
    color:#004d40;
    display:flex;
    align-items:center;
    gap:10px;
}
.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
}
.full{grid-column:1/3}
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
        <i class="fa fa-undo"></i> RETURN NFS EQUIPMENT
    </div>
    <hr>

    <form method="POST" action="insert_nfs_return_equipment.php">

        <div class="grid">

            <!-- ISSUED EQUIPMENT -->
            <div class="full">
                <label>Select Issued Equipment</label>
                <select name="issue_id" id="issueSelect" class="form-control" required>
                    <option value="">-- Select --</option>
                    <?php while($row = $result->fetch_assoc()){ ?>
                        <option 
                            value="<?= $row['issue_id'] ?>"
                            data-max="<?= $row['balance_qty'] ?>"
                        >
                            <?= $row['equipment_name'] ?> |
                            <?= $row['serial_no'] ?> |
                            Issued: <?= $row['issue_qty'] ?> |
                            Returned: <?= $row['returned_qty'] ?> |
                            Balance: <?= $row['balance_qty'] ?> |
                            To: <?= $row['issued_to'] ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <!-- RETURN DETAILS -->
            <div>
                <label>Return Quantity</label>
                <input type="number"
                       name="return_qty"
                       id="returnQty"
                       min="1"
                       class="form-control"
                       required>
            </div>

            <div>
                <label>Return Date</label>
                <input type="date" name="return_date" class="form-control" required>
            </div>

            <div class="full">
                <label>Returned From (Unit / Section)</label>
                <input type="text" name="returned_from" class="form-control" required>
            </div>

            <div class="full">
                <label>Remarks</label>
                <textarea name="remarks" class="form-control" rows="3"></textarea>
            </div>

        </div>

        <button class="btn btn-success mt-4 w-100">
            <i class="fa fa-check"></i> RETURN EQUIPMENT
        </button>

        <a href="nfs_dashboard.php" class="btn btn-secondary mt-3 w-100">
            ⬅ Back to NFS Dashboard
        </a>

    </form>

    <div class="info-box mt-3">
        ⚠️ <b>Note:</b> Return quantity cannot exceed available balance quantity.
    </div>

</div>

<script>
document.getElementById('issueSelect').addEventListener('change', function(){
    const maxQty = this.options[this.selectedIndex].getAttribute('data-max');
    const qtyInput = document.getElementById('returnQty');

    if(maxQty){
        qtyInput.max = maxQty;
        qtyInput.value = '';
    }
});
</script>

</body>
</html>
