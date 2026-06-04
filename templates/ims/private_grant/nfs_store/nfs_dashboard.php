<?php
session_start();
require_once __DIR__ . "/../../auth.php";
require_once __DIR__ . "/../../connect.php";
require_admin();

/* ROLE GUARD */
if (
    !isset($_SESSION['id']) ||
    !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin','super_admin'])
) {
    header('Location: ../../user_dboard.php');
    exit;
}

/* 🔔 NOTIFICATIONS (OPTIONAL – SAFE) */
$notifications = $connect->query("
    SELECT title,message,type
    FROM notifications
    ORDER BY created_at DESC
    LIMIT 8
");
?>
<!DOCTYPE html>
<html>
<head>
<title>NFS STORE DASHBOARD</title>

<link rel="stylesheet" href="../../css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
html,body{height:100%;margin:0;font-family:Segoe UI}
body{background:linear-gradient(135deg,#e3f2fd,#c8e6c9)}
a{text-decoration:none;color:#000}

/* TOP BAR */
.topbar{
    height:45px;background:#004d40;color:#fff;
    display:flex;align-items:center;justify-content:center;
    position:relative;font-weight:600;font-size:18px;
}
#dateTimeDisplay{position:absolute;right:260px;font-size:18px;color:#ffe082}

/* BUTTONS */
.logout-btn{
    position:absolute;right:20px;top:8px;
    background:linear-gradient(#ff5252,#d50000);
    color:#fff;height:32px;padding:0 18px;
    border-radius:18px;font-size:14px;font-weight:700;
}

.back-btn{
    position:absolute;
    left:20px;
    top:6px;
    background:linear-gradient(#42a5f5,#1565c0);
    color:#fff;
    height:32px;
    padding:0 18px;
    border-radius:18px;
    font-size:12px;
    font-weight:700;
    display:flex;
    align-items:center;
    gap:6px;
}
.back-btn:hover{
    opacity:0.9;
}

/* LAYOUT */
.dashboard-wrapper{
    flex:1;
    padding:15px 0;
    overflow:hidden;
}

.dashboard-3col{
    height:100%;
    display:grid;
    grid-template-columns:320px 1fr 320px;   /* bigger side panels */
    gap:25px;
    max-width:1550px;
    margin:auto;
}

/* PANELS */
.side-panel{
    background:#fff;
    border-radius:22px;
    padding:16px;
    box-shadow:0 12px 18px rgba(0,0,0,.18);
    display:grid;
    gap:12px;
}

.center-panel{
    background:#fff;
    border-radius:22px;
    box-shadow:0 12px 18px rgba(0,0,0,.18);
    padding:18px;
    display:flex;
    flex-direction:column;
}

/* BUTTON CARDS */
.category-card,
.action-buttons a{
    background:#fff;
    border-radius:26px;
    border-top:4px solid #8b0000;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:17px;
    font-weight:600;
    box-shadow:0 6px 14px rgba(0,0,0,.15);
    height:100px;              /* increased from 64px */
    transition:0.2s ease;
}

.category-card:hover,
.action-buttons a:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 16px rgba(0,0,0,.18);
}

/* CENTER */
.notify-header{
    font-size:20px;
    font-weight:700;
    color:#004d40;
    border-bottom:2px solid #e0e0e0;
    padding-bottom:8px;
}

.notify-list{
    margin-top:12px;
    overflow-y:auto;
}

.notify-item{
    background:#f9f9f9;
    border-left:5px solid #8b0000;
    border-radius:10px;
    padding:12px;
    margin-bottom:10px;
    font-size:15px;
}

/* FOOTER */
.footer{
    height:45px;background:#004ea8;color:#fff;
    display:flex;align-items:center;justify-content:center;
    font-weight:600;
}
</style>
</head>

<body>

<!-- 🔰 SAME HEADER -->
<?php include __DIR__ . "/../../header.php"; ?>

<div class="topbar">

    <!-- 🔙 BACK BUTTON -->
    <a href="../../dboard.php" class="back-btn">
        <i class="fa fa-arrow-left"></i> BACK
    </a>

    <span>NFS STORE</span>
    <div id="dateTimeDisplay"></div>
    <a href="../../logout.php" class="logout-btn">LOGOUT</a>

</div>

<div class="dashboard-wrapper">
<div class="dashboard-3col">

<!-- LEFT : NFS CATEGORIES -->
<div class="side-panel">
    <a href="nfs_category_view.php?cat=GOFNMS"><div class="category-card">GOFNMS</div></a>
    <a href="nfs_category_view.php?cat=IPMPLS"><div class="category-card">IPMPLS</div></a>
    <a href="nfs_category_view.php?cat=MW"><div class="category-card">MW</div></a>
    <a href="nfs_category_view.php?cat=SATL"><div class="category-card">SATL</div></a>
    <a href="nfs_category_view.php?cat=DWDM"><div class="category-card">DWDM</div></a>
    <a href="nfs_category_view.php?cat=HC-MCEU"><div class="category-card">HC-MCEU</div></a>
    <a href="nfs_category_view.php?cat=LC-MCEU"><div class="category-card">LC-MCEU</div></a>
</div>

<!-- CENTER : NOTIFICATIONS -->
<div class="center-panel">
    <div class="notify-header">
        <i class="fa fa-bell"></i> NOTIFICATIONS & ALERTS
    </div>
    <div class="notify-list">
        <?php if($notifications && $notifications->num_rows): ?>
            <?php while($n=$notifications->fetch_assoc()): ?>
                <div class="notify-item">
                    <strong><?= htmlspecialchars($n['title']) ?></strong><br>
                    <?= htmlspecialchars($n['message']) ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="notify-item">No new notifications</div>
        <?php endif; ?>
    </div>
</div>

<!-- RIGHT : ACTIONS -->
<div class="side-panel action-buttons">
    <a href="add_nfs_equipment.php">ADD EQUIPMENTS</a>
    <a href="nfs_equipment_report.php">EQUIPMENTS REPORT</a>
    <a href="nfs_issue_equipment.php">ISSUE EQUIPMENTS</a>
    <a href="nfs_return_approval.php">RETURN APPROVAL</a>
    <a href="nfs_new_arrival.php">NEW ARRIVAL</a>
    <a href="nfs_voucher_details.php">VOUCHER DETAILS</a>
    <a href="nfs_return_voucher_list.php">RETURN VOUCHERS</a>
</div>

</div>
</div>

<div class="footer">DESIGN AND DEVELOPED BY 15 CESR</div>

<script>
setInterval(()=>{
    document.getElementById("dateTimeDisplay").innerText =
    new Date().toLocaleString("en-IN",{timeZone:"Asia/Kolkata"});
},1000);
</script>

</body>
</html>
