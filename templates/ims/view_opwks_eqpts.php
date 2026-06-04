<?php
session_start();
require_once "auth.php";
require_admin();
require_once "connect.php";

$sql="
SELECT 
 em.id,em.equipment_name,em.lp_no,em.au,em.qty_received,em.cost,
 IFNULL(
  GROUP_CONCAT(
   CONCAT(
    et.unit_name,' : ',
    CASE 
      WHEN et.txn_type='ISSUE' THEN CONCAT('-',et.qty)
      WHEN et.txn_type='RETURN' THEN CONCAT('+',et.qty)
    END,
    ' (',DATE_FORMAT(et.created_at,'%d-%m-%Y'),')'
   )
   ORDER BY et.created_at SEPARATOR '<br>'
  ),
  '<span class=text-muted>Not Issued</span>'
 ) AS distribution
FROM equipment_master em
LEFT JOIN equipment_txn et 
 ON et.equipment_id=em.id AND et.txn_type IN('ISSUE','RETURN')
WHERE em.grant_type='OPWKS'
GROUP BY em.id
ORDER BY em.id DESC
";
$res=$connect->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>OPWKS Equipment View</title>
<link rel="stylesheet" href="css/bootstrap.min.css">
<style>
body{background:#eef5ff;padding:10px;font-family:Segoe UI}
.dist{text-align:left;font-size:.85rem}
</style>
</head>
<body>

<div class="card p-3">
<h5 class="text-center">🔧 OPWKS EQUIPMENT REGISTER</h5>
<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
<th>Ser</th><th>Job No</th><th>Nomenclature</th>
<th>A/U</th><th>Qty</th><th>Cost</th>
<th>Distribution (Issue / Return)</th>
</tr>
</thead>
<tbody>
<?php $i=1; while($r=$res->fetch_assoc()){ ?>
<tr>
<td><?= $i++ ?></td>
<td><?= $r['lp_no'] ?></td>
<td><?= $r['equipment_name'] ?></td>
<td><?= $r['au'] ?></td>
<td><?= $r['qty_received'] ?></td>
<td><?= $r['cost'] ?></td>
<td class="dist"><?= $r['distribution'] ?></td>
</tr>
<?php } ?>
</tbody>
</table>
<a href="dboard.php" class="btn btn-secondary">⬅ Back</a>
</div>

</body>
</html>
