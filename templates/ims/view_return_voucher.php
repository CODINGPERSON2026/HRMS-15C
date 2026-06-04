<?php
session_start();
require_once "connect.php";

/* 🔐 ADMIN GUARD */
if (
    !isset($_SESSION['id']) ||
    !in_array($_SESSION['role'], ['admin','super_admin'])
) {
    header("location:logout.php");
    exit;
}

/* ===== FETCH SAVED RETURN VOUCHERS ===== */
$sql = "
SELECT
    rv.id,
    rv.rv_no,
    rv.rv_date,
    rv.returned_from,
    rv.auth_text,
    rv.created_at,
    rv.status,
    u.username
FROM return_vouchers rv
LEFT JOIN users u ON u.id = rv.created_by
ORDER BY rv.id DESC
";

$result = mysqli_query($connect, $sql);
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin – Saved Return Vouchers</title>

<link rel="stylesheet" href="css/bootstrap.min.css">

<style>

body{
    background:linear-gradient(135deg,#eef5ff,#dbeafe);
    padding:30px;
    font-family:'Segoe UI',sans-serif;
}

/* ===== TOP BUTTON AREA ===== */
.top-box{
    max-width:1700px;
    margin:auto auto 25px auto;
    display:flex;
    justify-content:flex-start;
}

.return-btn{
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:#fff;
    text-decoration:none;
    padding:15px 30px;
    border-radius:15px;
    font-size:17px;
    font-weight:700;
    box-shadow:0 6px 20px rgba(37,99,235,.35);
    transition:0.3s;
    border:2px solid #fff;
}

.return-btn:hover{
    background:linear-gradient(135deg,#1d4ed8,#1e40af);
    transform:translateY(-2px) scale(1.02);
    color:#fff;
}

/* ===== MAIN CARD ===== */
.card{
    width:99%;
    max-width:1700px;
    margin:auto;
    background:#fff;
    padding:30px;
    border-radius:22px;
    box-shadow:0 10px 30px rgba(0,0,0,.12);
    animation:fadeIn .4s ease-in-out;
}

h4{
    font-weight:800;
    color:#1e3a8a;
    letter-spacing:.5px;
    font-size:32px;
}

/* ===== TABLE ===== */
.table-responsive{
    width:100%;
    overflow-x:auto;
}

.table{
    width:100%;
    min-width:1600px;
    margin-top:20px;
    border-radius:15px;
    overflow:hidden;
}

.table th{
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:#fff;
    text-align:center;
    vertical-align:middle;
    padding:16px;
    font-size:15px;
    white-space:nowrap;
}

.table td{
    padding:14px;
    font-size:14px;
    vertical-align:middle;
    white-space:nowrap;
}

.table tbody tr:nth-child(even){
    background:#f8fbff;
}

.table tbody tr:hover{
    background:#eaf2ff;
    transition:.25s;
}

/* ===== STATUS BADGES ===== */
.badge{
    padding:8px 15px;
    border-radius:25px;
    font-size:12px;
    font-weight:700;
    letter-spacing:.5px;
}

.bg-success{
    background:#16a34a !important;
    color:#fff !important;
}

.bg-danger{
    background:#dc2626 !important;
    color:#fff !important;
}

.bg-warning{
    background:#facc15 !important;
    color:#000 !important;
}

/* ===== BUTTONS ===== */
.btn{
    border-radius:10px;
    font-size:13px;
    font-weight:700;
    padding:8px 14px;
    transition:.25s;
}

.btn-success{
    background:#16a34a;
    border:none;
}

.btn-success:hover{
    background:#15803d;
    transform:translateY(-1px);
}

.btn-danger{
    background:#dc2626;
    border:none;
}

.btn-danger:hover{
    background:#b91c1c;
    transform:translateY(-1px);
}

.btn-secondary{
    padding:10px 22px;
    font-size:15px;
    border-radius:10px;
    margin-top:18px;
}

/* ===== ANIMATION ===== */
@keyframes fadeIn{
    from{
        opacity:0;
        transform:translateY(15px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

</style>
</head>

<body>

<!-- ===== TOP BUTTON ===== -->
<div class="top-box">

    <a href="returned_equipment_list.php" class="return-btn">
        🔁 Returned Equipment List
    </a>

</div>

<!-- ===== MAIN CARD ===== -->
<div class="card">

<h4 class="text-center mb-4">
    📦 SAVED RETURN VOUCHERS
</h4>

<hr>

<div class="table-responsive">

<table class="table table-bordered table-hover text-center align-middle">

<thead>
<tr>
    <th>SER NO</th>
    <th>RV NO</th>
    <th>RV DATE</th>
    <th>RETURNED FROM</th>
    <th>AUTHORITY</th>
    <th>CREATED BY</th>
    <th>CREATED AT</th>
    <th>STATUS</th>
    <th>ACTION</th>
</tr>
</thead>

<tbody>

<?php
$i = 1;

if(mysqli_num_rows($result) == 0){

    echo "
    <tr>
        <td colspan='9' class='text-danger fw-bold'>
            No Return Vouchers Found
        </td>
    </tr>
    ";
}

while($row = mysqli_fetch_assoc($result)){

    $status = strtoupper($row['status'] ?? 'PENDING');

    if($status == 'APPROVED'){
        $badge = "<span class='badge bg-success'>APPROVED</span>";
    }
    elseif($status == 'REJECTED'){
        $badge = "<span class='badge bg-danger'>REJECTED</span>";
    }
    else{
        $badge = "<span class='badge bg-warning'>PENDING</span>";
    }

?>

<tr>

    <td><?= $i++ ?></td>

    <td>
        <b>
            <?= htmlspecialchars($row['rv_no'] ?? '-') ?>
        </b>
    </td>

    <td>
        <?= !empty($row['rv_date']) 
            ? date('d-m-Y', strtotime($row['rv_date'])) 
            : '-' ?>
    </td>

    <td>
        <?= htmlspecialchars($row['returned_from'] ?? '-') ?>
    </td>

    <td>
        <?= htmlspecialchars($row['auth_text'] ?? '-') ?>
    </td>

    <td>
        <?= htmlspecialchars($row['username'] ?? '-') ?>
    </td>

    <td>
        <?= !empty($row['created_at']) 
            ? date('d-m-Y H:i', strtotime($row['created_at'])) 
            : '-' ?>
    </td>

    <td>
        <?= $badge ?>
    </td>

    <td>

        <a href="print_return_voucher.php?id=<?= $row['id'] ?>"
           target="_blank"
           class="btn btn-success btn-sm">
           👁 View
        </a>

        <?php if($status == 'PENDING'){ ?>

            <a href="delete_return_voucher.php?id=<?= $row['id'] ?>"
               onclick="return confirm('Are you sure to delete this voucher?')"
               class="btn btn-danger btn-sm">
               🗑 Delete
            </a>

        <?php } ?>

    </td>

</tr>

<?php } ?>

</tbody>
</table>

</div>

<a href="dboard.php" class="btn btn-secondary">
    ⬅ Back
</a>

</div>

</body>
</html>