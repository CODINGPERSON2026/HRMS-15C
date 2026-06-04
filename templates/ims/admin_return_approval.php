<?php
session_start();
require_once "auth.php";
require_admin();
require_once 'connect.php';

/* 🔐 ADMIN GUARD */
if (
    !isset($_SESSION['id']) ||
    !in_array($_SESSION['role'], ['admin','super_admin'])
) {
    header("location:logout.php");
    exit;
}

/* ================= FETCH PENDING RETURN REQUESTS ================= */
$stmt = $connect->prepare("
    SELECT
        rr.id,
        rr.store_name,
        rr.return_qty,
        rr.requested_at,
        em.equipment_name,
        em.lp_no,
        em.au,
        gm.grant_name,
        u.username
    FROM return_requests rr
    JOIN equipment_master em ON em.id = rr.equipment_id
    JOIN grants_master gm ON gm.id = rr.grant_id
    JOIN users u ON u.id = rr.requested_by
    WHERE rr.status = 'PENDING'
    ORDER BY rr.id DESC
");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Return Approval</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:25px;
    font-family:Segoe UI;
}
.card{
    max-width:1300px;
    margin:auto;
    background:#fff;
    padding:25px;
    border-radius:15px;
    box-shadow:0 6px 15px rgba(0,0,0,.15);
}
.table th{
    background:#f8f9fa;
}
.btn-sm{
    width:90px;
}
</style>
</head>

<body>

<div class="card">

<h4 class="text-center mb-3">🔁 EQUIPMENT RETURN APPROVAL</h4>
<hr>

<div class="table-responsive">
<table class="table table-bordered table-hover table-sm text-center align-middle">
<thead>
<tr>
    <th>SER NO</th>
    <th>STORE</th>
    <th>USER</th>
    <th>GRANT</th>
    <th>LP NO</th>
    <th>NOMENCLATURE</th>
    <th>A/U</th>
    <th>RETURN QTY</th>
    <th>REQUEST DATE</th>
    <th>ACTION</th>
</tr>
</thead>
<tbody>

<?php
$i = 1;

if ($result->num_rows == 0) {
    echo "<tr>
            <td colspan='10' class='text-danger fw-bold'>
                No pending return requests
            </td>
          </tr>";
}

while ($row = $result->fetch_assoc()) {

    $requestDate = $row['requested_at']
        ? date('d-m-Y', strtotime($row['requested_at']))
        : '-';

    echo "<tr>
        <td>{$i}</td>
        <td>".htmlspecialchars($row['store_name'])."</td>
        <td>".htmlspecialchars($row['username'])."</td>
        <td>".htmlspecialchars($row['grant_name'])."</td>
        <td>".htmlspecialchars($row['lp_no'])."</td>
        <td>".htmlspecialchars($row['equipment_name'])."</td>
        <td>".htmlspecialchars($row['au'])."</td>
        <td><b>{$row['return_qty']}</b></td>
        <td>{$requestDate}</td>
        <td>
            <a href='admin_return_action.php?id={$row['id']}&action=approve'
               class='btn btn-success btn-sm mb-1'
               onclick=\"return confirm('Approve this return request?')\">
               ✅ Approve
            </a>

            <a href='admin_return_action.php?id={$row['id']}&action=reject'
               class='btn btn-danger btn-sm'
               onclick=\"return confirm('Reject this return request?')\">
               ❌ Reject
            </a>
        </td>
    </tr>";

    $i++;
}
?>

</tbody>
</table>
</div>

<a href="dboard.php" class="btn btn-secondary mt-3">⬅ Back</a>

</div>

</body>
</html>
