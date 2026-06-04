<?php
session_start();

require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../connect.php";

require_admin();

/* CATEGORY (NFS GRANT NAME) */
$category = trim($_GET['cat'] ?? '');
if ($category === '') {
    die("Invalid Category");
}
$category_safe = mysqli_real_escape_string($connect, $category);

/* ================= FINAL NFS QUERY ================= */
$sql = "
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
    ORDER BY et.issue_date DESC
    SEPARATOR '<br>'
) AS distribution_details

FROM equipment_master em
JOIN grants_master gm 
    ON gm.id = em.grant_id

LEFT JOIN equipment_txn et 
    ON et.equipment_id = em.id

WHERE 
    gm.grant_type = 'NFS'
    AND gm.grant_name = '$category_safe'

GROUP BY em.id
ORDER BY em.id DESC
";


$result = mysqli_query($connect, $sql);
if (!$result) {
    die("SQL Error: " . mysqli_error($connect));
}
?>
<!DOCTYPE html>
<html>
<head>
<title><?= htmlspecialchars($category) ?> – NFS STORE</title>
<link rel="stylesheet" href="../../css/bootstrap.min.css">

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
th:nth-child(3),
td:nth-child(3){
    width:220px;          /* 👈 adjust value here */
    max-width:220px;
    text-align:left;
    padding-left:8px;
    word-wrap:break-word;
}

/* TOP BAR */
.top-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin:15px 0 10px;
}
.search-box{width:320px;}

/* TABLE */
table{width:100%;font-size:0.95rem;}
thead th{
    background:#f2f2f2;
    text-align:center;
}
td{
    text-align:center;
    vertical-align:middle;
}
.dist-col{text-align:left;}

/* STOCK COLORS */
.low-stock{background:#f8d7da!important;font-weight:700;}
.medium-stock{background:#fff3cd!important;font-weight:700;}

/* SIGNATURE */
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
}table{
    width:100%;
    table-layout:fixed;
}

/* SER NO */
th:nth-child(1), td:nth-child(1){
    width:60px;
}

/* LP NO */
th:nth-child(2), td:nth-child(2){
    width:110px;   /* 👈 yaha control karega */
}

/* NOMENCLATURE */
th:nth-child(3), td:nth-child(3){
    width:200px;
    max-width:200px;
    text-align:left;
    padding-left:8px;
    word-wrap:break-word;
}

/* A/U */
th:nth-child(4), td:nth-child(4){
    width:70px;
}

/* QTY REC */
th:nth-child(5), td:nth-child(5){
    width:90px;
}

/* QTY AVL */
th:nth-child(6), td:nth-child(6){
    width:90px;
}

/* DATE */
th:nth-child(7), td:nth-child(7){
    width:120px;
}

/* COST */
th:nth-child(8), td:nth-child(8){
    width:110px;
}

/* DISTRIBUTION */
th:nth-child(9), td:nth-child(9){
    width:200px;
}

/* NOMENCLATURE ALIGN */
th:nth-child(3){text-align:center;}
td:nth-child(3){text-align:left;padding-left:8px;}
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
    <h4>📦 <?= htmlspecialchars($category) ?></h4>
    <span class="badge-grant">NFS STORE</span>
</div>

<!-- TOP BAR -->
<div class="top-bar">
    <a href="nfs_dashboard.php" class="btn btn-secondary">⬅ Back</a>
    <input type="text" id="searchInput" class="form-control search-box" placeholder="🔍 Search anything...">
    <button onclick="window.print()" class="btn btn-outline-primary">🖨 Print</button>
</div>

<!-- TABLE -->
<table class="table table-bordered table-striped" id="equipTable">
<thead>
<tr>
    <th>SER NO</th>
    <th>LP NO</th>
    <th>NOMENCLATURE</th>
    <th>A/U</th>
    <th>QTY REC</th>
    <th>QTY AVL</th>
    <th>DATE OF RECEIVED</th>
    <th>COST OF EACH</th>
    <th>DISTRIBUTION</th>
</tr>
</thead>
<tbody>

<?php
$i=1;
if(mysqli_num_rows($result)==0){
    echo "<tr><td colspan='9' class='text-center text-muted'>No Records Found</td></tr>";
}
while($row=mysqli_fetch_assoc($result)){

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
        <td class='dist-col'>".($row['distribution_details'] ?: 'Not Issued')."</td>
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
