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

/* ================= FETCH EQUIPMENT + AVAILABLE QTY ================= */
$stmt = $connect->prepare("
    SELECT 
        em.id,
        em.job_no,
        em.equipment_name,
        em.au,
        em.qty_received,
        (
            em.qty_received -
            IFNULL(SUM(
                CASE 
                    WHEN tx.txn_type='ISSUE'  THEN tx.qty
                    WHEN tx.txn_type='RETURN' THEN -tx.qty
                    ELSE 0
                END
            ),0)
        ) AS qty_available
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

if (!$eq) {
    die("Equipment Not Found");
}
if ($eq['qty_available'] <= 0) {
    die("No stock available for issue");
}

/* ================= HANDLE ISSUE + VOUCHER ================= */
if (isset($_POST['issue_submit'])) {

    $unit = trim($_POST['unit_name']);
    $qty  = (int)$_POST['qty'];
    $user = $_SESSION['id'];

    if ($unit === '') {
        die("Issued To is required");
    }
    if ($qty <= 0 || $qty > $eq['qty_available']) {
        die("Invalid Issue Quantity");
    }

    /* ================= 1️⃣ SAVE ISSUE TRANSACTION ================= */
    $stmt = $connect->prepare("
        INSERT INTO opwks_equipment_txn
        (equipment_id, txn_type, qty, unit_name, created_by)
        VALUES (?,?,?,?,?)
    ");
    $txn_type = 'ISSUE';
    $stmt->bind_param("isisi", $id, $txn_type, $qty, $unit, $user);
    $stmt->execute();
    $stmt->close();

    /* ================= 2️⃣ CREATE ISSUE VOUCHER HEADER ================= */
    $stmt = $connect->prepare("
        INSERT INTO opwks_issue_voucher
        (equipment_id, issued_to, total_qty, created_by)
        VALUES (?,?,?,?)
    ");
    $stmt->bind_param("isii", $id, $unit, $qty, $user);
    $stmt->execute();
    $voucher_id = $stmt->insert_id;
    $stmt->close();

    if ($voucher_id <= 0) {
        die("Voucher generation failed");
    }

    /* ================= 3️⃣ GENERATE VOUCHER NO ================= */
    $year = date('Y');
    $voucher_no = "OPWKS-ISS-$year-" . str_pad($voucher_id, 4, '0', STR_PAD_LEFT);

    $stmt = $connect->prepare("
        UPDATE opwks_issue_voucher
        SET voucher_no = ?
        WHERE id = ?
    ");
    $stmt->bind_param("si", $voucher_no, $voucher_id);
    $stmt->execute();
    $stmt->close();

    /* ================= 4️⃣ SAVE VOUCHER ITEM ================= */
    $stmt = $connect->prepare("
        INSERT INTO opwks_issue_voucher_items
        (voucher_id, equipment_id, qty, unit_name)
        VALUES (?,?,?,?)
    ");
    $stmt->bind_param("iiis", $voucher_id, $id, $qty, $unit);
    $stmt->execute();
    $stmt->close();

    /* ================= SUCCESS ================= */
    header("Location: opwks_equipment_table_view.php?issue_voucher=$voucher_no");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Issue OPWKS Equipment</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
</head>

<body class="bg-light">

<div class="container mt-4">
<div class="card shadow">

<div class="card-header bg-primary text-white">
    <h5 class="mb-0">📤 ISSUE OPWKS EQUIPMENT</h5>
</div>

<div class="card-body">

<table class="table table-bordered">
<tr><th>Job No</th><td><?= htmlspecialchars($eq['job_no']) ?></td></tr>
<tr><th>Nomenclature</th><td><?= htmlspecialchars($eq['equipment_name']) ?></td></tr>
<tr><th>A/U</th><td><?= $eq['au'] ?></td></tr>
<tr><th>Qty Available</th><td><b><?= $eq['qty_available'] ?></b></td></tr>
</table>

<form method="POST">

<label>Issued To (Store)</label>
<select name="unit_name" class="form-control" required>
<option value="">SELECT STORE</option>
<option>HQ CQ STORE</option>
<option>1 CQ STORE</option>
<option>2 CQ STORE</option>
<option>3 CQ STORE</option>
<option>3 COY EFS STORE</option>
<option>3 COY MT STORE</option>
<option>3 COY RADIO ROOM</option>
<option>3 RR STORE</option>
<option>3 RR BN</option>
<option>13 ENGR REGT</option>
<option>3 COY EFS STORE</option>
<option>LINE STORE</option>
<option>ICN STORE</option>
<option>MCCS STORE</option>
<option>RP STORE</option>
<option>EFS STORE</option>
<option>OP SHIVA STORE</option>
<option>MT STORE</option>
<option>LRW STORE</option>
<option>TM STORE</option>
<option>RHQ STORE</option>
<option>IT STORE</option>
<option>STRONG ROOM</option>
<option>CSO RES</option>
<option>NFS STORE</option>
<option>CABLE TV</option>
<option>OFFR MESS</option>
<option>JCO MESS</option>
<option>3 COY SYSTEM</option>
<option>SPORTS NCO</option>
<option>CSD</option>
<option>RAREMART</option>
<option>ACCT STORE</option>
<option>QM STORE</option>
<option>SIGS BR</option>
<option>RATION STORE</option>
<option>FOL STORE</option>
<option>LIBRARY</option>
<option>PROJECT STORE</option>
<option>CIPHER STORE</option>
<option>MANDIR STORE</option>
<option>REC ROOM</option>
<option>DELHI MS9</option>
<option>MES NCO</option>
<option>1/2 COY KOTE</option>
<option>HQ COY KOTE</option>
<option>AMN STORE</option>
<option>MI ROOM</option>
<option>15 COSR</option>
<option>EDN NCO</option>
<option>3 COY EFS STORE</option>
<option>2 COY EFS STORE</option>
<option>VFSR</option>
<option>KFSR</option>
<option>FAD KHUNMOH</option>
<option>HQ 268 BDE</option>
<option>19 IDSR</option>
<option>28 IDSR</option>
<option>68 MBSC</option>
<option>79 MBSC</option>
<option>14 CESR</option>
<option>14 WEU</option>
<option>25 AD REGT</option>
<option>54 ADV VET HOSP</option>
<option>102 IBSC</option>
<option>109 IBSC</option>
<option>162 TA BN</option>
<option>619 ADBSC</option>
<option>4015 FD HOSP</option>
<option>15 CBS</option>
<option>3 SECT RR</option>
<option>5 SECT RR</option>
<option>7 SECT RR</option>
<option>8 SECT RR</option>
<option>14 RR BN</option>
<option>15 CZW</option>
<option>113 FD REGT</option>
<option>JAKLI REGT</option>
<option>521 NSC</option>
<option>RADIO STORE</option>
<option>2 COY STRONG ROOM</option>
<option>SONAMARG DET</option>
<option>OAF STN HQ</option>
</select>

<label class="mt-2">Issue Quantity</label>
<input type="number"
       name="qty"
       min="1"
       max="<?= $eq['qty_available'] ?>"
       class="form-control"
       required>

<div class="mt-3 d-flex gap-2">
<button name="issue_submit" class="btn btn-primary">
📤 Issue & Generate Voucher
</button>
<a href="opwks_equipment_table_view.php" class="btn btn-secondary">
⬅ Back
</a>
</div>

</form>

</div>
</div>
</div>

</body>
</html>
