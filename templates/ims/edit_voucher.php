<?php
require_once "connect.php";

$id = (int)$_GET['id'];

$stmt = $connect->prepare("SELECT * FROM voucher_master WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$voucher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$voucher){
    die("Voucher not found");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Voucher</title>

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

.row{
    display:flex;
    margin-bottom:10px;
}
.label{
    width:65px;
    font-weight: bold;
    font-size: 15px;
    padding: 1px;
}

input, textarea{
    border:none;
    border-bottom:1px solid #000;
    font-family:inherit;
    font-size:15px;
    width:100%;
    outline:none;
}

.center-block{
    text-align:center;
    margin-top:30px;
}

.center-block input{
    width:300px;
    text-align:center;
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

.note-text{
    text-align:center;
    font-size:17px;
    font-weight:bold;
    margin-top:6px;
}

.sign{
    margin-top:40px;
    display:flex;
    justify-content:space-between;
}
.sign div{
    width:30%;
    text-align:center;
}

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
</style>
</head>

<body>
<div class="page">

<form action="update_voucher.php?id=<?= $id ?>" method="POST">

<div class="iafz">In lieu of IAFZ-2096</div>

<h3><?= htmlspecialchars($voucher['voucher_type']) ?> VOUCHER – <?= date('Y') ?></h3>
<h4>ISSUE, RECEIPT & EXPENCE VOUCHER</h4>

<div class="header-row">

<div class="left-block">
    <div class="row">
        <div class="label">IV No:</div>
        <input type="text" name="iv_no" value="<?= htmlspecialchars($voucher['iv_no']) ?>">
    </div>

    <div class="row">
        <div class="label">Unit:</div>
        <input type="text" name="iv_unit" value="<?= htmlspecialchars($voucher['iv_unit']) ?>">
    </div>

    <div class="row">
        <div class="label">Stn:</div>
        <input type="text" name="iv_stn" value="<?= htmlspecialchars($voucher['iv_stn']) ?>">
    </div>

    <div class="row">
        <div class="label">Date:</div>
        <input type="date" name="iv_date" value="<?= htmlspecialchars($voucher['iv_date']) ?>">
    </div>
</div>

<div class="right-block">
    <div class="row">
        <div class="label" >RV No:</div>
        <input type="text" name="rv_no" value="<?= htmlspecialchars($voucher['rv_no']) ?>">
    </div>

    <div class="row">
        <div class="label">Unit:</div>
        <input type="text" name="rv_unit" value="<?= htmlspecialchars($voucher['rv_unit']) ?>">
    </div>

    <div class="row">
        <div class="label">Stn:</div>
        <input type="text" name="rv_stn" value="<?= htmlspecialchars($voucher['rv_stn']) ?>">
    </div>

    <div class="row">
        <div class="label">Date:</div>
        <input type="date" name="rv_date" value="<?= htmlspecialchars($voucher['rv_date']) ?>">
    </div>
</div>

</div>

<div class="center-block">
    <b>ISSUED TO :</b><br>
    <input type="text" name="issued_to" value="<?= htmlspecialchars($voucher['issued_to']) ?>">
    <br><br>

    <b>AUTH :</b><br>
    <input type="text" name="auth" value="<?= htmlspecialchars($voucher['auth']) ?>">
</div>

<p class="note-text">Voucher details update form</p>

<b>Note :</b>
<textarea name="note"><?= htmlspecialchars($voucher['note']) ?></textarea>

<div class="sign">
    <div><b>ISSUED BY</b></div>
    <div><b>COLLECTED / DEPOSITED BY</b></div>
    <div><b>RECEIVED BY</b></div>
</div>

<div class="controls">
    <button type="submit" class="btn-action">
        💾 UPDATE VOUCHER
    </button>

    <a href="voucher_view.php?id=<?= $id ?>" class="btn-action">
        ⬅ BACK
    </a>
</div>

</form>
</div>
</body>
</html>