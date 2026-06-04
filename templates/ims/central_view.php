<?php
session_start();
require_once "auth.php";
require_admin();

require_once "connect.php";

if (!isset($_SESSION['id'])) {
    header("location:logout.php");
    exit;
}

/* ================= FIXED & SAFE QUERY ================= */
$sql = "
SELECT 
    em.id,
    gm.grant_type,
    gm.grant_name,
    gm.sub_grant,
    em.equipment_name,
    em.lp_no,
    em.cat_part_no,
    em.au,

    em.qty_received,

    IFNULL(SUM(
        CASE 
            WHEN et.txn_type = 'ISSUE'  THEN et.qty
            WHEN et.txn_type = 'RETURN' THEN 0
            ELSE 0
        END
    ),0) AS net_issued,

    (
        em.qty_received -
        IFNULL(SUM(
            CASE 
                WHEN et.txn_type = 'ISSUE'  THEN et.qty
                WHEN et.txn_type = 'RETURN' THEN -et.qty
                ELSE 0
            END
        ),0)
    ) AS qty_available,

    em.received_date,
    em.cost,

    GROUP_CONCAT(
        CONCAT(
            et.unit_name,
            ' : ',
            CASE 
                WHEN et.txn_type = 'ISSUE'  THEN CONCAT('-', et.qty)
                WHEN et.txn_type = 'RETURN' THEN CONCAT('+', et.qty)
            END,
            ' (',
            DATE_FORMAT(et.created_at,'%d-%m-%Y'),
            ')'
        )
        ORDER BY et.created_at
        SEPARATOR '<br>'
    ) AS distribution_details

FROM equipment_master em
JOIN grants_master gm ON em.grant_id = gm.id
LEFT JOIN equipment_txn et 
    ON et.equipment_id = em.id

GROUP BY em.id
HAVING qty_available > 0
ORDER BY em.id DESC
";
$result = $connect->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>Central Equipment View</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

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
h5{font-size:1rem;font-weight:700;margin-bottom:6px;}

.action-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:8px;
    margin-bottom:8px;
}
.search-box{max-width:260px;font-size:0.8rem;}

.table-responsive{flex:1;overflow:auto;}
table{
    width:100%;
    table-layout:fixed;
    font-size:0.78rem;
}

thead th{
    position:sticky;
    top:0;
    z-index:5;
    background:#004d40;
    color:#fff;
    text-align:center;
    padding:6px 4px;
    font-weight:700;
}

/* COLUMN WIDTH CONTROL */
.th-ser{width:45px;}
.th-granttype{width:130px;}
.th-grant{width:110px;}
.th-subgrant{width:90px;}
.th-lp{width:70px;}
.th-cat{width:120px;}
.th-au{width:50px;}
.th-qty{width:70px;}
.th-date{width:95px;}
.th-cost{width:90px;}
.th-action{width:170px;}

td{
    text-align:center;              /* cell center */
    vertical-align:middle;
    padding:5px 4px;
    word-break:break-word;
}

/* ✅ NOMENCLATURE: cell center, text left */
.td-nomenclature{
    text-align:left;               /* text left */
    padding-left:8px;
    white-space:normal;
    line-height:1.3;
}

/* DISTRIBUTION */
.dist-col{
    text-align:left;
    font-size:0.75rem;
    line-height:1.35;
}

/* ACTION BUTTONS */
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
tbody tr:nth-child(even){background:#f4f8ff;}
tbody tr:hover{background:#e6f0ff;}

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

<h5 class="text-center">📦 NEW ARRIVAL EQUIPMENTS</h5>

<div class="action-bar">
    <a href="dboard.php" class="btn btn-sm btn-secondary">⬅ Back</a>
    <input type="text" id="searchInput" class="form-control form-control-sm search-box" placeholder="🔍 Search...">
    <button onclick="window.print()" class="btn btn-sm btn-outline-primary">🖨 Print</button>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped" id="equipTable">
<thead>
<tr>
    <th class="th-ser">SER NO</th>
    <th class="th-granttype">GRANT TYPE</th>
    <th class="th-grant">GRANT</th>
    <th class="th-subgrant">SUB GRANT</th>
    <th class="th-lp">LP NO</th>
    <th class="th-cat">CAT/ PART NO</th>
    <th>NOMENCLATURE</th>
    <th class="th-au">A/U</th>
    <th class="th-qty">QTY REC</th>
    <th class="th-qty">QTY AVL</th>
    <th class="th-date">DATE</th>
    <th class="th-cost">COST</th>
    <th>DISTRIBUTION</th>
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
            <td>{$r['grant_type']}</td>
            <td>{$r['grant_name']}</td>
            <td>{$r['sub_grant']}</td>
            <td>{$r['lp_no']}</td>
            <td>{$r['cat_part_no']}</td>
            <td class='td-nomenclature'>{$r['equipment_name']}</td>
            <td>{$r['au']}</td>
            <td>{$r['qty_received']}</td>
            <td><b>{$r['qty_available']}</b></td>
            <td>{$r['received_date']}</td>
            <td>{$r['cost']}</td>
            <td class='dist-col'>".($r['distribution_details'] ?: "<span class='text-muted'>Not Issued</span>")."</td>
            <td class='action-btns'>
                <a href='edit_equipment.php?id={$r['id']}' class='btn btn-warning btn-sm'>✏ Edit</a>
                <a href='issue_equipment.php?id={$r['id']}' class='btn btn-primary btn-sm'>📤 Issue</a>
                <a href='delete_equipment.php?id={$r['id']}' class='btn btn-danger btn-sm'
                   onclick=\"return confirm('Delete this item?')\">🗑 Delete</a>
            </td>
        </tr>";
        $i++;
    }
}else{
    echo "<tr><td colspan='14'>No Equipment Found</td></tr>";
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
