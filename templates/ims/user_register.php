<?php
include('connect.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = "";
$msgType = "";

if (isset($_POST['submit'])) {

    $unit     = trim($_POST['unit']);
     $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $msg = "Passwords do not match";
        $msgType = "error";
    } else {

        // CHECK USERNAME ALREADY EXISTS
        $check = $connect->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $msg = "Username already registered";
            $msgType = "error";
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // INSERT USER AS PENDING
            $insert = $connect->prepare("
                INSERT INTO users 
                (unit, username, password, role, status)
                VALUES (?, ?, ?, 'user', 'PENDING')
            ");

            $insert->bind_param(
                "sss",
                $unit,
                $username,
                $hashedPassword
            );

            if ($insert->execute()) {
                $msg = "Registration successful. Awaiting admin approval.";
                $msgType = "success";
            } else {
                $msg = "Registration failed. Try again.";
                $msgType = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>User Registration</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:linear-gradient(135deg,#1e3c72,#2a5298);
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family:Segoe UI, Arial, sans-serif;
}

/* Card */
.reg-card{
    width:430px;
    background:#ffffff;
    padding:26px;
    border-radius:16px;
    box-shadow:0 20px 40px rgba(0,0,0,0.25);
}

/* Heading */
.reg-card h4{
    text-align:center;
    font-weight:700;
    margin-bottom:20px;
}

/* Inputs */
.form-control, select{
    border-radius:10px;
}

/* Buttons */
.btn{
    border-radius:10px;
    font-weight:600;
}

/* Back button */
.back-btn{
    margin-bottom:15px;
}
</style>
</head>

<body>

<div class="reg-card">

<!-- 🔙 BACK BUTTON -->
<a href="edit/au.php" class="btn btn-outline-secondary btn-sm back-btn w-100">
← Back to Login
</a>

<h4>USER REGISTRATION</h4>

<?php if($msg){ ?>
<div class="alert alert-<?= $msgType=='success'?'success':'danger' ?> text-center">
<?= htmlspecialchars($msg) ?>
</div>
<?php } ?>

<form method="POST">

<select name="unit" class="form-control mb-2" required>
<option value="">USER ID</option>
<option>HQ CQ STORE</option>
<option>1 COY CQ STORE</option>
<option>2 COY CQ STORE</option>
<option>3 COY CQ STORE</option>
<option>3 COY EFS STORE</option>
<option>3 COY MT STORE</option>
<option>3 COY RADIO ROOM</option>
<option>3 COY RR STORE</option>
<option>13 ENGR REGT</option>
<option>LINE STORE</option>
<option>ICN STORE</option>
<option>MCCS STORE</option>
<option>RP STORE</option>
<option>EFS STORE 1 COY</option>
<option>OP SHIVA STORE</option>
<option>MT STORE</option>
<option>LRW STORE</option>
<option>TM STORE</option>
<option>RHQ STORE</option>
<option>IT STORE</option>
<option>STRONG ROOM</option>
<option>CSO RES</option>
<option>NFS STORE</option>
<option>CABLE TV</option>
<option>OFFR MESS</option>
<option>JCO MESS</option>
<option>3 COY SYSTEM</option>
<option>SPORTS NCO</option>
<option>CSD</option>
<option>RAREMART</option>
<option>ACCT STORE</option>
<option>QM STORE</option>
<option>SIGS BR</option>
<option>RATION STORE</option>
<option>FOL STORE</option>
<option>LIBRARY</option>
<option>PROJECT STORE</option>
<option>CIPHER STORE</option>
<option>MANDIR STORE</option>
<option>REC ROOM</option>
<option>DELHI MS9</option>
<option>MES NCO</option>
<option>1/2 COY KOTE</option>
<option>HQ COY KOTE</option>
<option>AMN STORE</option>
<option>MI ROOM</option>
<option>15 COSR</option>
<option>EDN NCO</option>
<option>2 COY EFS STORE</option>
<option>VFSR</option>
<option>KFSR</option>
<option>FAD KHUNMOH</option>
<option>HQ 268 BDE</option>
<option>19 IDSR</option>
<option>28 IDSR</option>
<option>68 MBSC</option>
<option>79 MBSC</option>
<option>14 CESR</option>
<option>14 WEU</option>
<option>25 AD REGT</option>
<option>54 ADV VET HOSP</option>
<option>102 IBSC</option>
<option>109 IBSC</option>
<option>162 TA BN</option>
<option>619 ADBSC</option>
<option>4015 FD HOSP</option>
<option>15 CBS</option> 
<option>3 SECT RR</option>
<option>5 SECT RR</option>
<option>7 SECT RR</option>
<option>8 SECT RR</option>
<option>14 RR BN</option>
<option>15 CZW</option>
<option>113 FD REGT</option>
<option>JAKLI REGT</option>
<option>521 NSC</option>
<option>RADIO STORE</option>
<option>2 COY STRONG ROOM</option>
<option>SONAMARG DET</option>
<option>OAF STN HQ</option>
</select>

<input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
<input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
<input type="password" name="confirm_password" class="form-control mb-3" placeholder="Confirm Password" required>

<button type="submit" name="submit" class="btn btn-primary w-100">
Register
</button>

</form>
</div>

</body>
</html>
