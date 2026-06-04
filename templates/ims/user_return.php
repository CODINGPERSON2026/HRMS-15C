<?php
session_start();
require_once "auth.php";
require_user();
require_once 'connect.php';
require_once 'audit_log.php'; // ✅ AUDIT LOG INCLUDE

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header("location:logout.php");
    exit;
}

$store        = trim($_SESSION['unit']);
$equipment_id = (int)($_GET['equipment_id'] ?? 0);
$grant_id     = (int)($_GET['grant_id'] ?? 0);

if ($equipment_id <= 0 || $grant_id <= 0) {
    die("Invalid Request");
}

/* ================= FETCH NET ISSUED QTY ================= */
$sql = "
SELECT
    em.id,
    MAX(et.id) AS issue_txn_id,
    em.equipment_name,
    em.lp_no,
    em.au,

    SUM(
        CASE 
            WHEN et.txn_type = 'ISSUE'  THEN et.qty
            WHEN et.txn_type = 'RETURN' THEN -et.qty
        END
    ) AS net_qty

FROM equipment_txn et
JOIN equipment_master em ON em.id = et.equipment_id

WHERE et.equipment_id = ?
  AND em.grant_id = ?
  AND TRIM(et.unit_name) = ?

GROUP BY em.id, em.equipment_name, em.lp_no, em.au
HAVING net_qty > 0
";

$stmt = $connect->prepare($sql);
$stmt->bind_param("iis", $equipment_id, $grant_id, $store);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    die("❌ No issued quantity available for return");
}

/* ================= SUBMIT RETURN REQUEST ================= */
if (isset($_POST['submit_return'])) {

    $qty = (int)$_POST['return_qty'];

    if ($qty <= 0 || $qty > $item['net_qty']) {
        die("❌ Invalid return quantity");
    }

    /* 1️⃣ INSERT RETURN REQUEST */
    $q = $connect->prepare("
    INSERT INTO return_requests
    (issue_txn_id, equipment_id, grant_id, store_name, return_qty, requested_by, status)
    VALUES (?,?,?,?,?,?, 'PENDING')
    ");

    $q->bind_param(
        "iiisii",
        $item['issue_txn_id'],
        $equipment_id,
        $grant_id,
        $store,
        $qty,
        $_SESSION['id']
    );
$q->execute();

    $request_id = $connect->insert_id;

    /* 2️⃣ AUDIT LOG ✅ */
    audit_log(
        $connect,
        $_SESSION['id'],
        $_SESSION['username'],
        'REQUEST',
        'RETURN_REQUEST',
        $request_id,
        "Return requested by {$store} for {$item['equipment_name']} (Qty {$qty})"
    );

    /* 3️⃣ ADMIN NOTIFICATION 🔔 */
    $title   = "New Return Request";
    $message = "{$store} requested return of {$item['equipment_name']} (Qty {$qty})";

    $n = $connect->prepare("
        INSERT INTO notifications (title, message, type)
        VALUES (?, ?, 'warning')
    ");
    $n->bind_param("ss", $title, $message);
    $n->execute();

    echo "<script>
        alert('✅ Return request sent to Admin for approval');
        window.location='user_dboard.php';
    </script>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Return Equipment</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<style>
body{background:#eef5ff;padding:30px;font-family:Segoe UI}
.card{
    max-width:500px;
    margin:auto;
    background:#fff;
    padding:20px;
    border-radius:15px;
    box-shadow:0 6px 15px rgba(0,0,0,.15);
}
</style>
</head>

<body>

<div class="card">
<h5 class="text-center mb-3">🔁 Return Equipment</h5>

<table class="table table-bordered table-sm">
<tr><th>Equipment</th><td><?= htmlspecialchars($item['equipment_name']) ?></td></tr>
<tr><th>LP No</th><td><?= htmlspecialchars($item['lp_no']) ?></td></tr>
<tr><th>Available Qty</th><td><?= $item['net_qty']." ".$item['au'] ?></td></tr>
</table>

<form method="post">
<label>Return Quantity</label>
<input type="number"
       name="return_qty"
       min="1"
       max="<?= $item['net_qty'] ?>"
       class="form-control"
       required>

<button type="submit"
        name="submit_return"
        class="btn btn-danger w-100 mt-3">
🔁 Send Return Request
</button>

<a href="user_dboard.php" class="btn btn-secondary w-100 mt-2">⬅ Back</a>
</form>

</div>

</body>
</html>
