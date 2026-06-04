<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* ===============================
   METHOD + ID CHECK
================================ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: board_list.php");
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    die("Invalid Board ID");
}

/* ===============================
   FETCH OLD SIGNATURE DATA (FIX)
================================ */
$oldStmt = $connect->prepare(
    "SELECT po_sign, m1_sign, m2_sign FROM ord_board WHERE id=?"
);
$oldStmt->bind_param("i", $id);
$oldStmt->execute();
$old = $oldStmt->get_result()->fetch_assoc();

/* ===============================
   SAFE INPUT HELPER
================================ */
function clean($key){
    return trim($_POST[$key] ?? '');
}

/* ===============================
   COMMON FIELDS
================================ */
$board_type        = clean('board_type');

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

/* 🔥 SIGNATURES – SAFE MERGE */
$po_sign = clean('po_sign') ?: ($old['po_sign'] ?? '');
$m1_sign = clean('m1_sign') ?: ($old['m1_sign'] ?? '');
$m2_sign = clean('m2_sign') ?: ($old['m2_sign'] ?? '');

$station           = clean('station');
$dated             = $_POST['dated'] ?? null;

/* ===============================
   REQUIRED VALIDATION
================================ */
if ($assembled_at === '' || $on_day_text === '' || $purpose === '') {
    $_SESSION['error'] = "❌ Required fields missing";
    header("Location: edit_board.php?id=".$id);
    exit;
}

/* ===============================
   GRANTS EXTRA DATA
================================ */
$grants_table     = null;
$recommendations  = null;

if ($board_type === 'GRANTS') {

    $rows = [];

    if (isset($_POST['ser_no'], $_POST['grant_type_tbl'], $_POST['unsv_amt'])) {

        $count = count($_POST['ser_no']);

        for ($i = 0; $i < $count; $i++) {

            $ser   = trim($_POST['ser_no'][$i] ?? '');
            $type  = trim($_POST['grant_type_tbl'][$i] ?? '');
            $amt   = trim($_POST['unsv_amt'][$i] ?? '');
            $appx  = trim($_POST['appx'][$i] ?? '');
            $rem   = trim($_POST['remarks'][$i] ?? '');

            if ($ser === '' && $type === '') continue;

            $rows[] = [
                "ser"     => $ser,
                "type"    => $type,
                "amount"  => $amt,
                "appx"    => $appx,
                "remarks" => $rem
            ];
        }
    }

    if (!empty($rows)) {
        $grants_table = json_encode($rows, JSON_UNESCAPED_UNICODE);
    }

    $recommendations = clean('recommendation');
}

/* ===============================
   UPDATE QUERY
================================ */
$sql = "
UPDATE ord_board SET
    board_type        = ?,
    proceedings_of    = ?,
    assembled_at      = ?,
    on_day_text       = ?,
    order_of          = ?,
    purpose           = ?,
    presiding_officer = ?,
    member1           = ?,
    member2           = ?,
    point1            = ?,
    point2            = ?,
    point3            = ?,
    grants_table      = ?,
    recommendations   = ?,
    po_sign           = ?,
    m1_sign           = ?,
    m2_sign           = ?,
    station           = ?,
    dated             = ?
WHERE id = ?
";

$stmt = $connect->prepare($sql);
if (!$stmt) {
    die("Prepare failed : ".$connect->error);
}

/* ===============================
   BIND PARAMS
================================ */
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
    $recommendations,
    $po_sign,
    $m1_sign,
    $m2_sign,
    $station,
    $dated,
    $id
);

/* ===============================
   EXECUTE
================================ */
if ($stmt->execute()) {
    $_SESSION['success'] = "✅ Board updated successfully";
    header("Location: board_list.php");
    exit;
} else {
    die("❌ Update Failed : ".$stmt->error);
}

$stmt->close();
$connect->close();
