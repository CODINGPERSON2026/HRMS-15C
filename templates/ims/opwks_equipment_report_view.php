<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

/* ===== FETCH DATA WITH AVAILABLE QTY ===== */
$sql = "
SELECT 
    em.id,
    em.job_no,
    em.equipment_name,
    em.au,
    em.qty_received,

    IFNULL(SUM(
        CASE 
            WHEN tx.txn_type = 'ISSUE'  THEN tx.qty
            WHEN tx.txn_type = 'RETURN' THEN -tx.qty
            ELSE 0
        END
    ),0) AS net_issued,

    (
        em.qty_received -
        IFNULL(SUM(
            CASE 
                WHEN tx.txn_type = 'ISSUE'  THEN tx.qty
                WHEN tx.txn_type = 'RETURN' THEN -tx.qty
                ELSE 0
            END
        ),0)
    ) AS qty_available,

    em.cost_each,
    em.total_amount,
    DATE_FORMAT(em.created_at,'%d-%m-%Y') AS rec_date,

    IFNULL(
        GROUP_CONCAT(
            CONCAT(
                IFNULL(tx.unit_name,'-'),
                ' : ',
                CASE 
                    WHEN tx.txn_type = 'ISSUE'  THEN CONCAT('-', tx.qty)
                    WHEN tx.txn_type = 'RETURN' THEN CONCAT('+', tx.qty)
                END,
                ' (', DATE_FORMAT(tx.created_at,'%d-%m-%Y'), ')'
            )
            ORDER BY tx.created_at
            SEPARATOR '<br>'
        ),
        '<span class=\"text-muted\">Not Issued</span>'
    ) AS distribution

FROM opwks_equipment_master em
LEFT JOIN opwks_equipment_txn tx 
    ON tx.equipment_id = em.id

GROUP BY em.id
ORDER BY em.id DESC
";

$result = $connect->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>OPWKS Equipment Report</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:20px;
    font-family:Segoe UI;
}
.card{
    max-width:1600px;
    margin:auto;
    padding:20px;
    border-radius:14px;
    background:#fff;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
}
table th, table td{
    text-align:center;
    vertical-align:middle;
    font-size:0.85rem;
}
.dist{
    text-align:left;
    line-height:1.4;
}
.search-box{
    max-width:260px;
}
</style>
</head>

<body>

<div class="card">

<h4 class="text-center mb-3">📊 OPWKS EQUIPMENT REPORT</h4>

<div class="d-flex justify-content-between mb-3">
    <a href="opwks.php" class="btn btn-secondary btn-sm">⬅ Back</a>

    <input type="text" id="searchInput"
           class="form-control form-control-sm search-box"
           placeholder="🔍 Search...">
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped" id="reportTable">

<thead class="table-dark">
<tr>
    <th>SER</th>
    <th>JOB / NAR NO</th>
    <th>NOMENCLATURE</th>
    <th>A/U</th>
    <th>QTY REC</th>
    <th>QTY AVL</th>
    <th>COST / EACH</th>
    <th>TOTAL AMOUNT</th>
    <th>DATE</th>
    <th>DISTRIBUTION</th>
</tr>
</thead>

<tbody>
<?php
$i = 1;
if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {

        $available = max(0, $r['qty_available']);

        echo "
        <tr>
            <td>{$i}</td>
            <td>{$r['job_no']}</td>
            <td>{$r['equipment_name']}</td>
            <td>{$r['au']}</td>
            <td>{$r['qty_received']}</td>
            <td><b>{$available}</b></td>
            <td>".number_format((float)$r['cost_each'],2)."</td>
            <td><b>".number_format((float)$r['total_amount'],2)."</b></td>
            <td>{$r['rec_date']}</td>
            <td class='dist'>{$r['distribution']}</td>
        </tr>";
        $i++;
    }
} else {
    echo "<tr><td colspan='10'>No Records Found</td></tr>";
}
?>
</tbody>

</table>
</div>
</div>

<script>
/* 🔍 SEARCH FILTER */
document.getElementById("searchInput").addEventListener("keyup", function(){
    let value = this.value.toLowerCase();
    document.querySelectorAll("#reportTable tbody tr").forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
    });
});
</script>

</body>
</html>
