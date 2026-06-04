<?php
session_start();
include('connect.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = "";
$msgType = "";

if (isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = mysqli_prepare($connect, "SELECT * FROM user WHERE username=?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {

        // check password
        if (password_verify($password, $row['password'])) {

            // check status
            if ($row['status'] !== 'approved') {
                $msg = "Your account is not approved yet.";
                $msgType = "error";
            } else {

               // login success
        session_regenerate_id(true);

        $_SESSION['id']       = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['unit']     = $row['unit'];
        $_SESSION['rank']     = $row['rank'];
        $_SESSION['role']     = 'user';

        /* update last login */
        date_default_timezone_set('Asia/Kolkata');

        $time = date('Y-m-d H:i:s');

        mysqli_query($connect,
        "UPDATE user SET last_login='$time' WHERE id='{$row['id']}'"
        );

        /* prevent cache */
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");

        header("Location: user_dashboard.php");
        exit();
            }

        } else {
            $msg = "Invalid username or password.";
            $msgType = "error";
        }

    } else {
        $msg = "Invalid username or password.";
        $msgType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background:linear-gradient(120deg,#1e3c72,#2a5298);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.login-card{
    background:#fff;
    width:100%;
    max-width:420px;
    border-radius:14px;
    box-shadow:0 25px 50px rgba(0,0,0,.35);
    overflow:hidden;
}

.login-header{
    background:#ff9800;
    padding:16px;
    text-align:center;
    font-size:22px;
    font-weight:bold;
    color:#000;
}

.login-body{
    padding:25px;
}

input{
    width:100%;
    padding:12px;
    margin:10px 0;
    border-radius:6px;
    border:1px solid #ccc;
    font-size:15px;
}

input:focus{
    outline:none;
    border-color:#2a5298;
}

button{
    width:100%;
    background:#2a5298;
    color:#fff;
    border:none;
    padding:14px;
    font-size:16px;
    border-radius:6px;
    cursor:pointer;
}

button:hover{
    background:#1e3c72;
}

.alert-error{
    background:#e74c3c;
    color:#fff;
    padding:12px;
    text-align:center;
    border-radius:6px;
    margin-bottom:15px;
    font-weight:600;
}

.links{
    text-align:center;
    margin-top:15px;
}

.links a{
    text-decoration:none;
    color:#2a5298;
    font-weight:600;
}
</style>
</head>

<body>

<div class="login-card">
    <div class="login-header">USER LOGIN</div>

    <div class="login-body">

        <?php if (!empty($msg)) { ?>
            <div class="alert-<?php echo $msgType; ?>">
                <?php echo $msg; ?>
            </div>
        <?php } ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>

            <button type="submit" name="login">Login</button>
        </form>

        <div class="links">
            <a href="user_register.php">New user? Register</a> |
            <a href="edit/au.php">Back</a>
        </div>
    </div>
</div>

</body>
</html>
