<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "auth.php";
require_user();
require_once "connect.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header("location:logout.php");
    exit;
}

$equipment_id = (int)($_GET['equipment_id'] ?? 0);
$grant_id     = (int)($_GET['grant_id'] ?? 0);
$store        = trim($_SESSION['unit']);

if ($equipment_id <= 0 || $grant_id <= 0) {
    die("Invalid Request");
}

/* ================= FETCH EQUIPMENT ================= */
$stmt = $connect->prepare("
    SELECT 
        em.id,
        em.equipment_name,
        em.lp_no,
        em.cat_part_no,
        em.au,
        em.cost,
        gm.grant_name,
        gm.grant_type,

        MAX(CASE WHEN et.txn_type='ISSUE' THEN et.id END) AS issue_txn_id,

        (
            SUM(
                CASE 
                    WHEN et.txn_type='ISSUE' THEN et.qty
                    WHEN et.txn_type='RETURN' THEN -et.qty
                    ELSE 0
                END
            )
            -
            COALESCE(
                (
                    SELECT SUM(rr.return_qty)
                    FROM return_requests rr
                    WHERE rr.equipment_id = em.id
                    AND rr.status='PENDING'
                    AND TRIM(UPPER(rr.store_name)) = TRIM(UPPER(?))
                ),0
            )
        ) AS available_qty

    FROM equipment_master em
    JOIN grants_master gm ON gm.id = em.grant_id
    JOIN equipment_txn et ON et.equipment_id = em.id

    WHERE em.id=? 
      AND em.grant_id=?
      AND TRIM(UPPER(et.unit_name)) = TRIM(UPPER(?))

    GROUP BY em.id
    HAVING available_qty > 0
");

$stmt->bind_param("siis", $store, $equipment_id, $grant_id, $store);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    die("No available quantity for return");
}

/* ================= SUBMIT RETURN ================= */
if (isset($_POST['submit_return'])) {

    $return_qty = (int)$_POST['return_qty'];
    $reason     = trim($_POST['reason']);

    if ($return_qty <= 0 || $return_qty > $item['available_qty']) {
        die("Invalid Return Qty");
    }

    if (empty($item['issue_txn_id'])) {
        die("Issue transaction not found");
    }

    $q = $connect->prepare("
        INSERT INTO return_requests
        (issue_txn_id, equipment_id, grant_id, store_name, return_qty, reason, requested_by, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING')
    ");

    $q->bind_param(
        "iiisisi",
        $item['issue_txn_id'],
        $equipment_id,
        $grant_id,
        $store,
        $return_qty,
        $reason,
        $_SESSION['id']
    );

    if(!$q->execute()){
        die("Insert Error: " . $q->error);
    }

    /* ================= CREATE RETURN VOUCHER ================= */
    $rv_no = "RV-" . date("YmdHis");

    $vh = $connect->prepare("
        INSERT INTO return_vouchers
        (rv_no, rv_date, returned_from, created_by)
        VALUES (?, NOW(), ?, ?)
    ");

    $vh->bind_param("ssi", $rv_no, $store, $_SESSION['id']);
    $vh->execute();

    $return_voucher_id = $connect->insert_id;

    $vi = $connect->prepare("
        INSERT INTO return_vouchers_items
        (return_voucher_id, equipment_id, lp_no, cat_part_no, nomenclature, au, qty, remarks)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $remarks = $reason;

    $vi->bind_param(
        "iissssis",
        $return_voucher_id,
        $equipment_id,
        $item['lp_no'],
        $item['cat_part_no'],
        $item['equipment_name'],
        $item['au'],
        $return_qty,
        $remarks
    );

    $vi->execute();

    header("Location: return_voucher_view.php?id=".$return_voucher_id);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Return Voucher</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<style>
body{background:#eef5ff;padding:25px;font-family:Segoe UI}
.card{max-width:900px;margin:auto;background:#fff;padding:22px;border-radius:14px}
.info-box{background:#f9fbfc;border:1px solid #ddd;border-radius:12px;padding:16px;margin-bottom:20px}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px 30px}
.info-item span{font-weight:600}
</style>
</head>
<body>

<div class="card">
<h4 class="text-center">🔁 Return Voucher</h4>
<hr>

<div class="info-box">
<div class="info-grid">
    <div class="info-item"><span>Grant Type :</span> <?= htmlspecialchars($item['grant_type']) ?></div>
    <div class="info-item"><span>Grant Name :</span> <?= htmlspecialchars($item['grant_name']) ?></div>
    <div class="info-item"><span>Equipment :</span> <?= htmlspecialchars($item['equipment_name']) ?></div>
    <div class="info-item"><span>LP No :</span> <?= htmlspecialchars($item['lp_no']) ?></div>
    <div class="info-item"><span>Available Qty :</span> <?= $item['available_qty']." ".$item['au'] ?></div>
</div>
</div>

<form method="POST">

<label>Return Qty</label>
<input type="number" name="return_qty" min="1" max="<?= $item['available_qty'] ?>" class="form-control" required>

<label class="mt-3">Reason for Return</label>
<textarea name="reason" class="form-control" rows="3" required></textarea>

<div class="d-flex gap-2 mt-3">
<button name="submit_return" class="btn btn-danger">🔁 Generate Return Voucher</button>
<a href="user_grant.php?grant_id=<?= $grant_id ?>" class="btn btn-secondary">⬅ Back</a>
</div>

</form>
</div>

</body>
</html>