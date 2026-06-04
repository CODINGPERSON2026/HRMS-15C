<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Select Login Role</title>

<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body {
    background-image: url('photo/bg.jpg');
    background-size: cover;
    background-position: center;
    min-height: 100vh;
    font-family: Arial, sans-serif;
}

/* dark overlay */
.overlay {
    background: rgba(0,0,0,0.65);
    min-height: 100vh;
    display: flex;
    align-items: center;
}

/* role card */
.role-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 35px 25px;
    text-align: center;
    box-shadow: 0 12px 30px rgba(0,0,0,0.4);
    transition: all 0.3s ease;
}

.role-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 18px 40px rgba(0,0,0,0.5);
}

.role-card img {
    height: 160px;
    margin-bottom: 20px;
}

.role-title {
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 8px;
}

.role-desc {
    font-size: 14px;
    color: #555;
}

/* different border colors */
.super-admin { border-top: 6px solid #dc3545; }
.admin { border-top: 6px solid #007bff; }
.user { border-top: 6px solid #28a745; }
</style>
</head>

<body>

<div class="overlay">
    <div class="container">
        <div class="row justify-content-center text-center">

            <!-- SUPER ADMIN -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="admin_loginform.php?role=super_admin" style="text-decoration:none;color:#000;">
                    <div class="role-card super-admin">
                        <img src="t3.png" alt="Super Admin">
                        <div class="role-title">Super Admin</div>
                        <div class="role-desc">Full system control & permissions</div>
                    </div>
                </a>
            </div>

            <!-- ADMIN -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="admin_loginform.php?role=admin" style="text-decoration:none;color:#000;">
                    <div class="role-card admin">
                        <img src="t2.png" alt="Admin">
                        <div class="role-title">Admin</div>
                        <div class="role-desc">Inventory & user management</div>
                    </div>
                </a>
            </div>

            <!-- USER -->
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="user_loginform.php?role=user" style="text-decoration:none;color:#000;">
                    <div class="role-card user">
                        <img src="t1.png" alt="User">
                        <div class="role-title">User</div>
                        <div class="role-desc">View & request equipment</div>
                    </div>
                </a>
            </div>

        </div>
    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
