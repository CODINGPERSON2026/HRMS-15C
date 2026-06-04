<?php 
session_start();
require_once __DIR__ . "/../auth.php";
require_admin();
require_once "../connect.php";

if (!isset($_SESSION['id'])) {
    header("location:../logout.php");
    exit;
}

/* FIXED FILTER – REGTL PROPERTIES / JCO MESS FUND */
$GRANT_TYPE = "REGTL PROPERTIES";
$GRANT_NAME = "JCO MESS FUND";

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
                et.unit_name,' - ',et.qty
            )
    END
    ORDER BY et.created_at
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
<title>JCO MESS FUND</title>
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
    padding:60px 18px 45px;
    border-radius:16px;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
}

/* HEADER */
h4{font-size:1.3rem;font-weight:700;margin-bottom:4px;}
.badge-grant{
    background:#004d40;
    color:#fff;
    padding:6px 18px;
    border-radius:20px;
}

/* APPX */
.appx-box{
    position:absolute;
    top:18px;
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
    margin:12px 0 8px;
}
.search-box{width:300px;}

/* TABLE – REGISTER STYLE */
table{
    width:100%;
    table-layout:fixed;
    font-size:0.95rem;
}
thead th{
    background:#f1f3f6;
    text-align:center;
    text-transform:uppercase;
    font-size:0.95rem;   
    font-weight:700;    
    padding:8px 6px;
}
td{
    text-align:center;
    vertical-align:middle;
    padding:6px;
    white-space:normal;
    word-wrap:break-word;
}

/* COLUMN WIDTHS */
th:nth-child(1), td:nth-child(1){ width:45px; }   /* SER */
th:nth-child(2), td:nth-child(2){ width:70px; }   /* LP */
th:nth-child(3), td:nth-child(3){
    width:240px;                                  /* NOMENCLATURE */
    text-align:left;
    font-weight:600;
}
th:nth-child(3){
    text-align:center;
    font-weight:700;
   }                                     /* heading center */
th:nth-child(4), td:nth-child(4){ width:50px; }   /* AU */
th:nth-child(5), td:nth-child(5){ width:80px; }   /* QTY REC */
th:nth-child(6), td:nth-child(6){ width:85px; }   /* QTY AVL */
th:nth-child(7), td:nth-child(7){ width:95px; }   /* DATE */
th:nth-child(8), td:nth-child(8){ width:90px; }   /* COST */
th:nth-child(9), td:nth-child(9){
    width:180px;                                  /* DISTRIBUTION */
    text-align:left;
}

/* STOCK COLORS */
.low-stock{background:#f8d7da!important;font-weight:700;}
.medium-stock{background:#fff3cd!important;font-weight:700;}

/* SIGNATURE */
.page-sign{
    position:absolute;
    bottom:22px;
    left:18px;
    right:18px;
    display:flex;
    justify-content:space-between;
    font-weight:600;
}

/* PRINT */
@media print{
    @page{size:A4 landscape;margin:10mm;}
    body{background:#fff;padding:0;}
    .top-bar,.search-box{display:none;}
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
    <h4>📦 JCO MESS FUND</h4>
    <span class="badge-grant">REGTL PROPERTIES</span>
</div>

<!-- TOP BAR -->
<div class="top-bar">
    <a href="../dboard.php" class="btn btn-secondary btn-sm">⬅ Back</a>
    <input type="text" id="searchInput" class="form-control form-control-sm search-box" placeholder="🔍 Search anything...">
    <button onclick="window.print()" class="btn btn-outline-primary btn-sm">🖨 Print</button>
</div>

<!-- TABLE -->
<table class="table table-bordered table-striped table-sm" id="equipTable">
<thead>
<tr>
    <th>Ser</th>
    <th>LP</th>
    <th>Nomenclature</th>
    <th>A/U</th>
    <th>Rec</th>
    <th>Avl</th>
    <th>Date</th>
    <th>Cost</th>
    <th>Distribution</th>
</tr>
</thead>
<tbody>

<?php
$i=1;
if($result->num_rows==0){
    echo "<tr><td colspan='9' class='text-center text-muted'>No Records Found</td></tr>";
}
while($row=$result->fetch_assoc()){

    $masterReceived  = (int)$row['qty_received'];
    $masterAvailable = (int)$row['qty_available'];

    $totalIssue  = (int)$row['total_issue'];
    $totalReturn = (int)$row['total_return'];

    /* ===============================
       SMART OPENING + NORMAL LOGIC
       =============================== */

    // 🔥 Opening Issued Case
    if($masterAvailable == 0 && $totalIssue > 0){

        $received  = $totalIssue;
        $available = 0;

    }
    else{

        // ✅ Normal System
        $received  = $masterReceived;
        $available = $masterReceived - ($totalIssue - $totalReturn);

        if($available < 0){
            $available = 0;
        }
    }

    $cls = $available<=5?'low-stock':($available<=10?'medium-stock':'');

    echo "<tr>
        <td>{$i}</td>
        <td>{$row['lp_no']}</td>
        <td>".htmlspecialchars($row['equipment_name'])."</td>
        <td>{$row['au']}</td>
        <td>{$received}</td>
        <td class='{$cls}'>{$available}</td>
        <td>{$row['received_date']}</td>
        <td>{$row['cost']}</td>
        <td class='dist-col'>".($row['distribution_details'] ?: "Not Issued")."</td>
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
