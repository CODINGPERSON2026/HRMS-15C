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
    FROM return_vouchers
    WHERE id = ?
");
$stmt->bind_param("i", $voucher_id);
$stmt->execute();
$voucher = $stmt->get_result()->fetch_assoc();

if (!$voucher) die("Return Voucher not found");

/* ================= FETCH ITEMS ================= */
$itemStmt = $connect->prepare("
    SELECT *
    FROM return_vouchers_items
    WHERE return_voucher_id = ?
");
$itemStmt->bind_param("i", $voucher_id);
$itemStmt->execute();
$items = $itemStmt->get_result();

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

    if($num < 20){
        return $ones[$num];
    } elseif($num < 100){
        return $tens[intval($num/10)] . ($num%10 ? " ".$ones[$num%10] : "");
    } elseif($num < 1000){
        return $ones[intval($num/100)] . " HUNDRED" . ($num%100 ? " ".numberToWords($num%100) : "");
    } else {
        return $num;
    }
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
    margin:20px 0;
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
}

.center{
    text-align:center;
    margin:20px 0;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:15px;
    font-size:12.5px;
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
    width:32%;
}

.collect{
    margin-top:10px;
    line-height:2.2;
}

.controls{
    text-align:center;
    margin-top:25px;
}

.btn{
    padding:8px 20px;
    border:1px solid #000;
    background:#fff;
    cursor:pointer;
    text-decoration:none;
    color:#000;
}

@media print{
    .controls{ display:none; }
}

input{
    border:none;
    outline:none;
    background:transparent;
    font-family:inherit;
    font-size:14px;
    width:180px;
}
</style>
</head>

<body>
<div class="page">

<div class="iafz">In lieu of IAFZ-2096</div>

<h3>RETURN VOUCHER – <?= date('Y', strtotime($voucher['rv_date'])) ?></h3>

<!-- ================= HEADER ================= -->
<form id="returnVoucherForm">

    <div class="header">
        <div class="block">
            <div class="row">
                <b>IV No :</b>
                <input type="text" name="iv_no"
                value="<?= htmlspecialchars($voucher['iv_no'] ?? '') ?>">
            </div>

            <div class="row">
                <b>Unit :</b>
                <input type="text" name="iv_unit"
                value="<?= htmlspecialchars($voucher['iv_unit'] ?? '') ?>">
            </div>

            <div class="row">
                <b>Stn :</b>
                <input type="text" name="iv_stn"
                value="<?= htmlspecialchars($voucher['iv_stn'] ?? '') ?>">
            </div>

            <div class="row">
                <b>Date :</b>
                <input type="date" name="iv_date"
                value="<?= htmlspecialchars($voucher['iv_date'] ?? '') ?>">
            </div>
        </div>

        <div class="block">
            <div class="row">
                <b>RV No :</b>
                <input type="text" name="rv_no"
                value="<?= htmlspecialchars($voucher['rv_no'] ?? '') ?>">
            </div>

            <div class="row">
                <b>Unit :</b>
                <input type="text" name="returned_from"
                value="<?= htmlspecialchars($voucher['returned_from'] ?? '') ?>">
            </div>

            <div class="row">
                <b>Stn :</b>
                <input type="text" name="rv_stn"
                value="<?= htmlspecialchars($voucher['rv_stn'] ?? '') ?>">
            </div>

            <div class="row">
                <b>Date :</b>
                <input type="date" name="rv_date"
                value="<?= htmlspecialchars($voucher['rv_date'] ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="center">
        <b>ISSUED TO :</b>
        <input type="text" value="QM STORE" readonly>

        <br><br>

        <b>AUTH :</b>
        <input type="text" name="auth_text"
        value="<?= htmlspecialchars($voucher['auth_text'] ?? '') ?>">
    </div>

<!-- ================= TABLE ================= -->
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
$totalItems = 0;

while($it = $items->fetch_assoc()):
$totalItems++;
?>
<tr>
    <td><?= $sn++ ?></td>
    <td><?= htmlspecialchars($it['lp_no']) ?></td>
    <td><?= htmlspecialchars($it['cat_part_no']) ?></td>
    <td><?= htmlspecialchars($it['nomenclature']) ?></td>
    <td><?= htmlspecialchars($it['au']) ?></td>
    <td><?= htmlspecialchars($it['qty']) ?></td>
    <td><?= htmlspecialchars($it['remarks']) ?></td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

<p class="note">
    Total Items : <?= $totalItems ?> (<?= numberToWords($totalItems) ?>)
</p>

<p class="note">
Pl sign and return one copy of this return voucher to this office duly receipted.
</p>

<p><b>Note :</b> <?= htmlspecialchars($voucher['note'] ?? '') ?></p>

<!-- ================= SIGNATURE ================= -->
<div class="sign">
    <div>
        <b>ISSUED BY</b>
    </div>

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

    <div>
        <b>RECEIVED BY</b>
    </div>
</div>

<div class="controls">
    <button type="button" onclick="saveReturnVoucher()" class="btn">
        💾 SAVE VOUCHER
    </button>

    <button onclick="window.print()" class="btn">PRINT</button>

    <a href="user_return_vouchers.php" class="btn">BACK</a>
</div>

</div>
<script>
function saveReturnVoucher(){

    const form = document.getElementById("returnVoucherForm");
    const data = new FormData(form);

    fetch("save_return_voucher.php?id=<?= $voucher_id ?>",{
        method:"POST",
        body:data
    })
    .then(res => res.text())
    .then(msg => {
        if(msg.trim() === "SUCCESS"){
            alert("Voucher Saved Successfully");
            window.location.href = "user_return_vouchers.php";
        }else{
            alert(msg);
        }
    });

}
</script>

</body>
</html>