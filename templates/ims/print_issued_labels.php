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
    FROM voucher_master
    WHERE id = ?
");
$stmt->bind_param("i", $voucher_id);
$stmt->execute();
$voucher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$voucher) {
    echo "Invalid Voucher ID: ".$voucher_id;
    exit;
}

/* ================= FETCH ITEMS ================= */
$stmt = $connect->prepare("
    SELECT *
    FROM voucher_items
    WHERE voucher_id = ?
");
$stmt->bind_param("i", $voucher_id);
$stmt->execute();
$res = $stmt->get_result();

$labels = [];

while($row = $res->fetch_assoc()){

    if(!empty($row['barcode'])){

        $barcode_array = json_decode($row['barcode'], true);

        if(is_array($barcode_array)){
            foreach($barcode_array as $code){

                // 🔥 OFFLINE FULL DETAILS
                $qrData =
                "EQUIPMENT DETAILS\n".
                "-------------------------\n".
                "Barcode: ".$code."\n".
                "Equipment: ".$row['nomenclature']."\n".
                "LP No: ".$row['lp_no']."\n".
                "Cat No: ".$row['cat_part_no']."\n".
                "Issued To: ".$voucher['issued_to']."\n".
                "Date: ".date('d-m-Y');

                $labels[] = $qrData;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Print Issued Labels</title>

<style>
body{
    font-family:Arial;
}

.label-box{
    width:150px;
    height:150px;
    border:1px solid #000;
    float:left;
    margin:8px;
    text-align:center;
    padding:8px;
}

.label-box img{
    width:120px;
    height:120px;
}

@media print{
    button{ display:none; }
}
</style>
</head>

<body>

<button onclick="window.print()">🖨 Print</button>
<hr>

<?php foreach($labels as $data): ?>

<div class="label-box">

<?php
// 🔥 Only QR with full data inside
echo '<img src="qr.php?data='.urlencode($data).'">';
?>

</div>

<?php endforeach; ?>

</body>
</html>