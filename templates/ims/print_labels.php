<?php
require_once "connect.php";

$result = $connect->query("
    SELECT id
    FROM equipment_master
    WHERE qty_available > 0
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Barcode Only Labels</title>

<style>
@page { size:A4; margin:8mm; }

body{
    font-family:Arial, sans-serif;
}

.print-btn{
    margin-bottom:10px;
}

.sheet{
    display:flex;
    flex-wrap:wrap;
    gap:5mm;
}

/* 🔥 ONLY BARCODE LABEL */
.label{
    width:50mm;
    height:25mm;
    border:1px solid #000;
    display:flex;
    align-items:center;
    justify-content:center;
}

/* Make barcode bigger */
.label img{
    height:45px;
}

@media print{
    .print-btn{ display:none; }
}
</style>
</head>
<body>

<button class="print-btn" onclick="window.print()">🖨 Print Labels</button>

<div class="sheet">

<?php while($row = $result->fetch_assoc()): 
$barcode = "EQP".$row['id'];
?>

<div class="label">
    <img src="barcode.php?code=<?= $barcode ?>">
</div>

<?php endwhile; ?>

</div>

</body>
</html>