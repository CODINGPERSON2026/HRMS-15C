<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* ================= SAFETY CHECK ================= */
if (empty($_SESSION['VOUCHER_ITEMS'])) {
    die("No items in voucher");
}

$items  = $_SESSION['VOUCHER_ITEMS'];
$header = $_SESSION['VOUCHER_HEADER'] ?? [];

$issued_to_default = $header['unit_name'] ?? 'NOT SET';

$voucher_id = (int)($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
<title>VOUCHER - EDITABLE</title>

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
td:nth-child(4){ text-align:left; }

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

</style>
</head>

<body>
<div class="page">

<div class="iafz">In lieu of IAFZ-2096</div>

<h3>LOAN RENEWAL VOUCHER – 2026</h3>
<h4>ISSUE, RECEIPT & EXPENCE VOUCHER</h4>

<form id="voucherForm">

<div class="header-row">

    <!-- LEFT -->
    <div class="left-block">
        <div class="row"><div class="label">IV No:</div><input name="iv_no"></div>
        <div class="row"><div class="label">Unit:</div><input name="iv_unit"></div>
        <div class="row"><div class="label">Stn :</div><input name="iv_stn"></div>
        <div class="row"><div class="label">Date:</div><input type="date" name="iv_date"></div>
    </div>

    <!-- RIGHT -->
    <div class="right-block">
        <div class="row"><div class="label">RV No:</div><input name="rv_no"></div>
        <div class="row"><div class="label">Unit:</div><input name="rv_unit"></div>
        <div class="row"><div class="label">Stn :</div><input name="rv_stn"></div>
        <div class="row"><div class="label">Date:</div><input type="date" name="rv_date"></div>
    </div>

</div>

<div class="center-block">
    <b>ISSUED TO :</b>
    <input name="issued_to"
           value="<?= htmlspecialchars($issued_to_default) ?>"
           readonly>
    <br><br>

    <b>AUTH :</b>
    <input name="auth">
</div>

<table>
<thead>
<tr>
    <th>SER NO</th>
    <th>LP NO</th>
    <th>CAT / PART NO</th>
    <th>NOMENCLATURE</th>
    <th>A/U</th>
    <th>QTY</th>
    <th>REMARKS</th>
</tr>
</thead>

<tbody>
<?php foreach($items as $i=>$it){ ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= htmlspecialchars($it['lp']) ?></td>
    <td><?= htmlspecialchars($it['cat'] ?? '') ?></td>
    <td style="text-align:center"><?= htmlspecialchars($it['name']) ?></td>
    <td><?= htmlspecialchars($it['au']) ?></td>

    <!-- Qty column -->
    <td><?= htmlspecialchars($it['qty']) ?></td>

    <!-- ✅ Remarks column -->
    <td style="text-align:center">

<?php
if(!empty($it['barcodes']) && is_array($it['barcodes'])){

    // Take only first barcode
    $code = $it['barcodes'][0];

    $qrData =
    "EQP ID: ".$code."\n".
    "Equipment: ".$it['name']."\n".
    "LP No: ".$it['lp']."\n".
    "Cat: ".$it['cat']."\n".
    "Total Qty: ".$it['qty']."\n".
    "Issued To: ".$issued_to_default."\n".
    "Date: ".date('d-m-Y');

    echo '<img src="qr.php?data='.urlencode($qrData).'" width="110">';
}
?>

</td>
</tr>
<?php } ?>
</tbody>
</table>

<p class="note-text">/</p>
<p class="note-text">(Total items <?= count($items) ?> only)</p>

<p class="note-text">
Pl sign and return one copy of this issue voucher to this office duly receipted.
</p>

<b>Note :</b>
<textarea name="note"></textarea>

</form>

<div class="sign">
    <div><b>ISSUED BY</b></div>
    <div>
        <b>COLLECTED / DEPOSITED BY</b>
        <div class="collect">
            No :<br>
            Rank :<br>
            Name :<br>
            Sig :<br>
            Dt :
        </div>
    </div>
    <div><b>RECEIVED BY</b></div>
</div>

<div class="controls">

<button type="button" onclick="saveVoucher()" class="btn-action">
💾 SAVE
</button>

<button type="button" onclick="window.print()" class="btn-action">
🖨 PRINT
</button>

<?php if(isset($voucher_id) && $voucher_id > 0): ?>
<a href="print_issued_labels.php?id=<?= $voucher_id ?>" class="btn-action">
🏷 Print Issued Labels
</a>
<?php endif; ?>

<button type="button"
onclick="location.href='central_view.php'"
class="btn-action">
⬅ BACK
</button>

</div>

<script>
function saveVoucher(){

    const form = document.getElementById("voucherForm");
    const data = new FormData(form);

    fetch("save_voucher.php",{
        method:"POST",
        body:data
    })
    .then(res => res.text())
    .then(msg => {

        msg = msg.trim();

        if(msg.startsWith("SUCCESS")){
            const id = msg.split("|")[1];

            // ✅ Redirect to view page with voucher id
            window.location.href = "voucher_view.php?id="+id;

        }else{
            alert(msg);
        }

    });

}
</script>

</body>
</html>

</body>
</html>
