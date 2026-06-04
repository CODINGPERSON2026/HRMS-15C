<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

if (!isset($_SESSION['id'])) {
    die("Unauthorized access");
}

$voucher_id = (int)($_GET['id'] ?? 0);
if ($voucher_id <= 0) {
    die("Invalid Voucher ID");
}

/* ================= HEADER ================= */
$h = $connect->prepare("
    SELECT *
    FROM return_vouchers
    WHERE id = ?
    LIMIT 1
");
$h->bind_param("i", $voucher_id);
$h->execute();
$header = $h->get_result()->fetch_assoc();

if (!$header) {
    die("Return Voucher Not Found");
}

/* ================= ITEMS ================= */
$i = $connect->prepare("
    SELECT 
        lp_no,
        cat_part_no,
        nomenclature,
        au,
        qty,
        remarks
    FROM return_vouchers_items
    WHERE return_voucher_id = ?
    ORDER BY id ASC
");
$i->bind_param("i", $voucher_id);
$i->execute();
$items = $i->get_result();

function numberToWords($num){
    $ones = [
        0=>"ZERO","ONE","TWO","THREE","FOUR","FIVE","SIX",
        "SEVEN","EIGHT","NINE","TEN","ELEVEN","TWELVE",
        "THIRTEEN","FOURTEEN","FIFTEEN","SIXTEEN",
        "SEVENTEEN","EIGHTEEN","NINETEEN"
    ];

    $tens = [
        2=>"TWENTY","THIRTY","FORTY","FIFTY",
        "SIXTY","SEVENTY","EIGHTY","NINETY"
    ];

    if($num < 20) return $ones[$num];
    if($num < 100) return $tens[intval($num/10)] . ($num%10 ? " ".$ones[$num%10] : "");
    if($num < 1000) return $ones[intval($num/100)] . " HUNDRED" . ($num%100 ? " ".numberToWords($num%100) : "");
    return $num;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>RETURN VOUCHER – PRINT</title>
<style>
@page{ size:A4; margin:15mm; }

body{
    margin:0;
    font-family:"Times New Roman", serif;
}

.page{
    width:210mm;
    margin:auto;
}

.iafz{
    text-align:right;
    font-size:13px;
    font-weight:bold;
    text-decoration:underline;
}

h3{
    text-align:center;
    margin:15px 0 20px;
}

.header{
    display:flex;
    justify-content:space-between;
    margin-top:10px;
}

.block{
    width:45%;
}

.row{
    margin-bottom:8px;
    font-size:14px;
}

input{
    border:none;
    background:transparent;
    font-family:inherit;
    font-size:14px;
    font-weight:600;
    width:180px;
}

.center{
    text-align:center;
    margin:20px 0;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:15px;
    font-size:12px;
}

th,td{
    border:1px solid #000;
    padding:5px;
    text-align:center;
}

td:nth-child(4){
    text-align:left;
}

.note{
    text-align:center;
    margin-top:10px;
    font-weight:bold;
}

.sign{
    margin-top:40px;
    display:flex;
    justify-content:space-between;
}

.sign > div{
    width:30%;
}

.collect{
    margin-top:10px;
    line-height:2;
}

.controls{
    text-align:center;
    margin-top:25px;
}

button{
    padding:8px 20px;
    border:1px solid #000;
    background:#fff;
    cursor:pointer;
}

@media print{
    .controls{ display:none; }
}
</style>
</head>

<body>
<div class="page">

<div class="iafz">In lieu of IAFZ-2096</div>

<h3>RETURN VOUCHER – <?= date('Y', strtotime($header['rv_date'])) ?></h3>

<div class="header">
    <div class="block">
        <div class="row">IV No : <input value="<?= htmlspecialchars($header['iv_no'] ?? '') ?>"></div>
        <div class="row">Unit : <input value="<?= htmlspecialchars($header['iv_unit'] ?? '') ?>"></div>
        <div class="row">Stn : <input value="<?= htmlspecialchars($header['iv_stn'] ?? '') ?>"></div>
        <div class="row">Date : <input value="<?= !empty($header['iv_date']) ? date('d-m-Y', strtotime($header['iv_date'])) : '' ?>"></div>
    </div>

    <div class="block">
        <div class="row">RV No : <input value="<?= htmlspecialchars($header['rv_no'] ?? '') ?>"></div>
        <div class="row">Unit : <input value="<?= htmlspecialchars($header['rv_unit'] ?? '') ?>"></div>
        <div class="row">Stn : <input value="<?= htmlspecialchars($header['rv_stn'] ?? '') ?>"></div>
        <div class="row">Date : <input value="<?= date('d-m-Y', strtotime($header['rv_date'])) ?>"></div>
    </div>
</div>

<div class="center">
    <b>RETURNED FROM :</b>
    <input style="width:320px" value="<?= htmlspecialchars($header['returned_from'] ?? '') ?>"><br><br>

    <b>AUTH :</b>
    <input style="width:320px" value="<?= htmlspecialchars($header['auth_text'] ?? '') ?>">
</div>

<table>
<thead>
<tr>
    <th>S.No</th>
    <th>LP No</th>
    <th>Cat / Part No</th>
    <th>Nomenclature</th>
    <th>A/U</th>
    <th>Qty</th>
    <th>Remarks</th>
</tr>
</thead>
<tbody>
<?php
$sn = 1;
$total_qty = 0;

if ($items->num_rows > 0):
    while($it = $items->fetch_assoc()):
        $total_qty += (int)$it['qty'];
?>
<tr>
    <td><?= $sn++ ?></td>
    <td><?= htmlspecialchars($it['lp_no']) ?></td>
    <td><?= htmlspecialchars($it['cat_part_no']) ?></td>
    <td><?= htmlspecialchars($it['nomenclature']) ?></td>
    <td><?= htmlspecialchars($it['au']) ?></td>
    <td><?= (int)$it['qty'] ?></td>
    <td><?= htmlspecialchars($it['remarks']) ?></td>
</tr>
<?php endwhile; else: ?>
<tr>
    <td colspan="7"><b>No Return Items Found</b></td>
</tr>
<?php endif; ?>
</tbody>
</table>

<p class="note">
Total Qty : <?= $total_qty ?> (<?= numberToWords($total_qty) ?>)
</p>

<p class="note">
Pl sign and return one copy of this return voucher to this office duly receipted.
</p>

<p>
    <b>Note :</b>
    <input style="width:500px"
           value="<?= htmlspecialchars($header['note'] ?? '') ?>">
</p>

<div class="sign">
    <div><b>ISSUED BY</b></div>

    <div>
        <b>COLLECTED / DEPOSITED BY</b>
        <div class="collect">
            No : <br>
            Rank : <br>
            Name : <br>
            Sig : <br>
            Dt :
        </div>
    </div>

    <div><b>RECEIVED BY</b></div>
</div>

<div class="controls">
    <button onclick="window.print()">PRINT</button>
    <button onclick="location.href='view_return_voucher.php'">⬅ BACK</button>
</div>

</div>
</body>
</html>