<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=audit_logs.csv");

$out = fopen("php://output", "w");
fputcsv($out, ['Date','User','Action','Module','Description','IP']);

$where=[]; $params=[]; $types='';

if (!empty($_GET['user'])) {
    $where[]="username LIKE ?";
    $params[]="%".$_GET['user']."%";
    $types.='s';
}
if (!empty($_GET['module'])) {
    $where[]="module=?";
    $params[]=$_GET['module'];
    $types.='s';
}
if (!empty($_GET['action'])) {
    $where[]="action=?";
    $params[]=$_GET['action'];
    $types.='s';
}
if (!empty($_GET['from'])) {
    $where[]="DATE(created_at)>=?";
    $params[]=$_GET['from'];
    $types.='s';
}
if (!empty($_GET['to'])) {
    $where[]="DATE(created_at)<=?";
    $params[]=$_GET['to'];
    $types.='s';
}

$sql="SELECT * FROM audit_logs";
if($where){$sql.=" WHERE ".implode(" AND ",$where);}
$sql.=" ORDER BY id DESC";

$stmt=$connect->prepare($sql);
if($params){$stmt->bind_param($types,...$params);}
$stmt->execute();
$res=$stmt->get_result();

while($r=$res->fetch_assoc()){
    fputcsv($out,[
        $r['created_at'],
        $r['username'],
        $r['action'],
        $r['module'],
        $r['description'],
        $r['ip_address']
    ]);
}
fclose($out);
exit;
