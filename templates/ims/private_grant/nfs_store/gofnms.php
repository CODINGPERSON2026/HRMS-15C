<?php
session_start();
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../connect.php";
require_admin();

/* CATEGORY REGISTER – SHOW ALL (EVEN QTY 0) */
$sql = "
SELECT 
    ne.id,
    ne.equipment_name,
    ne.make,
    ne.serial_no,
    ne.au,
    ne.qty_received,

    IFNULL(SUM(
        CASE 
            WHEN tx.type='ISSUE' THEN tx.qty
            WHEN tx.type='RETURN' THEN -tx.qty
        END
    ),0) AS net_issued,

    (ne.qty_received -
     IFNULL(SUM(
        CASE 
            WHEN tx.type='ISSUE' THEN tx.qty
            WHEN tx.type='RETURN' THEN -tx.qty
        END
     ),0)
    ) AS qty_available,

    ne.received_date

FROM nfs_equipment ne
LEFT JOIN (
    SELECT equipment_id, issue_qty AS qty, 'ISSUE' AS type FROM nfs_issue_equipment
    UNION ALL
    SELECT equipment_id, return_qty AS qty, 'RETURN' AS type FROM nfs_return_equipment
) tx ON tx.equipment_id = ne.id

WHERE ne.nfs_category='GOFNMS'
GROUP BY ne.id
ORDER BY ne.id DESC
";

$result = $connect->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>GOFNMS Register</title>
<link rel="stylesheet" href="../../css/bootstrap.min.css">
<style>
body{background:#eef5ff;padding:8px;font-family:Segoe UI}
.card{background:#fff;padding:10px;border-radius:14px;box-shadow:0 6px 18px rgba(0,0,0,.15)}
table{font-size:.75rem}
th{background:#004d40;color:#fff;text-align:center}
td{text-align:center}
.td-left{text-align:left}
.zero{color:#b71c1c;font-weight:700}
</style>
</head>

<body>
<div class="card">
<h5 class="text-center">📡 GOFNMS EQUIPMENT REGISTER</h5>

<table class="table table-bordered table-striped">
<thead>
<tr>
<th>SER</th>
<th>NOMENCLATURE</th>
<th>MAKE</th>
<th>SERIAL</th>
<th>A/U</th>
<th>QTY RECEIVED</th>
<th>DATE</th>
</tr>
</thead>
<tbody>

<?php $i=1;
while($r=$result->fetch_assoc()){ ?>
<tr>
<td><?= $i++ ?></td>
<td class="td-left"><?= $r['equipment_name'] ?></td>
<td><?= $r['make'] ?></td>
<td><?= $r['serial_no'] ?></td>
<td><?= $r['au'] ?></td>
<td class="<?= $r['qty_available']==0?'zero':'' ?>">
    <?= $r['qty_available'] ?>
</td>
<td><?= date('d-m-Y',strtotime($r['received_date'])) ?></td>
</tr>
<?php } ?>

</tbody>
</table>
</div>
</body>
</html>
