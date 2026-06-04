<?php
session_start();
require_once "connect.php";

if (!isset($_GET['id'])) {
    die("Equipment ID missing");
}

$equipment_id = (int)$_GET['id'];

/* ===== FETCH ALL BARCODE ITEMS ===== */

$stmt = $connect->prepare("
    SELECT barcode
    FROM equipment_items
    WHERE equipment_id = ?
    ORDER BY id ASC
");

$stmt->bind_param("i", $equipment_id);
$stmt->execute();
$result = $stmt->get_result();

$barcodes = [];

while($row = $result->fetch_assoc()){
    $barcodes[] = $row['barcode'];
}

$stmt->close();

if(empty($barcodes)){
    die("No barcodes found");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Equipment Barcode Labels</title>

<style>
@page {
    size: A4;
    margin: 10mm;
}

body{
    margin:0;
    font-family:Arial;
}

/* Sticker Grid */
.label-container{
    display:grid;
    grid-template-columns: repeat(4, 1fr); /* 4 stickers per row */
    gap:12px;
}

/* Single Sticker */
.label{
    width:100%;
    height:80px;
    display:flex;
    align-items:center;
    justify-content:center;
    border:1px dashed #ccc;
}

.label img{
    height:55px;
}

/* Hide border when printing */
@media print{
    .label{
        border:none;
    }
}
</style>
</head>

<body>

<div class="label-container">

<?php foreach($barcodes as $code): ?>
    <div class="label">
        <img src="barcode.php?code=<?= urlencode($code) ?>">
    </div>
<?php endforeach; ?>

</div>

<script>
window.onload = function(){
    window.print();
}
</script>

</body>
</html>