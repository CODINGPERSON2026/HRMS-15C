<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

$id = intval($_GET['id'] ?? 0);
if(!$id){
    die("Invalid Board ID");
}

/* FETCH BOARD */
$stmt = $connect->prepare("SELECT * FROM ord_board WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows === 0){
    die("Board not found");
}

$data = $res->fetch_assoc();

/* 🔀 ROUTE BASED ON BOARD TYPE */
if($data['board_type'] === 'GRANTS'){
    include "view_board_grants.php";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>View Board of Officers</title>

<style>
@page{ size:A4; margin:20mm; }

body{
    font-family: Arial, sans-serif;
    background:#f5f5f5;
}

/* 🔥 TOP BUTTON BAR */
.btn-bar{
    text-align:center;
    margin:20px;
}
.btn{
    padding:8px 14px;
    font-size:14px;
    border:none;
    cursor:pointer;
    margin:0 5px;
}
.print{ background:#198754; color:#fff; }
.back{ background:#6c757d; color:#fff; }

@media print{
    .btn-bar{ display:none; }
    body{ background:#fff; }
}

.page{
    width:210mm;
    min-height:297mm;
    margin:auto;
    background:#fff;
    padding:25mm;
    font-size:13.5px;
    line-height:1.6;
    color:#000;
}

.top-right{
    text-align:right;
    font-weight:bold;
    text-decoration:underline;
    margin-bottom:35px;
}

/* TABLE */
.table{
    width:100%;
    border-collapse:collapse;
}
.table td{
    padding:8px 4px;
    vertical-align:top;
}
.left{width:35%;}
.mid{width:3%; text-align:center;}
.right{width:62%;}

/* POINTS */
ol{
    margin-top:25px;
    padding-left:18px;
}
ol li{
    margin-bottom:14px;
}

/* SIGNATURE */
.signature{
    margin-top:40px;
}
.sig-line{
    width:60%;
    border-bottom:1px solid #000;
    margin:12px 0 6px;
}
.sig-text{
    font-size:13px;
}

/* COUNTERSIGNED */
.center{
    text-align:center;
    font-weight:bold;
    text-decoration:underline;
    margin:40px 0 30px;
}

/* FOOTER */
.footer div{
    margin-bottom:8px;
}
</style>
</head>

<body>

<!-- 🔥 TOP PRINT / BACK -->
<div class="btn-bar">
    <button class="btn print" onclick="window.print()">🖨 Print</button>
    <a href="board_list.php"><button class="btn back">⬅ Back</button></a>
</div>

<div class="page">

<div class="top-right">In Lieu of IAFD-931</div>

<table class="table">
<tr>
<td class="left">Proceedings of</td><td class="mid">:</td>
<td class="right"><?= htmlspecialchars($data['proceedings_of']) ?></td>
</tr>

<tr>
<td class="left">Assembled at</td><td class="mid">:</td>
<td class="right"><?= htmlspecialchars($data['assembled_at']) ?></td>
</tr>

<tr>
<td class="left">On the day of</td><td class="mid">:</td>
<td class="right"><?= htmlspecialchars($data['on_day_text']) ?></td>
</tr>

<tr>
<td class="left">By the order of</td><td class="mid">:</td>
<td class="right"><?= htmlspecialchars($data['order_of']) ?></td>
</tr>

<tr>
<td class="left">For the purpose of</td><td class="mid">:</td>
<td class="right"><?= nl2br(htmlspecialchars($data['purpose'])) ?></td>
</tr>

<tr>
<td class="left">Presiding Officer</td><td class="mid">:</td>
<td class="right"><?= htmlspecialchars($data['presiding_officer']) ?></td>
</tr>

<tr>
<td class="left">Members 1.</td><td class="mid">:</td>
<td class="right"><?= htmlspecialchars($data['member1']) ?></td>
</tr>

<tr>
<td class="left">Members 2.</td><td class="mid">:</td>
<td class="right"><?= htmlspecialchars($data['member2']) ?></td>
</tr>
</table>

<ol>
<li><?= nl2br(htmlspecialchars($data['point1'])) ?></li>
<li><?= nl2br(htmlspecialchars($data['point2'])) ?></li>
<li><?= nl2br(htmlspecialchars($data['point3'])) ?></li>
</ol>

<div class="signature">
<table class="table">
<tr>
<td class="left">Presiding Officer</td><td class="mid">:</td>
<td class="right">
<div class="sig-line"></div>
<span class="sig-text">( <?= htmlspecialchars($data['po_sign']) ?> )</span>
</td>
</tr>

<tr>
<td class="left">Members 1.</td><td class="mid">:</td>
<td class="right">
<div class="sig-line"></div>
<span class="sig-text">( <?= htmlspecialchars($data['m1_sign']) ?> )</span>
</td>
</tr>

<tr>
<td class="left">Members 2.</td><td class="mid">:</td>
<td class="right">
<div class="sig-line"></div>
<span class="sig-text">( <?= htmlspecialchars($data['m2_sign']) ?> )</span>
</td>
</tr>
</table>
</div>

<div class="center">COUNTERSIGNED</div>

<div class="footer">
<div>Station : <?= htmlspecialchars($data['station']) ?></div>
<div>
Dated :
<?= $data['dated'] ? date("d M Y", strtotime($data['dated'])) : '' ?>
</div>
</div>

</div>
</body>
</html>
