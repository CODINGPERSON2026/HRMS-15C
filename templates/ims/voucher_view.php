<?php
session_start();
require_once "connect.php";

if (!isset($_GET['id'])) {
    die("Voucher ID missing");
}

$voucher_id = (int)$_GET['id'];

/* ================= FETCH MASTER ================= */
$stmt = $connect->prepare("
    SELECT *
    FROM voucher_master
    WHERE id = ?
");
$stmt->bind_param("i", $voucher_id);
$stmt->execute();
$voucher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$voucher) die("Voucher not found");

/* ================= GRANT TYPE ================= */
$grant_type = strtoupper(trim($voucher['grant_type'] ?? ''));

/* ================= FETCH ITEMS ================= */
$itemStmt = $connect->prepare("
    SELECT *
    FROM voucher_items
    WHERE voucher_id = ?
");
$itemStmt->bind_param("i", $voucher_id);
$itemStmt->execute();
$res = $itemStmt->get_result();

/* ===== DECIDE COLUMNS ===== */
$showCost = false;
$showCat  = true;
$items    = [];

while ($r = $res->fetch_assoc()) {
    if (!empty($r['cost']) && $r['cost'] > 0) {
        $showCost = true;
        $showCat  = false;
    }
    $items[] = $r;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Voucher View</title>

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
.label{ width:60px; font-weight:bold; }
.value{ font-size:15px; }

.center-block{
    text-align:center;
    margin-top:30px;
}

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
td:nth-child(4){ text-align:left; }

.note-text{
    text-align:center;
    font-size:17px;
    font-weight:bold;
    margin-top:6px;
}

.note-value{
    margin-top:6px;
    font-size:16px;
}

.sign{
    margin-top:40px;
    display:flex;
    justify-content:space-between;
}
.sign div{ width:30%; text-align:center; }
.collect{ line-height:2.6; margin-top:10px; }

.controls{
    text-align:center;
    margin-top:30px;
}

.btn-action{
    display:inline-block;
    padding:10px 22px;
    margin:5px;
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
@media print{ .controls{ display:none; } }
</style>
</head>

<body>
<div class="page">

<div class="iafz">In lieu of IAFZ-2096</div>

<h3><?= htmlspecialchars($voucher['voucher_type']) ?> VOUCHER – <?= date('Y') ?></h3>
<h4>ISSUE, RECEIPT & EXPENCE VOUCHER</h4>

<div class="header-row">
<div class="left-block">
    <div class="row"><div class="label">IV No:</div><div class="value"><?= htmlspecialchars($voucher['iv_no']) ?></div></div>
    <div class="row"><div class="label">Unit:</div><div class="value"><?= htmlspecialchars($voucher['iv_unit']) ?></div></div>
    <div class="row"><div class="label">Stn :</div><div class="value"><?= htmlspecialchars($voucher['iv_stn']) ?></div></div>
    <div class="row"><div class="label">Date:</div><div class="value"><?= htmlspecialchars($voucher['iv_date']) ?></div></div>
</div>

<div class="right-block">
    <div class="row"><div class="label">RV No:</div><div class="value"><?= htmlspecialchars($voucher['rv_no']) ?></div></div>
    <div class="row"><div class="label">Unit:</div><div class="value"><?= htmlspecialchars($voucher['rv_unit']) ?></div></div>
    <div class="row"><div class="label">Stn :</div><div class="value"><?= htmlspecialchars($voucher['rv_stn']) ?></div></div>
    <div class="row"><div class="label">Date:</div><div class="value"><?= htmlspecialchars($voucher['rv_date']) ?></div></div>
</div>
</div>

<div class="center-block">
<b>ISSUED TO :</b> <?= htmlspecialchars($voucher['issued_to']) ?><br><br>
<b>AUTH :</b> <?= htmlspecialchars($voucher['auth']) ?>
</div>

<table>
<thead>
<tr>
<th>Ser No</th>

<th>
<?php
if (strpos($grant_type, 'OPWKS') !== false) {
    echo 'JOB NO / NAR NO';
} else {
    echo 'LP No';
}
?>
</th>

<?php if ($showCat): ?>
<th>Cat / Part No</th>
<?php endif; ?>

<th>Nomenclature</th>
<th>A/U</th>
<th>Qty</th>

<?php if ($showCost): ?>
<th>Cost</th>
<?php endif; ?>

<th>Remarks</th>
</tr>
</thead>

<tbody>
<?php $i=1; foreach($items as $it): ?>
<tr>
<td><?= $i++ ?></td>

<td><?= htmlspecialchars($it['lp_no']) ?></td>

<?php if ($showCat): ?>
<td><?= htmlspecialchars($it['cat_part_no'] ?? '') ?></td>
<?php endif; ?>

<td><?= htmlspecialchars($it['nomenclature']) ?></td>
<td><?= htmlspecialchars($it['au']) ?></td>
<td><?= htmlspecialchars($it['qty']) ?></td>

<?php if ($showCost): ?>
<td><?= number_format($it['cost'],2) ?></td>
<?php endif; ?>

<td style="text-align:center">

<?php
if(!empty($it['barcode'])){

    $barcodeArray = json_decode($it['barcode'], true);

    if(is_array($barcodeArray)){

        $firstCode = $barcodeArray[0]; // only first barcode

$qrData =
"EQP ID: ".$firstCode."\n".
"Equipment: ".$it['nomenclature']."\n".
"LP No: ".$it['lp_no']."\n".
"Qty: ".$it['qty']."\n".
"Issued To: ".$voucher['issued_to']."\n".
"Date: ".date('d-m-Y');

echo '<img src="qr.php?data='.urlencode($qrData).'" width="100">';
        

    }
}
?>

</td>

</tr>
<?php endforeach; ?>
</tbody>
</table>

<p class="note-text">(Total items <?= count($items) ?> only)</p>

<p class="note-text">
Pl sign and return one copy of this issue voucher to this office duly receipted.
</p>

<b>Note :</b>
<div class="note-value"><?= nl2br(htmlspecialchars($voucher['note'])) ?></div>

<div class="sign">
<div><b>ISSUED BY</b></div>
<div>
<b>COLLECTED / DEPOSITED BY</b>
<div class="collect">
No :<br>Rank :<br>Name :<br>Sig :<br>Dt :
</div>
</div>
<div><b>RECEIVED BY</b></div>
</div>

<div class="controls">

    <button type="button" onclick="window.print()" class="btn-action">
        🖨 PRINT
    </button>

    <a href="print_issued_labels.php?id=<?= $voucher['id'] ?>" 
       class="btn-action">
        🏷 Print Issued Labels
    </a>

     <!--EDIT VOUCHER-->
    <a href="edit_voucher.php?id=<?= $voucher['id'] ?>" class="btn-action">
    ✏ EDIT VOUCHER
    </a>

    <button type="button" onclick="location.href='voucher_list.php'" class="btn-action">
        ⬅ BACK
    </button>

</div>
</div>
</body>
</html>