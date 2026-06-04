<?php 
session_start();
require_once __DIR__ . "/../auth.php";
require_admin();
require_once "../connect.php";

if (!isset($_SESSION['id'])) {
    header("location:../logout.php");
    exit;
}

/* FIXED GRANT FILTER – PUBLIC / MISC */
$GRANT_TYPE = "PUBLIC GRANT";
$GRANT_NAME = "MISC GRANT";

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

    GROUP BY em.id
    ORDER BY em.id DESC
");

$stmt->bind_param("ss", $GRANT_TYPE, $GRANT_NAME);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
<title>MISC GRANT</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    padding:15px;
    font-family:Segoe UI, Arial, sans-serif;
}
.card{
    position:relative;
    min-height:calc(100vh - 30px);
    background:#fff;
    padding:60px 20px 45px;
    border-radius:16px;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
}

/* HEADER */
h4{font-size:1.4rem;font-weight:700;margin-bottom:4px;}
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
.editable{
    min-width:80px;
    display:inline-block;
    outline:none;
}

/* TOP BAR */
.top-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin:15px 0 10px;
}
.search-box{width:320px;}

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

/* SIGNATURE – FIXED BOTTOM */
.page-sign{
    position:absolute;
    bottom:25px;
    left:20px;
    right:20px;
    display:flex;
    justify-content:space-between;
    font-weight:600;
}

/* PRINT */
@media print{
    @page{size:A4 landscape;margin:10mm;}
    body{background:#fff;padding:0;}
    .top-bar{display:none;}
}
</style>
</head>

<body>

<div class="card">

<!-- APPX -->
<div class="appx-box">
    Appx : <span contenteditable class="editable"></span>
</div>

<!-- TITLE -->
<div class="text-center">
    <h4>🏛 MISC GRANT</h4>
    <span class="badge-grant">PUBLIC GRANT</span>
</div>

<!-- TOP BAR -->
<div class="top-bar">
    <a href="../dboard.php" class="btn btn-secondary">⬅ Back</a>
    <input type="text" id="searchInput" class="form-control search-box" placeholder="🔍 Search anything...">
    <button onclick="window.print()" class="btn btn-outline-primary">🖨 Print</button>
</div>

<!-- TABLE -->
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
    <th class="th-cost">COST</th>
    <th>DISTRIBUTION</th>
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

<!-- SIGNATURE -->
<div class="page-sign">
    <div>PO : <span contenteditable class="editable"></span></div>
    <div>M1 : <span contenteditable class="editable"></span></div>
    <div>M2 : <span contenteditable class="editable"></span></div>
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
