<?php
require_once "connect.php";

$id = $_GET['id'] ?? 0;

$stmt = $connect->prepare("
SELECT em.equipment_name,
       em.lp_no,
       em.cat_part_no,
       ei.barcode
FROM equipment_items ei
JOIN equipment_master em ON ei.equipment_id = em.id
WHERE ei.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) exit;
?>

<!DOCTYPE html>
<html>
<head>
<style>
body{ margin:0; font-family:Arial; }

.label{
    width:60mm;
    height:35mm;
    border:1px solid #000;
    padding:5px;
    box-sizing:border-box;
}

.small{
    font-size:10px;
}

.qr{
    text-align:center;
    margin-top:3px;
}
</style>
</head>

<body onload="window.print()">

<div class="label">

<div class="small">
<b><?= $data['equipment_name'] ?></b><br>
LP: <?= $data['lp_no'] ?><br>
Cat: <?= $data['cat_part_no'] ?>
</div>

<div class="qr">
echo '<img src="qr.php?data='.urlencode($qrData).'" width="100">';
    "Equipment: ".$data['equipment_name'].
    "\nLP: ".$data['lp_no'].
    "\nCat: ".$data['cat_part_no'].
    "\nBarcode: ".$data['barcode']
) ?>" width="100">
</div>

</div>

</body>
</html>