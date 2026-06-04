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

/* ================= FETCH RETURN VOUCHERS ================= */
$sql = "
SELECT 
    re.id AS voucher_id,
    ne.nfs_category,
    ne.equipment_name,
    ne.serial_no,
    re.return_qty,
    re.returned_from,
    re.return_date,
    re.created_at
FROM nfs_return_equipment re
JOIN nfs_equipment ne ON ne.id = re.equipment_id
ORDER BY re.return_date DESC, re.created_at DESC
";

$result = $connect->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>NFS Return Voucher List</title>
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

/* ACTION BAR */
.action-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:8px;
    margin-bottom:8px;
}
.search-box{
    max-width:260px;
    font-size:0.8rem;
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

/* COLUMN WIDTH */
.th-ser{width:45px;}
.th-cat{width:100px;}
.th-serial{width:130px;}
.th-qty{width:80px;}
.th-date{width:95px;}
.th-party{width:180px;}

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

/* ROW STYLE */
tbody tr:nth-child(even){background:#f4f8ff;}
tbody tr:hover{background:#e6f0ff;}

@media print{
    @page{size:A4 landscape;margin:10mm;}
    .action-bar{display:none;}
    .card{box-shadow:none;padding:0;height:auto;}
    table{font-size:11px;table-layout:auto;}
    thead th{position:static;}
}
</style>
</head>

<body>
<div class="card">

<h5 class="text-center">↩️ NFS RETURN VOUCHER LIST</h5>

<div class="action-bar">
    <a href="nfs_dashboard.php" class="btn btn-sm btn-secondary">⬅ Back</a>
    <input type="text" id="searchInput" class="form-control form-control-sm search-box" placeholder="🔍 Search...">
    <button onclick="window.print()" class="btn btn-sm btn-outline-primary">🖨 Print</button>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped" id="returnTable">
<thead>
<tr>
    <th class="th-ser">SER</th>
    <th class="th-cat">CATEGORY</th>
    <th>NOMENCLATURE</th>
    <th class="th-serial">SERIAL NO</th>
    <th class="th-qty">RETURN QTY</th>
    <th class="th-party">RETURNED FROM</th>
    <th class="th-date">RETURN DATE</th>
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
        </tr>";
        $i++;
    }
}else{
    echo "<tr><td colspan='7'>No Return Vouchers Found</td></tr>";
}
?>

</tbody>
</table>
</div>
</div>

<script>
document.getElementById("searchInput").addEventListener("keyup",function(){
    let f=this.value.toLowerCase();
    document.querySelectorAll("#returnTable tbody tr").forEach(r=>{
        r.style.display=r.innerText.toLowerCase().includes(f)?"":"none";
    });
});
</script>

</body>
</html>
