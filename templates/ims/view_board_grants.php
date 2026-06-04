<?php
require_once "auth.php";
require_admin();
require_once "connect.php";

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid Board ID");

$stmt = $connect->prepare(
    "SELECT * FROM ord_board WHERE id=? AND board_type='GRANTS'"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) die("Record not found");
$data = $res->fetch_assoc();

/* DATE FORMAT */
function fdate($d){
    if(!$d || $d==='0000-00-00') return '';
    return date('d M Y', strtotime($d));
}

/* ===== GRANTS TABLE BUILD (JSON SAFE) ===== */
$rows = [];
$total = 0;

if (!empty($data['grants_table'])) {
    $decoded = json_decode($data['grants_table'], true);

    if (is_array($decoded)) {
        foreach ($decoded as $r) {
            $ser     = htmlspecialchars($r['ser'] ?? '');
            $type    = htmlspecialchars($r['type'] ?? '');
            $amount  = floatval($r['amount'] ?? 0);
            $appx    = htmlspecialchars($r['appx'] ?? '');
            $remarks = htmlspecialchars($r['remarks'] ?? '');

            $total += $amount;

            $rows[] = "
            <tr>
                <td>$ser</td>
                <td class='left'>$type</td>
                <td>".number_format($amount,2)."</td>
                <td>$appx</td>
                <td>$remarks</td>
            </tr>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Board of Officers – GRANTS</title>

<style>
@page{ size:A4; margin:25mm; }

body{
    background:#e6e6e6;
    font-family:"Times New Roman", serif;
    font-size:13.5px;
}

.page{
    width:210mm;
    min-height:297mm;
    background:#fff;
    margin:20px auto;
    padding:25mm;
}

.heading{
    text-align:right;
    font-weight:bold;
    text-decoration:underline;
    margin-bottom:25px;
}

.row{ display:flex; margin-bottom:6px; }
.label{ width:220px; font-weight:bold; }
.colon{ width:20px; text-align:center; }
.value{ flex:1; }

.points{ margin-top:12px; }
.points div{ margin-bottom:8px; }

table{
    width:80%;
    border-collapse:collapse;
    margin-top:12px;
}
th,td{
    border:1px solid #000;
    padding:5px;
    text-align:center;
}
.left{ text-align:left; }

.page-break{ page-break-before:always; }

.sign-row{ display:flex; margin-top:26px; }
.sign-label{ width:200px; }
.sign-colon{ width:20px; }
.sign-area{ flex:1; }
.sign-line{
    border-bottom:1px solid #000;
    width:320px;
    height:14px;
}

.center{
    text-align:center;
    font-weight:bold;
    text-decoration:underline;
    margin-top:30px;
}

.btn-bar{
    text-align:center;
    margin:20px;
}
.btn{
    padding:8px 16px;
    font-size:14px;
    border:none;
    cursor:pointer;
}
.print{ background:#198754; color:#fff; }
.back{ background:#6c757d; color:#fff; }

@media print{
    .btn-bar{ display:none; }
    body{ background:#fff; }
}
</style>
</head>

<body>

<div class="btn-bar">
    <button class="btn print" onclick="window.print()">🖨 Print</button>
    <a href="board_list.php"><button class="btn back">⬅ Back</button></a>
</div>

<div class="page">

<div class="heading">In lieu of IAFD-931</div>

<div class="row"><div class="label">Proceedings of a</div><div class="colon">:</div><div class="value"><?=$data['proceedings_of']?></div></div>
<div class="row"><div class="label">Assembled at</div><div class="colon">:</div><div class="value"><?=$data['assembled_at']?></div></div>
<div class="row"><div class="label">On the day of</div><div class="colon">:</div><div class="value"><?=$data['on_day_text']?></div></div>
<div class="row"><div class="label">By the order of</div><div class="colon">:</div><div class="value"><?=$data['order_of']?></div></div>

<br>

<div class="row">
<div class="label">For the purpose of</div>
<div class="colon">:</div>
<div class="value"><?=nl2br($data['purpose'])?></div>
</div>

<br>

<div class="row"><div class="label">Presiding Offr</div><div class="colon">:</div><div class="value"><?=$data['presiding_officer']?></div></div>
<div class="row"><div class="label">Members 1.</div><div class="colon">:</div><div class="value"><?=$data['member1']?></div></div>
<div class="row"><div class="label">Members 2.</div><div class="colon">:</div><div class="value"><?=$data['member2']?></div></div>

<div class="points">
<div>1. <?=$data['point1']?></div>
<div>2. <?=$data['point2']?></div>
<div>3. <?=$data['point3']?></div>
</div>

<!-- ✅ CORRECT GRANTS TABLE -->
<table>
<tr>
<th>Ser No</th>
<th>Type of Grants</th>
<th>Amt of UNSV Items</th>
<th>Appx</th>
<th>Remarks</th>
</tr>

<?= implode('', $rows) ?>

<tr>
<th colspan="2">TOTAL</th>
<th><?=number_format($total,2)?></th>
<th colspan="2"></th>
</tr>
</table>

<div class="page-break"></div>

<div style="text-align:center;font-weight:bold;">-2-</div>

<p><b>6.&nbsp;<u>Recommendation of the bd:-</u></b></p>
<p><?=nl2br($data['recommendations'])?></p>

<div class="sign-row">
<div class="sign-label">Presiding Officer</div><div class="sign-colon">:</div>
<div class="sign-area"><div class="sign-line"></div><?=$data['po_sign']?></div>
</div>

<div class="sign-row">
<div class="sign-label">Members 1.</div><div class="sign-colon">:</div>
<div class="sign-area"><div class="sign-line"></div><?=$data['m1_sign']?></div>
</div>

<div class="sign-row">
<div class="sign-label">Members 2.</div><div class="sign-colon">:</div>
<div class="sign-area"><div class="sign-line"></div><?=$data['m2_sign']?></div>
</div>

<div class="center">VETTED BY LAO</div>

<p>
Station : <?=$data['station']?><br><br>
Dated : <?=fdate($data['dated'])?>
</p>

<div class="center">RECOMMENDED / NOT RECOMMENDED</div>

<p>
Station : <?=$data['station']?><br><br>
Dated : <?=fdate($data['dated'])?>
</p>

<div class="center">REMARKS OF COMPETENT AUTHORITY</div>
<div class="center">APPROVED / NOT APPROVED</div>

</div>
</body>
</html>
