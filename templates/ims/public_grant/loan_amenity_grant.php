<?php 
session_start();
require_once __DIR__ . "/../auth.php";
require_admin();
require_once "../connect.php";

if (!isset($_SESSION['id'])) {
    header("location:../logout.php");
    exit;
}

/* ✅ CORRECT FILTER – PUBLIC → LOAN → AMENITY */
$GRANT_TYPE = "PUBLIC GRANT";
$GRANT_NAME = "LOAN";
$SUB_GRANT  = "AMENITY GRANT";

/* FETCH EQUIPMENTS + DISTRIBUTION DETAILS */
$stmt = $connect->prepare("
    SELECT 
        em.id,
        em.equipment_name,
        em.lp_no,
        em.au,
        em.qty_received,
        em.qty_available,
        em.received_date,
        em.cost,

        IFNULL(SUM(CASE WHEN et.txn_type='ISSUE' THEN et.qty ELSE 0 END),0) AS total_issue,
        IFNULL(SUM(CASE WHEN et.txn_type='RETURN' THEN et.qty ELSE 0 END),0) AS total_return,

        GROUP_CONCAT(
    CASE 
        WHEN et.txn_type='ISSUE' THEN
            CONCAT(
                et.unit_name,
                ' - ',
                et.qty
            )
    END
    SEPARATOR '<br>'
) AS distribution_details


    FROM equipment_master em
    JOIN grants_master gm ON em.grant_id = gm.id
    LEFT JOIN equipment_txn et 
    ON et.equipment_id = em.id

    WHERE gm.grant_type = ?
    AND gm.grant_name = ?
    AND IFNULL(gm.sub_grant,'') = ?

    GROUP BY em.id
    ORDER BY em.id ASC
");

$stmt->bind_param("sss", $GRANT_TYPE, $GRANT_NAME, $SUB_GRANT);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
<title>LOAN – AMENITY GRANT</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">

<style>
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

/* APPX */
.appx-box{
    position:absolute;
    top:20px;
    right:25px;
    font-weight:600;
}

/* editable – NO underline */
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

/* COLUMN WIDTHS */
th:nth-child(1), td:nth-child(1){ width:45px; }   /* SER */
th:nth-child(2), td:nth-child(2){ width:70px; }   /* LP */
   thead th:nth-child(3){
    font-weight:700;   /* header bold */
    text-align:center;
     width:240px;
}
tbody td:nth-child(3){
    font-weight:400;   /* text normal */
    text-align:left;
    width:240px;
}      
th:nth-child(4), td:nth-child(4){ width:50px; }   /* AU */
th:nth-child(5), td:nth-child(5){ width:80px; }   /* QTY REC */
th:nth-child(6), td:nth-child(6){ width:85px; }   /* QTY AVL */
th:nth-child(7), td:nth-child(7){ width:95px; }   /* DATE */
th:nth-child(8), td:nth-child(8){ width:90px; }   /* COST */
th:nth-child(9), td:nth-child(9){
    width:180px;                                  /* DISTRIBUTION */
    text-align:left;
}

/* COLUMN WIDTH */
.th-serno{width:65px;}
.th-lp{width:120px;}
.th-au{width:120px;}
.th-qty{width:120px;}
.th-date{width:120px;}
.th-cost{width:120px; }

.dist-col{
    text-align:center;
    font-size:17px;
    width:180px;
}

/* BOTTOM SIGN */
.page-sign{
    margin-top:30px;
    display:flex;
    justify-content:space-between;
    font-weight:600;
}

/* PRINT */
@media print{
    @page{size:A4 landscape;margin:10mm;}
    body{background:#fff;padding:0;}
    .top-bar{display:none!important;}
    table{font-size:11px;}
    thead th{position:static;background:#000;color:#fff;}
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
    <h4>📦 LOAN – AMENITY GRANT</h4>
    <span class="badge-grant">PUBLIC GRANT</span>
</div>

<div class="top-bar">
    <a href="../dboard.php" class="btn btn-secondary">⬅ Back</a>

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
    <th class="th-serno"style="text-align:center";>SER NO</th>
    <th class="th-lp">LP NO</th>
    <th>NOMENCLATURE</th>
    <th class="th-au">A/U</th>
    <th class="th-qty">QTY REC</th>
    <th class="th-qty">QTY AVL</th>
    <th class="th-date">REC DATE</th>
    <th class="th-cost">COST </th>
    <th>DISTRIBUTION DETAILS</th>
</tr>
</thead>
<tbody>

<?php
$i=1;
while($row=$result->fetch_assoc()){

    $masterReceived  = (int)$row['qty_received'];
    $masterAvailable = (int)$row['qty_available'];

    $totalIssue  = (int)$row['total_issue'];
    $totalReturn = (int)$row['total_return'];

    /* ===============================
       FINAL SMART LOGIC
       =============================== */

    // 🔥 OPENING ALREADY ISSUED CASE
    if($masterAvailable == 0 && $totalIssue > 0){

        $received  = $totalIssue;   // show distribution total
        $available = 0;

    }
    else{

        // ✅ NORMAL SYSTEM
        $received  = $masterReceived;
        $available = $masterReceived - ($totalIssue - $totalReturn);

        if($available < 0){
            $available = 0;
        }
    }

    $cls = $available<=5 ? 'low-stock' : ($available<=10 ? 'medium-stock' : '');

    echo "<tr>
        <td>{$i}</td>
        <td>{$row['lp_no']}</td>
        <td>".htmlspecialchars($row['equipment_name'])."</td>
        <td>{$row['au']}</td>
        <td>{$received}</td>
        <td class='{$cls}'>{$available}</td>
        <td>{$row['received_date']}</td>
        <td>{$row['cost']}</td>
        <td class='dist-col'>".($row['distribution_details'] ?: "<span class='text-muted'>Not Issued</span>")."</td>
    </tr>";

    $i++;
}

?>

</tbody>
</table>
</div>

<!-- SIGN -->
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
        r.style.display = r.innerText.toLowerCase().includes(f) ? "" : "none";
    });
});
</script>

</body>
</html>
