<?php
session_start();
require_once 'connect.php';
require_once 'auth.php';
require_user();

/* 🔐 USER GUARD */
if (
    empty($_SESSION['id']) ||
    empty($_SESSION['role']) ||
    $_SESSION['role'] !== 'user'
) {
    header("location:logout.php");
    exit;
}

$msg = '';
$msgType = '';

/* ================= FORM SUBMIT ================= */
if (isset($_POST['change_password'])) {

    $id      = $_SESSION['id'];
    $old     = $_POST['old_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) {
        $msg = "New password & confirm password do not match";
        $msgType = "danger";
    } else {

        /* FETCH CURRENT PASSWORD */
        $stmt = $connect->prepare("
            SELECT password 
            FROM users 
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($dbpass);
        $stmt->fetch();
        $stmt->close();

        if (!$dbpass || !password_verify($old, $dbpass)) {
            $msg = "Wrong current password";
            $msgType = "danger";
        } else {

            /* UPDATE PASSWORD */
            $newhash = password_hash($new, PASSWORD_DEFAULT);

            $stmt = $connect->prepare("
                UPDATE users 
                SET password = ? 
                WHERE id = ?
            ");
            $stmt->bind_param("si", $newhash, $id);
            $stmt->execute();
            $stmt->close();

            echo "<script>
                alert('Password updated successfully. Please login again.');
                window.location='logout.php';
            </script>";
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Change Password</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:linear-gradient(135deg,#e3f2fd,#c8e6c9);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family:'Segoe UI',sans-serif;
}
.card{
    width:380px;
    border-radius:16px;
    box-shadow:0 8px 22px rgba(0,0,0,.25);
}
</style>
</head>

<body>

<div class="card p-4">
<h4 class="text-center mb-3">🔐 Change Password</h4>

<?php if($msg){ ?>
<div class="alert alert-<?= $msgType ?> text-center">
<?= htmlspecialchars($msg) ?>
</div>
<?php } ?>

<form method="POST">

<input type="password"
       name="old_password"
       class="form-control mb-2"
       placeholder="Current Password"
       required>

<input type="password"
       name="new_password"
       class="form-control mb-2"
       placeholder="New Password"
       required>

<input type="password"
       name="confirm_password"
       class="form-control mb-3"
       placeholder="Confirm New Password"
       required>

<button type="submit"
        name="change_password"
        class="btn btn-primary w-100 mb-2">
Update Password
</button>

<a href="user_dboard.php" class="btn btn-secondary w-100">
⬅ Back
</a>

</form>
</div>

</body>
</html>
