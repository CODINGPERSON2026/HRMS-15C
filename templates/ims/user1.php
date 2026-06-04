<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* ================= ACTION HANDLER ================= */

if (isset($_GET['action']) && isset($_GET['id'])) {

    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {

        $stmt = $connect->prepare("UPDATE users SET status='ACTIVE' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['flash'] = "User approved successfully";

    } elseif ($action === 'reject') {

        $stmt = $connect->prepare("UPDATE users SET status='REJECTED' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['flash'] = "User rejected successfully";

    } elseif ($action === 'delete') {

        $stmt = $connect->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $_SESSION['flash'] = "User deleted successfully";
    }

    header("Location: user1.php");
    exit;
}

/* ================= FETCH USERS ================= */

$result = $connect->query("SELECT * FROM users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html>
<head>
<title>User Approval</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:linear-gradient(135deg,#eef2f7,#dbe7ff);
    font-family:Segoe UI, Arial, sans-serif;
}

/* Main Card 3D */
.card-box{
    background:#ffffff;
    padding:30px;
    border-radius:18px;
    box-shadow:
        0 20px 40px rgba(0,0,0,0.15),
        inset 0 1px 0 rgba(255,255,255,0.6);
    transition:all 0.3s ease;
}

.card-box:hover{
    transform:translateY(-3px);
    box-shadow:
        0 30px 60px rgba(0,0,0,0.18);
}

/* Title */
.page-title{
    text-align:center;
    font-weight:700;
    margin-bottom:25px;
    letter-spacing:1px;
    color:#2c3e50;
}

/* Table 3D Style */
.table{
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
}

thead th{
    background:linear-gradient(135deg,#1e3c72,#2a5298);
    color:#fff !important;
    text-align:center;
    font-weight:600;
    padding:12px;
    border:none;
}

tbody tr{
    transition:all 0.2s ease;
}

tbody tr:hover{
    background:#f1f6ff;
    transform:scale(1.01);
}

/* Cells */
td{
    vertical-align:middle !important;
    font-size:15px;
}


/* COLUMN WIDTHS */
thead th:nth-child(1){
    font-weight:700;   /* header bold */
    text-align:center;
     width:240px;
}
tbody td:nth-child(1){
    font-weight:400;   /* text normal */
    text-align:center;
    width:240px;
}
thead th:nth-child(2){
    font-weight:700;   /* header bold */
    text-align:center;
     width:240px;
}
tbody td:nth-child(2){
    font-weight:400;   /* text normal */
    text-align:center;
    width:240px;
}
 
      thead th:nth-child(3){
    font-weight:700;   /* header bold */
    text-align:center;
     width:240px;
}
tbody td:nth-child(3){
    font-weight:400;   /* text normal */
    text-align:center;
    width:240px;
}
thead th:nth-child(4){
    font-weight:700;   /* header bold */
    text-align:center;
     width:240px;
}
tbody td:nth-child(4){
    font-weight:400;   /* text normal */
    text-align:center;
    width:240px;
}
thead th:nth-child(5){
    font-weight:700;   /* header bold */
    text-align:center;
     width:240px;
}
tbody td:nth-child(5){
    font-weight:400;   /* text normal */
    text-align:center;
    width:240px;
}
thead th:nth-child(6){
    font-weight:700;   /* header bold */
    text-align:center;
     width:240px;
}
tbody td:nth-child(6){
    font-weight:400;   /* text normal */
    text-align:left;
    width:240px;
}

/* Status Badges */
.status-active{
    background:#d1e7dd;
    color:#0f5132;
    padding:5px 12px;
    border-radius:20px;
    font-weight:600;
}

.status-rejected{
    background:#f8d7da;
    color:#842029;
    padding:5px 12px;
    border-radius:20px;
    font-weight:600;
}

.status-pending{
    background:#fff3cd;
    color:#664d03;
    padding:5px 12px;
    border-radius:20px;
    font-weight:600;
}

/* Buttons 3D */
.btn{
    border-radius:25px;
    padding:6px 16px;
    font-weight:500;
    transition:all 0.2s ease;
}

.btn-success{
    background:linear-gradient(135deg,#28a745,#218838);
    border:none;
}

.btn-danger{
    background:linear-gradient(135deg,#dc3545,#b02a37);
    border:none;
}

.btn-secondary{
    background:linear-gradient(135deg,#6c757d,#495057);
    border:none;
}

.btn:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(0,0,0,0.15);
}

/* Back button styling */
.container > a.btn{
    border-radius:30px;
    padding:8px 18px;
}
</style>
</head>

<body>

<div class="container mt-4">

<a href="dboard.php" class="btn btn-secondary mb-3">← Back</a>

<div class="card-box">

<h3 class="page-title">USER REQUEST / APPROVAL</h3>

<?php if(isset($_SESSION['flash'])){ ?>
<div class="alert alert-success text-center">
    <?= $_SESSION['flash']; ?>
</div>
<?php unset($_SESSION['flash']); } ?>

<table class="table table-bordered mt-3">
<thead class="table-dark">
<tr>
    <th>ID</th>
    <th>USER ID</th>
    <th>USERNAME</th>
    <th>ROLE</th>
    <th>STATUS</th>
    <th>ACTION</th>
</tr>
</thead>

<tbody>
<?php while($row = $result->fetch_assoc()){ ?>
<tr>
    <td class="text-center"><?= $row['id'] ?></td>

    <td class="text-left"><?= $row['unit'] ?: '-' ?></td>
    <td class="text-left"><?= htmlspecialchars($row['username']) ?></td>

    <td class="text-center"><?= $row['role'] ?></td>

    <td class="text-center">
        <span class="<?=
            $row['status']=='ACTIVE' ? 'status-active' :
            ($row['status']=='REJECTED' ? 'status-rejected' : 'status-pending')
        ?>">
            <?= $row['status'] ?: 'PENDING' ?>
        </span>
    </td>

    <td class="text-center action-btns">
        <?php if(empty($row['status']) || $row['status']==='PENDING'){ ?>
            <a href="?id=<?= $row['id'] ?>&action=approve"
               class="btn btn-success btn-sm">Approve</a>

            <a href="?id=<?= $row['id'] ?>&action=reject"
               class="btn btn-danger btn-sm">Reject</a>
        <?php } ?>

        <a href="?id=<?= $row['id'] ?>&action=delete"
           class="btn btn-secondary btn-sm"
           onclick="return confirm('Delete this user?')">
           Delete
        </a>
    </td>
</tr>
<?php } ?>
</tbody>
</table>

</div>
</div>

</body>
</html>
