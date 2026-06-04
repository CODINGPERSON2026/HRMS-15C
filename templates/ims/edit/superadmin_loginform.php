<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body{
            font-family: Arial, sans-serif;
            background: url('image.png') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container{
            width: 330px;
            padding: 40px;
            background: rgba(255,255,255,0.10);
            border-radius: 25px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.35);
            box-shadow: 0 0 30px rgba(0,0,0,0.4);
            text-align: center;
        }

        .container h2{
            color: red;
            font-weight: 800;
            margin: 5px 0 15px;
        }

        .form-group{
            position: relative;
            margin-bottom: 20px;
        }

        .form-group input{
            width: 75%;
            padding: 12px 50px;
            border-radius: 50px;
            border: none;
            outline: none;
            background: rgba(255,255,255,0.95);
            font-size: 15px;
        }

        .form-group i.fa-user,
        .form-group i.fa-lock{
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #333;
            font-size: 17px;
        }

        .toggle-password{
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #333;
            font-size: 17px;
        }

        .btn-login{
            width: 100%;
            padding: 13px;
            border-radius: 50px;
            background: #0066FF;
            border: none;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-login:hover{
            background: #004ec7;
        }

        .remember-me{
            display: flex;
            justify-content: center;
            margin-top: -10px;
            color: white;
            font-size: 14px;
        }

        .remember-me input{
            margin-right: 8px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>INVENTORY MANAGEMENT SYSTEM</h2>
    <h2 style="color:white;font-weight:600;">SUPER ADMIN LOGIN</h2>

    <form action="../login_check.php" method="POST">

        <!-- IMPORTANT -->
        <input type="hidden" name="login_type" value="super_admin">

        <div class="form-group">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="nam" placeholder="Enter Super Admin Username" required>
        </div>

        <div class="form-group">
            <i class="fa-solid fa-lock"></i>
            <input type="password" id="pass" name="pass" placeholder="Enter Password" required>
            <i class="fa-solid fa-eye toggle-password" onclick="togglePasswordVisibility()"></i>
        </div>

        <div class="remember-me">
            <input type="checkbox" id="remember">
            <label for="remember">Remember me</label>
        </div>

        <button type="submit" class="btn-login" name="submit">Login</button>
    </form>
</div>

<script>
function togglePasswordVisibility() {
    let passwordField = document.getElementById("pass");
    let icon = document.querySelector(".toggle-password");

    if (passwordField.type === "password") {
        passwordField.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        passwordField.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}
</script>

</body>
</html>