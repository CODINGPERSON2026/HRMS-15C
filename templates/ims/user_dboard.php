<?php
session_start();

require_once "auth.php";
require_once "connect.php";

require_user();


/* 🔐 USER GUARD */
if (
    !isset($_SESSION['id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'user'
) {
    header('Location: logout.php');
    exit;
}

$store = trim($_SESSION['unit']); // USER STORE NAME

/* ================= FETCH ISSUED GRANTS (STORE-WISE) ================= */
$sql = "
SELECT DISTINCT 
    gm.id,
    gm.grant_type,
    gm.grant_name
FROM equipment_txn et
JOIN equipment_master em ON em.id = et.equipment_id
JOIN grants_master gm ON gm.id = em.grant_id
WHERE et.txn_type = 'ISSUE'
  AND TRIM(et.unit_name) = ?
ORDER BY gm.grant_type, gm.grant_name
";

$stmt = $connect->prepare($sql);
$stmt->bind_param("s", $store);
$stmt->execute();
$res = $stmt->get_result();

/* ================= GROUP LIKE ADMIN ================= */
$tech = $public = $private = $nfs = [];

while ($r = $res->fetch_assoc()) {

    $link = "user_grant.php?grant_id=".$r['id'];

    if (in_array($r['grant_type'], [
        'TECH GRANT','TECH/ORD/ACSFP','ORD','ACSFP','SECT','LOAN'
    ])) {
        $tech[] = ['name'=>$r['grant_name'], 'link'=>$link];
    }
    elseif ($r['grant_type'] === 'PUBLIC GRANT') {
        $public[] = ['name'=>$r['grant_name'], 'link'=>$link];
    }
    elseif (in_array($r['grant_type'], ['PRIVATE GRANT','REGTL PROPERTIES'])) {
        $private[] = ['name'=>$r['grant_name'], 'link'=>$link];
    }
    elseif ($r['grant_type'] === 'NFS') {
        $nfs[] = ['name'=>$r['grant_name'], 'link'=>$link];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Inventory Dashboard</title>

<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
body{
    background:linear-gradient(135deg,#e3f2fd,#c8e6c9);
    font-family:'Segoe UI',sans-serif;
    min-height:100vh;
    display:flex;
    flex-direction:column;
}
.page-content{flex:1}
a{text-decoration:none!important}

/* TOP BAR */
.topbar{
    background:#004d40;
    color:#fff;
    padding:12px 25px;
    display:flex;
    justify-content:center;
    align-items:center;
    position:relative;
}
#dateTimeDisplay{
    position:absolute;
    right:25px;
    color:#ffe082;
    font-weight:bold;
    font-size:20px;
}

/* TITLE */
.mainTitle{
    text-align:center;
    margin:20px 0;
    font-size:2rem;
    font-weight:800;
    color:#004d40;
}

/* LOGOUT STRIP */
.logout-strip{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin:8px 20px 20px;
}

/* BUTTONS */
.action-btn{
    display:flex;
    align-items:center;
    gap:6px;
    padding:6px 14px;
    border-radius:18px;
    font-weight:600;
    font-size:12px;
    color:#fff;
    box-shadow:0 4px 10px rgba(0,0,0,.25);
}
.pass-btn{background:linear-gradient(135deg,#1976d2,#0d47a1)}
.logout-btn{background:linear-gradient(135deg,#ff5252,#ff1744)}

/* GRID */
.center-container{
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    gap:20px;
    max-width:1200px;
    margin:auto;
}

/* CARD */
.category-card{
    width:230px;
    min-height:160px;
    background:#fff;
    border-radius:15px;
    box-shadow:0 6px 15px rgba(0,0,0,.15);
    border-top:5px solid transparent;
    display:flex;
    flex-direction:column;
    align-items:center;
}
.category-card h2{
    margin:16px 10px 8px;
    font-size:1.1rem;
    font-weight:700;
    color:#004d40;
    cursor:pointer;
}

/* SUB MENU */
.sub-menu{display:none;width:100%;padding:0 15px 15px}
.category-card.active .sub-menu{display:block}
.sub-menu a{
    display:block;
    padding:6px 10px;
    font-size:.9rem;
    color:#004d40;
}
.sub-menu a:hover{background:#e0f2f1}

/* COLORS */
.color-0{border-top-color:#ffadad}
.color-1{border-top-color:#ffd6a5}
.color-2{border-top-color:#fdffb6}
.color-3{border-top-color:#90caf9}

/* FOOTER */
.footer-bottom{
    background:#004ea8;
    color:#fff;
    text-align:center;
    padding:22px;
    font-size:18px;
    font-weight:600;
}
</style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="page-content">

<div class="topbar">
 <h4><i class="fas fa-store"></i> <?= htmlspecialchars(strtoupper($store)) ?></h4>
 <div id="dateTimeDisplay"></div>
</div>

<div class="logout-strip">
    <a href="user_change_password.php" class="action-btn pass-btn">
        <i class="fas fa-key"></i> CHANGE PASSWORD
    </a>
    <a href="logout.php" class="action-btn logout-btn">
        <i class="fas fa-power-off"></i> LOGOUT
    </a>
</div>

<h3 class="mainTitle">
<i class="fas fa-boxes"></i> USER INVENTORY DASHBOARD
</h3>

<div class="center-container">

<!-- TECH -->
<div class="category-card color-0">
<h2>TECH / ORD / ACSFP</h2>
<div class="sub-menu">
<?= empty($tech) ? "<span class='text-danger ps-3'>No issued items</span>" :
implode('', array_map(fn($g)=>"<a href='{$g['link']}'>{$g['name']}</a>",$tech)) ?>
</div>
</div>

<!-- PUBLIC -->
<div class="category-card color-1">
<h2>PUBLIC GRANT</h2>
<div class="sub-menu">
<?= empty($public) ? "<span class='text-danger ps-3'>No issued items</span>" :
implode('', array_map(fn($g)=>"<a href='{$g['link']}'>{$g['name']}</a>",$public)) ?>
</div>
</div>

<!-- REGTL -->
<div class="category-card color-2">
<h2>REGTL PROPERTIES</h2>
<div class="sub-menu">
<?= empty($private) ? "<span class='text-danger ps-3'>No issued items</span>" :
implode('', array_map(fn($g)=>"<a href='{$g['link']}'>{$g['name']}</a>",$private)) ?>
</div>
</div>

<!-- ✅ NFS STORE -->
<div class="category-card color-3">
<h2>NFS STORE</h2>
<div class="sub-menu">
<?= empty($nfs) ? "<span class='text-danger ps-3'>No issued items</span>" :
implode('', array_map(fn($g)=>"<a href='{$g['link']}'>{$g['name']}</a>",$nfs)) ?>
</div>
</div>

<a href="user_return_vouchers.php">
<div class="category-card color-3">
<h2>RETURN VOUCHERS</h2>
</div>
</a>

</div>
</div>

<div class="footer-bottom">
 DESIGN AND DEVELOPED BY 15 CESR
</div>

<script>
document.querySelectorAll('.category-card h2').forEach(h=>{
 h.onclick=()=>{
  const c=h.parentElement;
  document.querySelectorAll('.category-card').forEach(x=>x!==c&&x.classList.remove('active'));
  c.classList.toggle('active');
 };
});
setInterval(()=>{
 document.getElementById("dateTimeDisplay").innerText=
 new Date().toLocaleString("en-IN",{timeZone:"Asia/Kolkata"});
},1000);
</script>

</body>
</html>
