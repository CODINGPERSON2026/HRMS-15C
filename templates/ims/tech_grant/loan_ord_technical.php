<?php 
session_start();
require_once __DIR__ . "/../auth.php";
require_admin();
require_once "../connect.php";

if (!isset($_SESSION['id'])) {
    header("location:../logout.php");
    exit;
}

/* FIXED GRANT FILTER – LOAN / TECH */
$GRANT_TYPE = "TECH/ORD/ACSFP";
$GRANT_NAME = "LOAN";
$SUB_GRANT  = "TECH";

/* FETCH EQUIPMENTS + DISTRIBUTION DETAILS */
$stmt = $connect->prepare("
    SELECT 
        em.id,
        em.equipment_name,
        em.lp_no,
        em.cat_part_no,
        em.au,
        em.qty_received,
        em.qty_available,
        em.received_date,

        GROUP_CONCAT(
    CONCAT(
        et.unit_name,
        ' - ',
        et.qty
    )
    SEPARATOR '<br>'
) AS distribution_details

    FROM equipment_master em
    JOIN grants_master gm ON em.grant_id = gm.id
    LEFT JOIN equipment_txn et 
        ON et.equipment_id = em.id
       AND et.txn_type = 'ISSUE'

    WHERE gm.grant_type = ?
      AND gm.grant_name = ?
      AND gm.sub_grant  = ?

    GROUP BY em.id
    ORDER BY em.id DESC
");
$stmt->bind_param("sss", $GRANT_TYPE, $GRANT_NAME, $SUB_GRANT);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
<title>LOAN – TECH</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">

<style>
/* ================= SCREEN ================= */
body{
    background:#eef5ff;
    margin:0;
    padding:15px;
    font-family:Segoe UI;
}
.card{
    min-height:calc(100vh - 30px);
    background:#fff;
    padding:20px;
    border-radius:16px;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
    display:flex;
    flex-direction:column;
    position:relative;
}
h4{font-size:1.4rem;font-weight:700;}
.badge-grant{
    background:#004d40;
    color:#fff;
    padding:6px 18px;
    border-radius:20px;
}

/* ===== APPX (TOP RIGHT) ===== */
.appx-box{
    position:absolute;
    top:20px;
    right:25px;
    font-weight:600;
}

/* ===== EDITABLE (NO UNDERLINE) ===== */
.editable{
    min-width:80px;
    display:inline-block;
    padding:2px 6px;
    outline:none;
}

/* TOP BAR */
.top-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin:15px 0;
}
.search-box{width:300px;font-size:1rem;}

/* TABLE */
.table-responsive{flex:1;overflow:auto;}
table{
    width:100%;
    table-layout:fixed;
    font-size:0.95rem;
}
thead th{
    position:sticky;
    top:0;
    background:#004d40;
    color:#fff;
    text-align:center;
    padding:10px 6px;
}
td{
    text-align:center;
    padding:9px 6px;
}

/* COLUMN WIDTH */
.th-serno{width:72px;white-space:nowrap;}
.th-lp{width:80px;}
.th-cat{width:90px;}
.th-au{width:60px;}
.th-qty{width:90px;}
.th-date{width:120px;}

.dist-col{text-align:left;font-size:0.9rem;}

.low-stock{background:#f8d7da!important;font-weight:700;}
.medium-stock{background:#fff3cd!important;font-weight:700;}

/* ===== PAGE BOTTOM SIGN ===== */
.page-sign{
    margin-top:30px;
    display:flex;
    justify-content:space-between;
    font-weight:600;
}

/* ================= PRINT FIX ================= */
@media print{
    @page{size:A4 landscape;margin:10mm;}
    body{background:#fff;padding:0;}
    .top-bar,.back-btn{display:none!important;}
    table{table-layout:auto!important;width:100%!important;font-size:11px;}
    thead th{
        position:static!important;
        white-space:nowrap!important;
        background:#000!important;
        color:#fff!important;
    }
    td{white-space:nowrap;}
}
</style>
</head>

<body>

<div class="card">

<!-- APPX -->
<div class="appx-box">
    Appx : <span contenteditable="true" class="editable"></span>
</div>

<div class="text-center">
    <h4>🛠 LOAN – TECH</h4>
    <span class="badge-grant">TECH / ORD / ACSFP</span>
</div>

<div class="top-bar">
    <a href="../dboard.php" class="btn btn-secondary back-btn">⬅ Back</a>

    <input type="text" id="searchInput"
           class="form-control search-box"
           placeholder="🔍 Search anything...">

    <button onclick="window.print()" class="btn btn-outline-primary">
        🖨 Print
    </button>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped" id="equipTable">
<thead>
<tr>
    <th class="th-serno">SER.NO</th>
    <th class="th-lp">LP NO</th>
    <th class="th-cat">CAT / PART NO</th>
    <th>NOMENCLATURE</th>
    <th class="th-au">A/U</th>
    <th class="th-qty">QTY REC</th>
    <th class="th-qty">QTY AVL</th>
    <th class="th-date">DATE OF REC</th>
    <th>DISTRIBUTION DETAILS</th>
</tr>
</thead>
<tbody>

<?php
$i=1;
while($row=$result->fetch_assoc()){
    $qa=(int)$row['qty_available'];
    $cls=$qa<=5?'low-stock':($qa<=10?'medium-stock':'');

    echo "<tr>
        <td>{$i}</td>
        <td>{$row['lp_no']}</td>
        <td>{$row['cat_part_no']}</td>
        <td>".htmlspecialchars($row['equipment_name'])."</td>
        <td>{$row['au']}</td>
        <td>{$row['qty_received']}</td>
        <td class='{$cls}'>{$qa}</td>
        <td>{$row['received_date']}</td>
        <td class='dist-col'>".($row['distribution_details'] ?: "<span class='text-muted'>Not Issued</span>")."</td>
    </tr>";
    $i++;
}
?>

</tbody>
</table>
</div>

<!-- BOTTOM SIGN -->
<div class="page-sign">
    <div>PO : <span contenteditable="true" class="editable"></span></div>
    <div>M1 : <span contenteditable="true" class="editable"></span></div>
    <div>M2 : <span contenteditable="true" class="editable"></span></div>
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
