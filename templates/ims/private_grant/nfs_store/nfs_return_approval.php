<?php
session_start();

/* 🔥 PATH FIX */
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../connect.php";

require_admin();

if (!isset($_SESSION['id'])) {
    header("location:../../logout.php");
    exit;
}

/* ================= FETCH PENDING RETURNS ================= */
$sql = "
SELECT 
    re.id AS return_id,
    ne.nfs_category,
    ne.equipment_name,
    ne.serial_no,
    re.return_qty,
    re.returned_from,
    re.return_date,
    re.remarks,
    re.created_at
FROM nfs_return_equipment re
JOIN nfs_equipment ne ON ne.id = re.equipment_id
WHERE re.status = 'PENDING'
ORDER BY re.created_at DESC
";

$result = $connect->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>NFS Return Approval</title>
<link rel="stylesheet" href="../../css/bootstrap.min.css">

<style>
html,body{height:100%}
body{
    background:#eef5ff;
    margin:0;
    padding:8px;
    font-family:Segoe UI;
}
.card{
    height:calc(100vh - 16px);
    background:#fff;
    padding:10px;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
    display:flex;
    flex-direction:column;
}
h5{
    font-size:1.05rem;
    font-weight:700;
    margin-bottom:6px;
    color:#004d40;
}

/* TABLE */
.table-responsive{flex:1;overflow:auto;}
table{
    width:100%;
    table-layout:fixed;
    font-size:0.75rem;
}

thead th{
    position:sticky;
    top:0;
    z-index:5;
    background:#004d40;
    color:#fff;
    text-align:center;
    padding:6px 4px;
    font-weight:600;
}

td{
    text-align:center;
    vertical-align:middle;
    padding:5px 4px;
    word-break:break-word;
}

/* NOMENCLATURE */
.td-nomen{
    text-align:left;
    padding-left:8px;
    line-height:1.25;
}

/* ACTION */
.action-btns{
    display:flex;
    justify-content:center;
    gap:6px;
}
.action-btns a{
    font-size:0.72rem;
    padding:4px 8px;
    white-space:nowrap;
}

/* ROW */
tbody tr:nth-child(even){background:#f4f8ff;}
tbody tr:hover{background:#e6f0ff;}
</style>
</head>

<body>
<div class="card">

<h5 class="text-center">✅ NFS RETURN APPROVAL (PENDING)</h5>

<div class="table-responsive">
<table class="table table-bordered table-striped">
<thead>
<tr>
    <th>SER</th>
    <th>CATEGORY</th>
    <th>NOMENCLATURE</th>
    <th>SERIAL NO</th>
    <th>RETURN QTY</th>
    <th>RETURNED FROM</th>
    <th>RETURN DATE</th>
    <th>REMARKS</th>
    <th>ACTION</th>
</tr>
</thead>
<tbody>

<?php
$i=1;
if($result && $result->num_rows){
    while($r=$result->fetch_assoc()){
        echo "<tr>
            <td>{$i}</td>
            <td>{$r['nfs_category']}</td>
            <td class='td-nomen'>{$r['equipment_name']}</td>
            <td>{$r['serial_no']}</td>
            <td><b>{$r['return_qty']}</b></td>
            <td>{$r['returned_from']}</td>
            <td>".date('d-m-Y',strtotime($r['return_date']))."</td>
            <td>{$r['remarks']}</td>
            <td class='action-btns'>
                <a href='update_nfs_return_status.php?id={$r['return_id']}&status=APPROVED'
                   class='btn btn-success btn-sm'
                   onclick=\"return confirm('Approve this return?')\">✔ Approve</a>

                <a href='update_nfs_return_status.php?id={$r['return_id']}&status=REJECTED'
                   class='btn btn-danger btn-sm'
                   onclick=\"return confirm('Reject this return?')\">✖ Reject</a>
            </td>
        </tr>";
        $i++;
    }
}else{
    echo "<tr><td colspan='9'>No Pending Return Approvals</td></tr>";
}
?>

</tbody>
</table>
</div>

<a href="nfs_dashboard.php" class="btn btn-secondary btn-sm mt-2">⬅ Back</a>

</div>
</body>
</html>
