<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* ================= SAVE VOUCHER ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_voucher'])) {

    if (empty($_SESSION['VOUCHER_ITEMS'])) {
        die("No items in voucher");
    }

    $issued_to = trim($_POST['issued_to'] ?? '');
    $auth_by   = trim($_POST['auth_by'] ?? '');

    $iv_no   = $_POST['iv_no'] ?? '';
    $iv_unit = $_POST['iv_unit'] ?? '';
    $iv_stn  = $_POST['iv_stn'] ?? '';
    $iv_date = $_POST['iv_date'] ?? '';

    $rv_no   = $_POST['rv_no'] ?? '';
    $rv_unit = $_POST['rv_unit'] ?? '';
    $rv_stn  = $_POST['rv_stn'] ?? '';
    $rv_date = $_POST['rv_date'] ?? '';

    $note    = $_POST['note'] ?? '';

    $connect->begin_transaction();

    try {

        /* ================= SAVE MASTER ================= */
        $stmt = $connect->prepare("
            INSERT INTO voucher_master
            (
                voucher_type,
                issued_to,
                auth,
                iv_no,
                iv_unit,
                iv_stn,
                iv_date,
                rv_no,
                rv_unit,
                rv_stn,
                rv_date,
                note,
                created_by,
                created_at
            )
            VALUES
            (
                'LOAN RENEWAL',
                ?,?,?,?,?,?,?,?,?,?,?,?,
                NOW()
            )
        ");

        $stmt->bind_param(
            "sssssssssssi",
            $issued_to,
            $auth_by,
            $iv_no,
            $iv_unit,
            $iv_stn,
            $iv_date,
            $rv_no,
            $rv_unit,
            $rv_stn,
            $rv_date,
            $note,
            $_SESSION['id']
        );

        $stmt->execute();
        $voucher_id = $stmt->insert_id;
        $stmt->close();

        /* ================= SAVE ITEMS ================= */
        $itemStmt = $connect->prepare("
            INSERT INTO voucher_items
            (voucher_id, lp_no, nomenclature, au, qty, cost, barcode)
            VALUES (?,?,?,?,?,?,?)
        ");

        foreach ($_SESSION['VOUCHER_ITEMS'] as $it) {

            $lp   = $it['lp'];
            $name = $it['name'];
            $au   = $it['au'];
            $qty  = (int)$it['qty'];
            $cost = (float)$it['cost'];

            $barcode_array = [];

            if (!empty($it['barcodes']) && is_array($it['barcodes'])) {
                $barcode_array = $it['barcodes'];
            } else {
                for ($i = 1; $i <= $qty; $i++) {
                    $barcode_array[] = "LN".$voucher_id."_".uniqid();
                }
            }

            $barcode_json = json_encode($barcode_array);

            $itemStmt->bind_param(
                "isssids",
                $voucher_id,
                $lp,
                $name,
                $au,
                $qty,
                $cost,
                $barcode_json
            );

            $itemStmt->execute();
        }

        $itemStmt->close();

        $connect->commit();

        /* redirect to voucher view */
        header("Location: voucher_view.php?id=".$voucher_id);
        exit;

    } catch (Exception $e) {
        $connect->rollback();
        die("Voucher save failed: " . $e->getMessage());
    }
}

$items  = $_SESSION['VOUCHER_ITEMS'] ?? [];
$header = $_SESSION['VOUCHER_HEADER'] ?? [];
$issued_to_default = $header['unit_name'] ?? 'NOT SET';
$grant_type = strtoupper(trim($header['grant_type'] ?? ''));
?>
<!DOCTYPE html>
<html>
<head>
<title>LOAN COST VOUCHER - 2025</title>

<style>
@page{ size:A4; margin:15mm; }
body{ margin:0; font-family:"Times New Roman", serif; }
.page{ width:210mm; margin:auto; position:relative; }

.iafz{
    position:absolute;
    top:-15mm;
    right:0;
    font-size:13px;
    font-weight:bold;
    text-decoration:underline;
}

h3,h4{ text-align:center; margin:0; }
h3{ margin-top:18mm; }
h4{ margin-top:4px; }

.header-row{
    display:flex;
    justify-content:space-between;
    margin-top:22px;
}

.left-block{ width:30%; }
.right-block{ width:27%; }

.row{ display:flex; margin-bottom:10px; }
.label{ width:55px; }

input{
    border:none;
    outline:none;
    font-family:inherit;
    font-size:15px;
    width:150px;
}

.center-block{
    text-align:center;
    margin-top:30px;
}
.center-block input{ width:320px; }

table{
    width:100%;
    border-collapse:collapse;
    margin-top:18px;
    font-size:12.5px;
}
th,td{
    border:1px solid #000;
    padding:5px;
    text-align:center;
}
td:nth-child(3){ text-align:left; }

.note-text{
    text-align:center;
    font-size:17px;
    font-weight:bold;
    margin-top:6px;
}

textarea{
    width:100%;
    min-height:20px;
    font-family:inherit;
    font-size:16px;
    resize:none;
}

.sign-row{
    margin-top:40px;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    width:100%;
}

.sign-col{
    width:32%;
    text-align:center;
    font-size:14px;
}

.sign-col b{
    display:block;
    margin-bottom:6px;
}

.collect{
    margin-top:8px;
    line-height:2.2;
    text-align:left;
    padding-left:22%;
}



.controls{
    text-align:center;
    margin-top:30px;
}

.btn-action{
    display:inline-block;
    padding:10px 22px;
    margin:6px;
    background:#000;
    color:#fff;
    text-decoration:none;
    border:none;
    border-radius:6px;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
}

.btn-action:hover{
    background:#333;
}

@media print{
    .controls{ display:none !important; }
    textarea{ border:none; }
}

</style>
</head>

<body>
<div class="page">

<div class="iafz">In lieu of IAFZ-2096</div>

<h3>LOAN RENEWAL VOUCHER – 2025</h3>
<h4>ISSUE, RECEIPT & EXPENCE VOUCHER</h4>

<form method="POST">
<input type="hidden" name="save_voucher" value="1">

<div class="header-row">
    <div class="left-block">
        <div class="row">
        <div class="label">IV No:</div>
        <input type="text" name="iv_no">
    </div>

    <div class="row">
        <div class="label">Unit:</div>
        <input type="text" name="iv_unit">
    </div>

    <div class="row">
        <div class="label">Stn :</div>
        <input type="text" name="iv_stn">
    </div>

    <div class="row">
        <div class="label">Date:</div>
        <input type="date" name="iv_date" value="<?= date('Y-m-d') ?>">
    </div>
    </div>

    <div class="right-block">
        <div class="row">
            <div class="label">RV No:</div>
            <input type="text" name="rv_no">
        </div>

        <div class="row">
            <div class="label">Unit:</div>
            <input type="text" name="rv_unit">
        </div>

        <div class="row">
            <div class="label">Stn :</div>
            <input type="text" name="rv_stn">
        </div>

        <div class="row">
            <div class="label">Date:</div>
            <input type="date" name="rv_date" value="<?= date('Y-m-d') ?>">
        </div>
    </div>
</div>

<div class="center-block">
    <b>ISSUED TO :</b>
    <input name="issued_to" value="<?= htmlspecialchars($issued_to_default) ?>" readonly>
    <br><br>
    <b>AUTH :</b>
    <input name="auth_by" required>
</div>

<table>
<thead>
<tr>
    <th>SER NO</th>
  <th>
<?= (strpos($grant_type, 'OPWKS') !== false) ? 'JOB NO / NAR NO' : 'LP NO' ?>
</th>
    <th>NOMENCLATURE</th>
    <th>A/U</th>
    <th>QTY</th>
    <th>COST OF EACH</th>
    <th>REMARKS</th>
</tr>
</thead>
<tbody>
<?php foreach($items as $i=>$it){ ?>
<tr>
    <td><?= $i+1 ?></td>

    <!-- LP -->
    <td><?= htmlspecialchars($it['lp']) ?></td>

    <!-- Nomenclature -->
    <td style="text-align:center;">
        <?= htmlspecialchars($it['name']) ?>
    </td>

    <!-- A/U -->
    <td><?= htmlspecialchars($it['au']) ?></td>

    <!-- Qty -->
    <td><?= htmlspecialchars($it['qty']) ?></td>

    <!-- Cost -->
    <td style="text-align:center;">
        ₹ <?= number_format($it['cost'],2) ?>
    </td>

    <!-- ✅ Barcode in Remarks -->

<!-- ✅ QR Code in Remarks -->
<td style="text-align:center">

<?php

$qrData =
"Equipment: ".$it['name']."\n".
"LP No: ".$it['lp']."\n".
"Qty: ".$it['qty']."\n".
"Cost Each: ₹".number_format($it['cost'],2)."\n".
"Issued To: ".$issued_to_default."\n".
"Date: ".date('d-m-Y');

echo '<img src="qr.php?data='.urlencode($qrData).'" width="100">';

?>

</td>
</tr>
<?php } ?>
</tbody>
</table>
<p class="note-text">(Total items <?= count($items) ?> only)</p>

<b>Note :</b>
<textarea name="note"></textarea>

<div class="sign-row">

    <div class="sign-col">
        <b>ISSUED BY</b>
        
    </div>

    <div class="sign-col">
        <b>COLLECTED / DEPOSITED BY</b>
        <div class="collect">
            No :<br>
            Rank :<br>
            Name :<br>
            Sig :<br>
            Dt :
        </div>
    </div>

    <div class="sign-col">
        <b>RECEIVED BY</b>
    </div>

</div>

<div class="controls">

    <!-- SAVE -->
    <button type="submit" class="btn-action">
        💾 SAVE
    </button>

    <!-- PRINT -->
    <button type="button" onclick="window.print()" class="btn-action">
        🖨 PRINT
    </button>

    <!-- PRINT LABELS (SAFE ID) -->
    <?php if(isset($voucher_id) && $voucher_id > 0): ?>
        <a href="print_issued_labels.php?id=<?= $voucher_id ?>" 
           class="btn-action">
           🏷 Print Issued Labels
        </a>
    <?php endif; ?>

    <!-- BACK -->
    <button type="button"
            onclick="location.href='central_view.php'"
            class="btn-action">
        ⬅ BACK
    </button>

</div>

</form>

</div>
</body>
</html>
