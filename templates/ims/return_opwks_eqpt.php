<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* ================= GET EQUIPMENT ID ================= */
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid Equipment ID");
}

/* ================= FETCH EQUIPMENT ================= */
$stmt = $connect->prepare("
    SELECT 
        em.id,
        em.equipment_name,
        em.au,
        IFNULL(SUM(
            CASE 
                WHEN tx.txn_type='ISSUE'  THEN tx.qty
                WHEN tx.txn_type='RETURN' THEN -tx.qty
                ELSE 0
            END
        ),0) AS net_issued
    FROM opwks_equipment_master em
    LEFT JOIN opwks_equipment_txn tx 
        ON tx.equipment_id = em.id
    WHERE em.id = ?
    GROUP BY em.id
");
$stmt->bind_param("i", $id);
$stmt->execute();
$eq = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$eq) die("Equipment Not Found");

/* AVAILABLE QTY */
$available_for_return = max(0, (int)$eq['net_issued']);

/* ================= SAVE RETURN + VOUCHER ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $qty  = (int)$_POST['return_qty'];
    $unit = trim($_POST['unit_name']);
    $user = $_SESSION['id'];

    if ($qty <= 0 || $qty > $available_for_return) {
        die("Invalid Return Quantity");
    }
    if ($unit === '') {
        die("Returned From is required");
    }

    /* 1️⃣ RETURN TXN */
    $stmt = $connect->prepare("
        INSERT INTO opwks_equipment_txn
        (equipment_id, txn_type, qty, unit_name, created_by)
        VALUES (?,?,?,?,?)
    ");
    $type = 'RETURN';
    $stmt->bind_param("isisi", $id, $type, $qty, $unit, $user);
    $stmt->execute();
    $stmt->close();

    /* 2️⃣ VOUCHER HEADER */
    $year = date('Y');
    $connect->query("
        INSERT INTO opwks_return_voucher
        (voucher_no, equipment_id, returned_from, total_qty, created_by)
        VALUES ('', $id, '$unit', $qty, $user)
    ");

    $voucher_id = $connect->insert_id;
    $voucher_no = "OPWKS-RET-$year-" . str_pad($voucher_id, 4, '0', STR_PAD_LEFT);

    $connect->query("
        UPDATE opwks_return_voucher
        SET voucher_no='$voucher_no'
        WHERE id=$voucher_id
    ");

    /* 3️⃣ VOUCHER ITEM */
    $stmt = $connect->prepare("
        INSERT INTO opwks_return_voucher_items
        (voucher_id, equipment_id, qty, unit_name)
        VALUES (?,?,?,?)
    ");
    $stmt->bind_param("iiis", $voucher_id, $id, $qty, $unit);
    $stmt->execute();
    $stmt->close();

    header("Location: opwks_equipment_table_view.php?return_voucher=$voucher_no");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Return OPWKS Equipment</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
</head>

<body class="bg-light">

<div class="container mt-4">
<div class="card shadow">

<div class="card-header bg-success text-white">
    <h5 class="mb-0">↩ RETURN OPWKS EQUIPMENT</h5>
</div>

<div class="card-body">

<p><b>Equipment :</b> <?= htmlspecialchars($eq['equipment_name']) ?></p>
<p><b>Available for Return :</b> <?= $available_for_return ?> <?= $eq['au'] ?></p>

<form method="POST" onsubmit="return validateForm();">

<label>Return Type</label>
<select id="issueType" name="return_type" class="form-control" required>
    <option value="">-- Select --</option>
    <option value="INTERNAL">Internal</option>
    <option value="EXTERNAL">External</option>
</select>

<div id="internalBox" class="mt-2" style="display:none">
<label>Returned From (Store)</label>
<select id="internalUnit" class="form-control">
<option value="">SELECT STORE</option>
<option>HQ CQ STORE</option>
<option>1 CQ STORE</option>
<option>2 CQ STORE</option>
<option>ICN STORE</option>
<option>MCCS STORE</option>
<option>3 CQ STORE</option>
<option>3 RR STORE</option>
<option>RP STORE</option>
<option>LINE STORE</option>
<option>EFS STORE</option>
<option>OPSHIVA STORE</option>
<option>MT STORE</option>
<option>LRW STORE</option>
<option>TM STORE</option>
<option>RHQ STORE</option>
<option>IT STORE</option>
<option>STRONG ROOM</option>
<option>CSO RES</option>
<option>NFS STORE</option>
</select>
</div>

<div id="externalBox" class="mt-2" style="display:none">
<label>Returned From (External)</label>
<input type="text" id="externalUnit" class="form-control">
</div>

<input type="hidden" name="unit_name" id="finalUnit">

<label class="mt-3">Return Quantity</label>
<input type="number"
       name="return_qty"
       min="1"
       max="<?= $available_for_return ?>"
       class="form-control"
       required>

<div class="mt-3 d-flex gap-2">
<button class="btn btn-success">↩ Return & Generate Voucher</button>
<a href="opwks_equipment_table_view.php" class="btn btn-secondary">⬅ Back</a>
</div>

</form>

</div>
</div>
</div>

<script>
const issueType   = document.getElementById('issueType');
const internalBox = document.getElementById('internalBox');
const externalBox = document.getElementById('externalBox');
const internalSel = document.getElementById('internalUnit');
const externalInp = document.getElementById('externalUnit');
const finalUnit   = document.getElementById('finalUnit');

issueType.addEventListener('change', ()=>{
    internalBox.style.display = 'none';
    externalBox.style.display = 'none';
    finalUnit.value = '';

    if(issueType.value === 'INTERNAL') internalBox.style.display = 'block';
    if(issueType.value === 'EXTERNAL') externalBox.style.display = 'block';
});

internalSel.addEventListener('change', ()=> finalUnit.value = internalSel.value);
externalInp.addEventListener('input', ()=> finalUnit.value = externalInp.value);

function validateForm(){
    if(finalUnit.value.trim() === ''){
        alert('Please select / enter Returned From');
        return false;
    }
    return true;
}
</script>

</body>
</html>
