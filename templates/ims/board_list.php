<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* FETCH ALL BOARDS */
$sql = "
SELECT 
    id,
    board_type,
    assembled_at,
    on_day_text,
    purpose,
    station,
    dated,
    created_at
FROM ord_board
ORDER BY id DESC
";
$result = $connect->query($sql);

/* DATE FORMAT */
function formatDateDMY($date){
    if(!$date || $date === '0000-00-00') return '';
    return date("d-m-Y", strtotime($date));
}
?>
<!DOCTYPE html>
<html>
<head>
<title>ASTB BOARD LIST</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:20px;
    font-family:Segoe UI;
}
.card{
    max-width:1250px;
    margin:auto;
    padding:24px;
    border-radius:14px;
    background:#fff;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
}
.table th{
    background:#003366;
    color:#fff;
    font-size:14px;
    text-align:center;
    vertical-align:middle;
}
.table td{
    font-size:13px;
    vertical-align:top;
}
.small-text{
    font-size:12px;
    color:#444;
}
.badge{
    font-size:12px;
    padding:6px 10px;
}
.actions a{
    margin:2px;
}
.top-actions{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
}
</style>
</head>

<body>

<div class="card">

<h3 class="text-center mb-3">📋 ASTB BOARD LIST</h3>

<!-- SUCCESS / ERROR -->
<?php if(!empty($_SESSION['success'])){ ?>
<div class="alert alert-success">
<?= $_SESSION['success']; unset($_SESSION['success']); ?>
</div>
<?php } ?>

<?php if(!empty($_SESSION['error'])){ ?>
<div class="alert alert-danger">
<?= $_SESSION['error']; unset($_SESSION['error']); ?>
</div>
<?php } ?>

<!-- 🔥 TOP BUTTONS (MOVED HERE) -->
<div class="top-actions">
    <a href="add_board.php" class="btn btn-success">➕ Add New Board</a>
    <a href="dboard.php" class="btn btn-secondary">⬅ Back</a>
</div>

<table class="table table-bordered table-striped">
<thead>
<tr>
    <th style="width:5%">S.NO</th>
    <th style="width:8%">BOARD TYPE</th>
    <th style="width:18%">ASSEMBLED AT</th>
    <th style="width:14%">ON THE DAY OF</th>
    <th>PURPOSE</th>
    <th style="width:10%">STATION</th>
    <th style="width:9%">DATED</th>
    <th style="width:12%">CREATED AT</th>
    <th style="width:15%">ACTIONS</th>
</tr>
</thead>

<tbody>
<?php
$i = 1;
if($result && $result->num_rows > 0){
while($row = $result->fetch_assoc()){

    /* VIEW PAGE BASED ON BOARD TYPE */
    $viewPage = ($row['board_type'] === 'GRANTS')
        ? 'view_board_grants.php'
        : 'view_board.php';
?>
<tr>
<td class="text-center"><?= $i++ ?></td>

<td class="text-center">
<?php
if($row['board_type'] === 'ORD'){
    echo '<span class="badge bg-primary">ORD</span>';
}elseif($row['board_type'] === 'OPWKS'){
    echo '<span class="badge bg-warning text-dark">OPWKS</span>';
}elseif($row['board_type'] === 'GRANTS'){
    echo '<span class="badge bg-success">GRANTS</span>';
}else{
    echo '<span class="badge bg-secondary">N/A</span>';
}
?>
</td>

<td><?= htmlspecialchars($row['assembled_at']) ?></td>
<td><?= htmlspecialchars($row['on_day_text']) ?></td>

<td class="small-text">
<?= nl2br(htmlspecialchars(substr($row['purpose'],0,140))) ?>
<?= strlen($row['purpose']) > 140 ? '...' : '' ?>
</td>

<td><?= htmlspecialchars($row['station']) ?></td>
<td><?= formatDateDMY($row['dated']) ?></td>
<td class="small-text"><?= htmlspecialchars($row['created_at']) ?></td>

<td class="actions text-center">
    <a href="<?= $viewPage ?>?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">👁 View</a>
    <a href="<?= $viewPage ?>?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-secondary">🖨 Print</a>
    <a href="edit_board.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">✏ Edit</a>
    <a href="delete_board.php?id=<?= $row['id'] ?>"
       class="btn btn-sm btn-danger"
       onclick="return confirm('Are you sure you want to delete this Board?')">
       🗑 Delete
    </a>
</td>
</tr>
<?php
}
}else{
?>
<tr>
<td colspan="9" class="text-center text-muted">
No Boards Found
</td>
</tr>
<?php } ?>
</tbody>
</table>

</div>

</body>
</html>
