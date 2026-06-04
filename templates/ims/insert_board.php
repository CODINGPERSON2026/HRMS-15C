<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* METHOD CHECK */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location:add_board.php");
    exit;
}

/* HELPER */
function clean($key){
    return trim($_POST[$key] ?? '');
}

/* BOARD TYPE */
$board_type = clean('board_type');   // ORD | OPWKS | GRANTS

/* COMMON FIELDS */
$proceedings_of    = clean('proceedings_of');
$assembled_at      = clean('assembled_at');
$on_day_text       = clean('on_day_text');
$order_of          = clean('order_of');
$purpose           = clean('purpose');

$presiding_officer = clean('presiding_officer');
$member1           = clean('member1');
$member2           = clean('member2');

$point1            = clean('point1');
$point2            = clean('point2');
$point3            = clean('point3');

$po_sign           = clean('po_sign');
$m1_sign           = clean('m1_sign');
$m2_sign           = clean('m2_sign');

$station           = clean('station');
$dated             = clean('dated');

$created_by        = $_SESSION['id'];

/* GRANTS ONLY */
$grants_table   = null;
$recommendation = null;

if ($board_type === 'GRANTS') {
    $grants_table   = clean('grants_table');     // JSON string
    $recommendation = clean('recommendations');
}

/* REQUIRED CHECK */
if (
    $board_type === '' ||
    $assembled_at === '' ||
    $on_day_text === '' ||
    $purpose === ''
) {
    $_SESSION['error'] = "❌ Required fields missing";
    header("Location:add_board.php");
    exit;
}

/* INSERT */
$sql = "
INSERT INTO ord_board (
    board_type,
    proceedings_of,
    assembled_at,
    on_day_text,
    order_of,
    purpose,
    presiding_officer,
    member1,
    member2,
    point1,
    point2,
    point3,
    grants_table,
    recommendations,
    po_sign,
    m1_sign,
    m2_sign,
    station,
    dated,
    created_by,
    created_at
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, ?, NOW())
";

$stmt = $connect->prepare($sql);
if(!$stmt){
    die("Prepare failed : ".$connect->error);
}

$stmt->bind_param(
    "sssssssssssssssssssi",
    $board_type,
    $proceedings_of,
    $assembled_at,
    $on_day_text,
    $order_of,
    $purpose,
    $presiding_officer,
    $member1,
    $member2,
    $point1,
    $point2,
    $point3,
    $grants_table,
    $recommendation,
    $po_sign,
    $m1_sign,
    $m2_sign,
    $station,
    $dated,
    $created_by
);

/* EXECUTE */
if($stmt->execute()){
    $_SESSION['success'] = "✅ Board added successfully";
    header("Location: board_list.php");
}else{
    $_SESSION['error'] = "❌ Insert failed : ".$stmt->error;
    header("Location:add_board.php");
}

$stmt->close();
$connect->close();
exit;
