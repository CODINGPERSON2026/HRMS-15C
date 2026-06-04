<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* ===== ISSUE / RETURN REPORT QUERY (NO REMARKS) ===== */
$sql = "
SELECT
    tx.id,
    em.job_no,
    em.equipment_name,
    em.au,
    tx.txn_type,
    tx.qty,
    tx.unit_name,
    DATE_FORMAT(tx.created_at,'%d-%m-%Y') AS txn_date
FROM opwks_equipment_txn tx
JOIN opwks_equipment_master em
    ON em.id = tx.equipment_id
ORDER BY tx.created_at DESC
";

$result = $connect->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>OPWKS Issue / Return Report</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:20px;
    font-family:Segoe UI;
}
.card{
    max-width:1400px;
    margin:auto;
    padding:20px;
    border-radius:14px;
    background:#fff;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
}
table th, table td{
    text-align:center;
    vertical-align:middle;
    font-size:0.9rem;
}
.badge-issue{
    background:#e53935;
}
.badge-return{
    background:#2e7d32;
}
</style>
</head>

<body>

<div class="card">
<h4 class="text-center mb-3">📤 OPWKS ISSUE / RETURN REPORT</h4>

<div class="mb-2 d-flex justify-content-between">
    <a href="opwks.php" class="btn btn-secondary btn-sm">⬅ Back</a>
    <button onclick="window.print()" class="btn btn-outline-primary btn-sm">🖨 Print</button>
</div>

<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
    <th>SER</th>
    <th>JOB / NAR NO</th>
    <th>NOMENCLATURE</th>
    <th>A/U</th>
    <th>TXN TYPE</th>
    <th>QTY</th>
    <th>ISSUED / RETURNED TO</th>
    <th>DATE</th>
</tr>
</thead>
<tbody>

<?php
$i = 1;
if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {

        $badge = ($r['txn_type'] === 'ISSUE')
            ? "<span class='badge badge-issue'>ISSUE</span>"
            : "<span class='badge badge-return'>RETURN</span>";

        echo "
        <tr>
            <td>{$i}</td>
            <td>{$r['job_no']}</td>
            <td>{$r['equipment_name']}</td>
            <td>{$r['au']}</td>
            <td>{$badge}</td>
            <td><b>{$r['qty']}</b></td>
            <td>{$r['unit_name']}</td>
            <td>{$r['txn_date']}</td>
        </tr>";
        $i++;
    }
} else {
    echo "<tr><td colspan='8'>No Issue / Return Records Found</td></tr>";
}
?>

</tbody>
</table>
</div>

</body>
</html>
