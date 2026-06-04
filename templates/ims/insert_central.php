<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";
require_once "audit_log.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: add_central.php");
    exit;
}

/* ---------------- FORM DATA ---------------- */
$grant_type = trim($_POST['grant_type'] ?? '');
$grant_name = trim($_POST['grant_name'] ?? '');
$sub_grant  = trim($_POST['sub_grant'] ?? '');

/* NFS has NO sub grant */
if ($grant_type === 'NFS') {
    $sub_grant = '';
}

$name        = trim($_POST['equipment_name'] ?? '');
$lp_no       = trim($_POST['lp_no'] ?? '');
$cat_part_no = trim($_POST['cat_part_no'] ?? '');
$au          = trim($_POST['au'] ?? '');
$dte         = $_POST['received_date'] ?? '';
$qty         = (int)($_POST['qty_received'] ?? 0);
$cost        = (float)($_POST['cost'] ?? 0);

$user_id  = (int)($_SESSION['id'] ?? 0);
$username = $_SESSION['username'] ?? '';

/* ---------------- VALIDATION ---------------- */
if (
    $grant_type === '' ||
    $grant_name === '' ||
    $name === '' ||
    $au === '' ||
    $dte === '' ||
    $qty <= 0
) {
    die("❌ Invalid form data");
}

/* =====================================================
   🔴 NFS SPECIAL FLOW (FINAL – SCHEMA SAFE)
   ===================================================== */
if ($grant_type === 'NFS') {

    /* INSERT INTO NFS TABLE (ONLY EXISTING COLUMNS) */
    $stmt = $connect->prepare("
        INSERT INTO nfs_equipment
        (nfs_category, equipment_name, au, qty_received, received_date)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "sssis",
        $grant_name,
        $name,
        $au,
        $qty,
        $dte
    );
    $stmt->execute();
    $equipment_id = $stmt->insert_id;
    $stmt->close();

    /* AUDIT LOG */
    audit_log(
        $connect,
        $user_id,
        $username,
        'ADD',
        'NFS_EQUIPMENT',
        $equipment_id,
        "New NFS equipment added: {$name}, Qty {$qty}, Category {$grant_name}"
    );

    /* NOTIFICATION */
    $title   = "New NFS Equipment Added";
    $message = "New NFS equipment '{$name}' added under {$grant_name}";

    $stmt = $connect->prepare("
    INSERT INTO notifications 
    (title, message, type, module, reference_id, created_at, is_read)
    VALUES (?, ?, 'success', 'NFS_EQUIPMENT', ?, NOW(), 0)
");
$stmt->bind_param("ssi", $title, $message, $equipment_id);
$stmt->execute();
$stmt->close();

    echo "<script>
        alert('✅ NFS Equipment Added Successfully');
        window.location='add_central.php';
    </script>";
    exit;
}

/* =====================================================
   🟢 NON-NFS FLOW (UNCHANGED)
   ===================================================== */

/* 1️⃣ GET / INSERT GRANT */
$stmt = $connect->prepare("
    SELECT id FROM grants_master
    WHERE grant_type=? AND grant_name=? AND IFNULL(sub_grant,'')=? LIMIT 1
");
$stmt->bind_param("sss", $grant_type, $grant_name, $sub_grant);
$stmt->execute();
$stmt->bind_result($grant_id);
$stmt->fetch();
$stmt->close();

if (!$grant_id) {
    $stmt = $connect->prepare("
    INSERT INTO notifications 
    (title, message, type, module, reference_id, created_at, is_read)
    VALUES (?, ?, 'success', 'EQUIPMENT', ?, NOW(), 0)
");
$stmt->bind_param("ssi", $title, $message, $equipment_id);
$stmt->execute();
$stmt->close();
}

/* 2️⃣ CHECK EQUIPMENT */
$stmt = $connect->prepare("
    SELECT id
    FROM equipment_master
    WHERE grant_id=?
      AND equipment_name=?
      AND IFNULL(lp_no,'')=?
      AND IFNULL(cat_part_no,'')=?
      AND au=?
");
$stmt->bind_param("issss", $grant_id, $name, $lp_no, $cat_part_no, $au);
$stmt->execute();
$result = $stmt->get_result();

$is_new_equipment = false;

if ($row = $result->fetch_assoc()) {

    /* UPDATE EXISTING */
    $equipment_id = $row['id'];

    $stmt = $connect->prepare("
        UPDATE equipment_master
        SET qty_received = qty_received + ?,
            qty_available = qty_available + ?
        WHERE id=?
    ");
    $stmt->bind_param("iii", $qty, $qty, $equipment_id);
    $stmt->execute();
    $stmt->close();

} else {

    /* INSERT NEW */
    $stmt = $connect->prepare("
        INSERT INTO equipment_master
        (grant_id, equipment_name, lp_no, cat_part_no, au,
         qty_received, qty_available, cost, received_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "issssiiis",
        $grant_id,
        $name,
        $lp_no,
        $cat_part_no,
        $au,
        $qty,
        $qty,
        $cost,
        $dte
    );
    $stmt->execute();
    $equipment_id = $stmt->insert_id;
    $stmt->close();

    $is_new_equipment = true;
}
for ($i = 1; $i <= $qty; $i++) {

    $barcode = "EQP-" . $equipment_id . "-" . $i . "-" . time();

    $stmt2 = $connect->prepare("
        INSERT INTO equipment_items (equipment_id, barcode)
        VALUES (?, ?)
    ");
    $stmt2->bind_param("is", $equipment_id, $barcode);
    $stmt2->execute();
    $stmt2->close();
}
/* 3️⃣ TRANSACTION LOG */
$stmt = $connect->prepare("
    INSERT INTO equipment_txn (equipment_id, txn_type, qty, created_by)
    VALUES (?, 'ADD', ?, ?)
");
$stmt->bind_param("iii", $equipment_id, $qty, $user_id);
$stmt->execute();
$stmt->close();

/* 4️⃣ AUDIT LOG */
$action = $is_new_equipment ? 'ADD' : 'UPDATE';
$description = $is_new_equipment
    ? "New equipment added: {$name}, Qty {$qty}, Grant {$grant_type} / {$grant_name}"
    : "Stock updated: {$name}, Added Qty {$qty}, Grant {$grant_type} / {$grant_name}";

audit_log(
    $connect,
    $user_id,
    $username,
    $action,
    'EQUIPMENT',
    $equipment_id,
    $description
);

/* 5️⃣ NOTIFICATION */
$title   = $is_new_equipment ? "New Equipment Added" : "Stock Updated";
$message = $is_new_equipment
    ? "New equipment '{$name}' added under {$grant_type} / {$grant_name}"
    : "Stock updated for '{$name}' under {$grant_type} / {$grant_name}";

$stmt = $connect->prepare("
    INSERT INTO notifications (title, message, type)
    VALUES (?, ?, 'success')
");
$stmt->bind_param("ss", $title, $message);
$stmt->execute();
$stmt->close();

/* SUCCESS */
echo "<script>
alert('✅ Equipment Added Successfully');
window.location='add_central.php';
</script>";
