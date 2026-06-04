<?php
session_start();

require_once "auth.php";
require_once "connect.php";

require_super_admin(); // 🔐 ONLY SUPER ADMIN

/* FORM SUBMIT */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id = (int)($_POST['user_id'] ?? 0);
    $new_pass = trim($_POST['new_password'] ?? '');

    if ($user_id <= 0 || $new_pass === '') {
        $msg = "❌ Invalid input";
    } else {

        $hash = password_hash($new_pass, PASSWORD_DEFAULT);

        $stmt = $connect->prepare("
            UPDATE users 
            SET password = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("si", $hash, $user_id);

        if ($stmt->execute()) {
            $msg = "✅ Password reset successfully";
        } else {
            $msg = "❌ Failed to reset password";
        }
        $stmt->close();
    }
}

/* FETCH USERS */
$users = $connect->query("
    SELECT id, username, role, unit 
    FROM users 
    ORDER BY role, username
");
?>
<!DOCTYPE html>
<html>
<head>
<title>Super Admin – Reset Password</title>
<link rel="stylesheet" href="css/bootstrap.min.css">

<style>
body{
    background:#eef5ff;
    font-family:Segoe UI;
}
.card{
    max-width:520px;
    margin:60px auto;
    padding:25px;
    border-radius:16px;
    box-shadow:0 8px 20px rgba(0,0,0,.15);
}
h4{
    font-weight:800;
    color:#004d40;
    margin-bottom:20px;
    text-align:center;
}
</style>
</head>

<body>

<div class="card">

<h4>🔐 SUPER ADMIN – RESET PASSWORD</h4>

<?php if($msg): ?>
<div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="post">

<div class="mb-3">
    <label class="form-label fw-bold">Select User</label>
    <select name="user_id" class="form-select" required>
        <option value="">-- Select User --</option>
        <?php while($u = $users->fetch_assoc()): ?>
            <option value="<?= $u['id'] ?>">
                <?= strtoupper($u['username']) ?>
                (<?= $u['role'] ?> | <?= $u['unit'] ?>)
            </option>
        <?php endwhile; ?>
    </select>
</div>

<div class="mb-3">
    <label class="form-label fw-bold">New Password</label>
    <input type="text" name="new_password" class="form-control" required>
</div>

<div class="d-flex justify-content-between">
    <a href="dboard.php" class="btn btn-secondary">⬅ Back</a>
    <button type="submit" class="btn btn-danger">
        🔁 Reset Password
    </button>
</div>

</form>

</div>

</body>
</html>
