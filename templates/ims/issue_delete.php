<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

$id = (int)$_GET['id'];

/* GET ISSUE */
$q = mysqli_query($connect,"
SELECT equipment_id, qty
FROM equipment_txn
WHERE id=$id AND txn_type='ISSUE'
");

if(mysqli_num_rows($q)==0){
    die("Invalid Issue Entry");
}
$r = mysqli_fetch_assoc($q);

/* REVERSE STOCK */
mysqli_query($connect,"
UPDATE equipment_master
SET qty_available = qty_available + {$r['qty']}
WHERE id = {$r['equipment_id']}
");

/* DELETE ISSUE */
mysqli_query($connect,"
DELETE FROM equipment_txn WHERE id=$id
");

header("location:issue_list.php");
exit;
