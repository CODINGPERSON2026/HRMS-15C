<?php
session_start();
require_once "auth.php";
require_super_admin();
require_once "connect.php";

/* ================= USER COUNTS ================= */
$total_users = $connect->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$total_admin = $connect->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$total_block = $connect->query("SELECT COUNT(*) c FROM users WHERE status='BLOCKED'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Super Admin Dashboard</title>

<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="assets\fontawesome-free-7.1.0-web\css\all.min.css">

<style>
body{
    background:linear-gradient(135deg,#e3f2fd,#c8e6c9);
    font-family:'Segoe UI',sans-serif;
    min-height:100vh;
    display:flex;
    flex-direction:column;
}
a{text-decoration:none!important}
.page-content{flex:1}

/* TOP BAR */

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
    color:#3f0071;
}

/* ACTION BUTTONS */
.action-buttons{text-align:center;margin:25px 0}
.action-buttons a{
    margin:8px;
    padding:12px 30px;
    border-radius:30px;
    font-weight:600;
    transition:.3s;
}
.action-buttons a:hover{transform:scale(1.08)}

/* GRID */
.center-container{
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    gap:20px;
    max-width:1200px;
    margin:auto;
}

/* CARD – FINAL FIX */
.category-card{
    width:250px;
    height:180px;              /* ✅ SAME HEIGHT */
    background:#fff;
    border-radius:18px;
    box-shadow:0 8px 22px rgba(0,0,0,.18);
    border-top:6px solid transparent;
    text-align:center;
    padding:25px;
    cursor:pointer;
    transition:.3s;

    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
}
.category-card:hover{
    transform:translateY(-8px);
    box-shadow:0 18px 40px rgba(0,0,0,.25);
}
.category-card h2{
    font-size:1.1rem;
    font-weight:700;
    margin-top:10px;
    color:#3f0071;
}
.category-card .count{
    font-size:28px;
    font-weight:800;
    color:#000;
    margin:8px 0;
}

/* COLORS */
.color-0{border-top-color:#ff6f61}
.color-1{border-top-color:#42a5f5}
.color-2{border-top-color:#66bb6a}
.color-3{border-top-color:#ffa726}
.color-4{border-top-color:#ab47bc}
.color-5{border-top-color:#ef5350}

/* FOOTER */
.footer-bottom{
    background:#004ea8;
    color:#fff;
    text-align:center;
    padding:22px 10px;
    font-size:18px;
    letter-spacing:1px;
    font-weight:600;
    box-shadow:0 -4px 12px rgba(0,0,0,.25);
}
.category-card{
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    text-align:center;
}

a.category-card{
    color:inherit;
}

</style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="page-content">



<div class="action-buttons">
 <a href="super_admin_dboard.php" class="btn btn-dark">USER MANAGEMENT</a>
 <a href="central_view.php" class="btn btn-warning">CENTRAL STOCK</a>
 <a href="dboard.php" class="btn btn-primary">ADMIN DASHBOARD</a>
 <a href="loginactivity.php" class="btn btn-info text-white">LOGIN ACTIVITY</a>
 <a href="logout.php" class="btn btn-danger">LOGOUT</a>
</div>

<div class="center-container">

<div class="category-card color-0">
 <i class="fas fa-users fa-2x"></i>
 <div class="count"><?= $total_users ?></div>
 <h2>TOTAL USERS</h2>
</div>

<div class="category-card color-1">
 <i class="fas fa-user-tie fa-2x"></i>
 <div class="count"><?= $total_admin ?></div>
 <h2>TOTAL ADMINS</h2>
</div>

<div class="category-card color-2">
 <i class="fas fa-user-lock fa-2x"></i>
 <div class="count"><?= $total_block ?></div>
 <h2>BLOCKED USERS</h2>
</div>

<!-- ✅ FIXED : MANAGE USERS -->
<div class="category-card color-3"
     style="cursor:pointer"
     onclick="location.href='<?= dirname($_SERVER['PHP_SELF']) ?>/user1.php'">
    <i class="fas fa-user-cog fa-2x"></i>
    <h2>MANAGE USERS</h2>
</div>



<div class="category-card color-4" onclick="window.location='super_admin_reset_form.php'">
 <i class="fas fa-key fa-2x"></i>
 <h2>RESET PASSWORD</h2>
</div>

<div class="category-card color-5" onclick="window.location='admin_return_approval.php'">
 <i class="fas fa-sync-alt fa-2x"></i>
 <h2>RETURN APPROVAL</h2>
</div>

</div>
</div>

<div class="footer-bottom">
 DESIGN AND DEVELOPED BY 15 CESR
</div>

<script>
setInterval(()=>{
 document.getElementById("dateTimeDisplay").innerText =
 new Date().toLocaleString("en-IN",{timeZone:"Asia/Kolkata"});
},1000);
</script>

</body>
</html>
