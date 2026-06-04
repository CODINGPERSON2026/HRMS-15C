<?php
session_start();
require_once "auth.php";
require_user();
require_once "connect.php";

if (!isset($_SESSION['id'])) {
    header("location:logout.php");
    exit;
}

$user_id = $_SESSION['id'];

/* ===== FETCH USER RETURN VOUCHERS WITH STATUS ===== */
    $sql = "
    SELECT
        rv.id,
        rv.rv_no,
        rv.rv_date,
        rv.returned_from,
        rv.auth_text,
        rv.created_at,
        rr.status
    FROM return_vouchers rv
    LEFT JOIN return_requests rr ON rr.id = rv.request_id
    WHERE rv.created_by = ?
    ORDER BY rv.id DESC
    ";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
?>

<!DOCTYPE html>
<html>
<head>
<title>My Return Vouchers</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:25px;
    font-family:Segoe UI;
}

.card{
    max-width:1200px;
    margin:auto;
    background:#fff;
    padding:20px;
    border-radius:15px;
    box-shadow:0 6px 15px rgba(0,0,0,.15);
}

.table th{
    background:#f1f1f1;
    text-align:center;
}

.badge{
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:700;
}

.bg-success{
    background:#16a34a;
    color:#fff;
}

.bg-danger{
    background:#dc2626;
    color:#fff;
}

.bg-warning{
    background:#facc15;
    color:#000;
}
</style>
</head>

<body>

<div class="card">
<h4 class="text-center mb-3">📦 MY RETURN VOUCHERS</h4>
<hr>

<table class="table table-bordered table-sm text-center align-middle">
<thead>
<tr>
    <th>SER NO</th>
    <th>RV NO</th>
    <th>RV DATE</th>
    <th>RETURNED FROM</th>
    <th>AUTHORITY</th>
    <th>CREATED AT</th>
    <th>STATUS</th>
    <th>ACTION</th>
</tr>
</thead>
<tbody>

<?php
$i = 1;

if ($result->num_rows == 0) {
    echo "<tr>
            <td colspan='8' class='text-danger fw-bold'>
                No Return Vouchers Found
            </td>
          </tr>";
}

while ($row = $result->fetch_assoc()) {

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
    <td><b><?= htmlspecialchars($row['rv_no']) ?></b></td>
    <td><?= date('d-m-Y', strtotime($row['rv_date'])) ?></td>
    <td><?= htmlspecialchars($row['returned_from']) ?></td>
    <td><?= htmlspecialchars($row['auth_text']) ?></td>
    <td><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
    <td><?= $badge ?></td>
    <td>
        <a href="return_voucher_view.php?id=<?= $row['id'] ?>"
           class="btn btn-success btn-sm">
           View / Print
        </a>
    </td>
</tr>
<?php } ?>

</tbody>
</table>

<a href="user_dboard.php" class="btn btn-secondary mt-3">⬅ Back</a>

</div>
</body>
</html>