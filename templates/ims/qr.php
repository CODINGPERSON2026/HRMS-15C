<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/qr_lib/phpqrcode.php";

if (!isset($_GET['data']) || empty($_GET['data'])) {
    echo "No Data";
    exit;
}

$data = $_GET['data'];

header("Content-Type: image/png");

QRcode::png($data, false, QR_ECLEVEL_L, 5, 2);
?>
