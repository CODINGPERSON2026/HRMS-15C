<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Select Role</title>

<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
html, body {
    height: 100%;
    margin: 0;
}

body {
    background: #5f5f5f;   /* same grey background */
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: Arial, sans-serif;
}

/* MAIN ROW */
.role-container {
    width: 100%;
    display: flex;
    justify-content: space-evenly; /* left center right */
    align-items: center;
}

/* ROLE BOX */
.role-box {
    background: #fff;
    width: 260px;
    padding: 20px 15px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 8px 20px rgba(0,0,0,0.35);
    transition: all 0.3s ease;
}

.role-box:hover {
    transform: translateY(-6px);
}

/* IMAGE */
.role-box img {
    height: 110px;
    margin-bottom: 12px;
}

/* TITLE */
.role-title {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 10px;
}

/* LOGIN BUTTON */
.role-btn {
    display: inline-block;
    padding: 6px 22px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
    color: #fff;
}

/* COLORS */
.super-admin { border-top: 5px solid #dc3545; }
.admin       { border-top: 5px solid #007bff; }
.user        { border-top: 5px solid #28a745; }

.super-admin .role-btn { background: #dc3545; }
.admin .role-btn       { background: #007bff; }
.user .role-btn        { background: #28a745; }

/* LINK RESET */
a {
    text-decoration: none;
    color: inherit;
}
</style>
</head>

<body>

<div class="role-container">

    <!-- SUPER ADMIN -->
    <a href="superadmin_loginform.php?role=super_admin">
        <div class="role-box super-admin">
            <img src="t2.png" alt="Super Admin">
            <div class="role-title">SUPER ADMIN</div>
            <span class="role-btn">LOGIN</span>
        </div>
    </a>

    <!-- ADMIN -->
    <a href="admin_loginform.php?role=admin">
        <div class="role-box admin">
            <img src="t2.png" alt="Admin">
            <div class="role-title">ADMIN</div>
            <span class="role-btn">LOGIN</span>
        </div>
    </a>

    <!-- USER -->
    <a href="user_loginform.php?role=user">
        <div class="role-box user">
            <img src="t1.png" alt="User">
            <div class="role-title">USER</div>
            <span class="role-btn">LOGIN</span>
        </div>
    </a>

</div>

</body>
</html>
