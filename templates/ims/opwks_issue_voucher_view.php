<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid Voucher");

/* HEADER */
$head = $connect->prepare("SELECT * FROM opwks_issue_voucher WHERE id=?");
$head->bind_param("i",$id);
$head->execute();
$voucher = $head->get_result()->fetch_assoc();
$head->close();

if(!$voucher) die("Voucher not found");

/* ITEMS */
$items = $connect->prepare("
    SELECT 
        em.job_no,
        em.equipment_name,
        em.au,
        em.cost_each,
        vi.qty,
        (vi.qty * em.cost_each) AS total_amt
    FROM opwks_issue_voucher_items vi
    JOIN opwks_equipment_master em ON em.id=vi.equipment_id
    WHERE vi.voucher_id=?
");
$items->bind_param("i",$id);
$items->execute();
$resItems = $items->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<title>OPWKS Issue Voucher</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#fff;
    padding:40px;
    font-family:"Times New Roman", serif;
    color:#000;
}
.top-note{
    position:absolute;
    top:20px;
    right:40px;
    font-size:13px;
}
h3,h4{
    text-align:center;
    margin:0;
    font-weight:bold;   /* 🔥 BOLD HEADINGS */
}

table{width:100%;border-collapse:collapse}
th,td{border:1px solid #000;padding:6px;font-size:14px}

.no-border td{border:none}
.center{text-align:center}
.right{text-align:right}
.mt-20{margin-top:20px}
.mt-40{margin-top:40px}

/* editable fields */
.edit{
    border:none;
    width:200px;
    font-family:"Times New Roman";
}
.edit:focus{outline:none}

@media print{
    .no-print{display:none}
    .edit{border:none}
}
</style>
</head>

<body>

<div class="top-note">In lieu of IAFZ-2096</div>

<h3><b>LOAN RENEWAL VOUCHER – <?= date('Y') ?></b></h3>
<h4><b>ISSUE, RECEIPT & EXPENSE VOUCHER</b></h4>

<!-- IV / RV BLOCK -->
<table class="no-border mt-20">
<tr>
<td width="50%" valign="top">
    <b>IV No :</b> <input class="edit"><br>
    <b>Unit :</b> <input class="edit"><br>
    <b>Stn :</b> <input class="edit"><br>
    <b>Date :</b> <?= date('d-m-Y',strtotime($voucher['created_at'])) ?>
</td>

<td width="50%" valign="top" class="right">
    <div style="display:inline-block;text-align:left">
        <b>RV No :</b> <input class="edit"><br>
        <b>Unit :</b> <input class="edit"><br>
        <b>Stn :</b> <input class="edit"><br>
        <b>Date :</b> <?= date('d-m-Y',strtotime($voucher['created_at'])) ?>
    </div>
</td>
</tr>
</table>

<p class="center mt-20">
<b>ISSUED TO : <?= strtoupper($voucher['issued_to']) ?></b>
</p>

<p class="center">
<b>AUTH :</b> <input class="edit" style="width:300px">
</p>

<!-- ITEMS TABLE -->
<table class="mt-20">
<thead>
<tr class="center">
    <th width="5%">SER<br>NO</th>
    <th width="12%">JOB /NAR No</th>
    <th width="28%">NOMENCLATURE</th>
    <th width="7%">A/U</th>
    <th width="7%">QTY</th>
    <th width="12%" class="center">COST OF EACH</th>
    <th width="12%" class="center">TOTAL AMOUNT</th>
    <th width="17%">REMARKS</th>
</tr>
</thead>
<tbody>
<?php
$i=1; 
$itemCount=0;
while($it=$resItems->fetch_assoc()){
$itemCount++;
echo "
<tr>
<td class='center'>$i</td>
<td class='center'>{$it['job_no']}</td>
<td>{$it['equipment_name']}</td>
<td class='center'>{$it['au']}</td>
<td class='center'>{$it['qty']}</td>
<td class='center'>".number_format($it['cost_each'],2)."</td>
<td class='center'>".number_format($it['total_amt'],2)."</td>
<td></td>
</tr>";
$i++;
}
?>
</tbody>
</table>

<p class="center mt-20"><b>( Total Items <?= $itemCount ?> only )</b></p>

<p class="center">
Pl sign and return one copy of this issue voucher to this office duly receipted.
</p>

<p><b>Note :</b> <input class="edit" style="width:80%"></p>

<!-- SIGNATURE -->
<table class="no-border mt-40 center">
<tr>
<td width="33%"><b>ISSUED BY</b></td>
<td width="34%"><b>COLLECTED / DEPOSITED BY</b></td>
<td width="33%"><b>RECEIVED BY</b></td>
</tr>

<tr>
<td></td>
<td>
    No : <input class="edit"><br><br>
    Rank : <input class="edit"><br><br>
    Name : <input class="edit"><br><br>
    Sig : <input class="edit"><br><br>
    Dt : <input class="edit">
</td>
<td></td>
</tr>
</table>

<div class="no-print center mt-40">
<button onclick="window.print()" class="btn btn-primary">PRINT</button>
<a href="opwks_issue_voucher_list.php" class="btn btn-secondary">BACK</a>
</div>

</body>
</html>
