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

/* ================= NFS NEW ARRIVAL QUERY (FINAL LOGIC) ================= */
$sql = "
SELECT 
    ne.id,
    ne.nfs_category,
    ne.equipment_name,
    ne.make,
    ne.serial_no,
    ne.au,
    ne.qty_received,

    IFNULL(SUM(
        CASE 
            WHEN tx.type = 'ISSUE'  THEN tx.qty
            WHEN tx.type = 'RETURN' THEN -tx.qty
        END
    ),0) AS net_issued,

    (
        ne.qty_received -
        IFNULL(SUM(
            CASE 
                WHEN tx.type = 'ISSUE'  THEN tx.qty
                WHEN tx.type = 'RETURN' THEN -tx.qty
            END
        ),0)
    ) AS qty_available,

    ne.unit_cost,
    ne.total_cost,
    ne.received_date

FROM nfs_equipment ne

LEFT JOIN (
    SELECT equipment_id, issue_qty AS qty, 'ISSUE' AS type
    FROM nfs_issue_equipment
    UNION ALL
    SELECT equipment_id, return_qty AS qty, 'RETURN' AS type
    FROM nfs_return_equipment
) tx ON tx.equipment_id = ne.id

GROUP BY ne.id
HAVING qty_available > 0
ORDER BY ne.id ASC
";

$result = $connect->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>NFS New Arrival Equipments</title>
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
    width:100%;
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

/* ACTION BAR */
.action-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:8px;
    margin-bottom:8px;
}
.search-box{
    max-width:240px;
    font-size:0.78rem;
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

/* COLUMN WIDTH CONTROL */
.th-ser{width:40px;}
.th-cat{width:95px;}
.th-nomen{width:260px;}
.th-make{width:110px;}
.th-serial{width:130px;}
.th-au{width:50px;}
.th-qty{width:65px;}
.th-date{width:90px;}
.th-cost{width:85px;}
.th-action{width:170px;}

td{
    text-align:center;
    vertical-align:middle;
    padding:5px 4px;
    word-break:break-word;
}

/* NOMENCLATURE */
.td-nomenclature{
    text-align:left;
    padding-left:8px;
    white-space:normal;
    line-height:1.25;
    max-width:260px;
    font-weight:500;
}

/* ACTION BUTTONS – ORIGINAL STYLE */
.action-btns{
    display:flex;
    justify-content:center;
    gap:4px;
}
.action-btns a{
    padding:4px 6px;
    font-size:0.72rem;
    white-space:nowrap;
}

/* ROW STYLE */
tbody tr:nth-child(even){background:#f5f9ff;}
tbody tr:hover{background:#e3efff;}

@media print{
    @page{size:A4 landscape;margin:10mm;}
    .action-bar{display:none;}
    .card{box-shadow:none;padding:0;height:auto;}
    table{font-size:11px;table-layout:auto;}
    thead th{position:static;}
    .th-action, td:last-child{display:none;}
}
</style>
</head>

<body>
<div class="card">

<h5 class="text-center">📦 NFS NEW ARRIVAL EQUIPMENTS (AVAILABLE STOCK)</h5>

<div class="action-bar">
    <a href="nfs_dashboard.php" class="btn btn-sm btn-secondary">⬅ Back</a>
    <input type="text" id="searchInput" class="form-control form-control-sm search-box" placeholder="🔍 Search...">
    <button onclick="window.print()" class="btn btn-sm btn-outline-primary">🖨 Print</button>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped" id="equipTable">
<thead>
<tr>
    <th class="th-ser">SER</th>
    <th class="th-cat">CATEGORY</th>
    <th class="th-nomen">NOMENCLATURE</th>
    <th class="th-make">MAKE</th>
    <th class="th-serial">SERIAL NO</th>
    <th class="th-au">A/U</th>
    <th class="th-qty">AVL QTY</th>
    <th class="th-date">DATE</th>
    <th class="th-cost">UNIT</th>
    <th class="th-cost">TOTAL</th>
    <th class="th-action">ACTION</th>
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
            <td class='td-nomenclature'>{$r['equipment_name']}</td>
            <td>{$r['make']}</td>
            <td>{$r['serial_no']}</td>
            <td>{$r['au']}</td>
            <td><b>{$r['qty_available']}</b></td>
            <td>".date('d-m-Y',strtotime($r['received_date']))."</td>
            <td>".number_format($r['unit_cost'],2)."</td>
            <td>".number_format($r['total_cost'],2)."</td>
            <td class='action-btns'>
                <a href='edit_nfs_equipment.php?id={$r['id']}' class='btn btn-warning btn-sm'>✏ Edit</a>
                <a href='nfs_issue_equipment.php?id={$r['id']}' class='btn btn-primary btn-sm'>📤 Issue</a>
                <a href='delete_nfs_equipment.php?id={$r['id']}'
                   class='btn btn-danger btn-sm'
                   onclick=\"return confirm('Delete this item?')\">🗑 Delete</a>
            </td>
        </tr>";
        $i++;
    }
}else{
    echo "<tr><td colspan='11'>No Equipment Found</td></tr>";
}
?>

</tbody>
</table>
</div>
</div>

<script>
document.getElementById("searchInput").addEventListener("keyup",function(){
    let f=this.value.toLowerCase();
    document.querySelectorAll("#equipTable tbody tr").forEach(r=>{
        r.style.display=r.innerText.toLowerCase().includes(f)?"":"none";
    });
});
</script>

</body>
</html>
