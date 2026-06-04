<?php
session_start();
require_once 'connect.php';

date_default_timezone_set('Asia/Kolkata');

if (!isset($_POST['submit'])) {
    header("Location: edit/user_loginform.php");
    exit;
}

$username   = trim($_POST['nam'] ?? '');
$password   = $_POST['pass'] ?? '';
$login_type = $_POST['login_type'] ?? '';
$time       = date('Y-m-d H:i:s');

if ($username === '' || $password === '') {
    echo "<script>
        alert('❌ Username or Password missing');
        window.history.back();
    </script>";
    exit;
}

/* =========================
   FETCH USER
========================= */
$sql = "
SELECT id, username, password, role, unit, status
FROM users
WHERE username = ?
LIMIT 1
";

$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {

    /* BLOCKED USER */
    if ($row['status'] !== 'ACTIVE') {
        echo "<script>
            alert('❌ Your account is blocked. Contact Admin.');
            window.history.back();
        </script>";
        exit;
    }

    /* PASSWORD VERIFY */
    if (password_verify($password, $row['password'])) {

        /* =========================
           ROLE CHECK
        ========================= */

        // ADMIN LOGIN PAGE
        if ($login_type === 'admin') {
            if (!in_array($row['role'], ['admin', 'super_admin'])) {
                echo "<script>
                    alert('❌ Access Denied: Admin Only');
                    window.location.href='edit/admin_loginform.php';
                </script>";
                exit;
            }
        }

        // USER LOGIN PAGE
        if ($login_type === 'user') {
            if ($row['role'] !== 'user') {
                echo "<script>
                    alert('❌ Access Denied: User Only');
                    window.location.href='edit/user_loginform.php';
                </script>";
                exit;
            }
        }

        // SUPER ADMIN LOGIN PAGE
        if ($login_type === 'super_admin') {
            if ($row['role'] !== 'super_admin') {
                echo "<script>
                    alert('❌ Access Denied: Super Admin Only');
                    window.location.href='edit/superadmin_loginform.php';
                </script>";
                exit;
            }
        }

        /* =========================
           SESSION
        ========================= */
        session_regenerate_id(true);

        $_SESSION['id']       = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role']     = $row['role'];
        $_SESSION['unit']     = $row['unit'];

        /* UPDATE LAST LOGIN */
        $upd = mysqli_prepare($connect,
            "UPDATE users SET last_login = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param($upd, "si", $time, $row['id']);
        mysqli_stmt_execute($upd);

        /* LOGIN ACTIVITY LOG */
        $log = mysqli_prepare($connect,
            "INSERT INTO useractivitylog (userid, username, activity, datetime)
             VALUES (?, ?, 'login', ?)"
        );
        mysqli_stmt_bind_param($log, "iss", $row['id'], $row['username'], $time);
        mysqli_stmt_execute($log);

        /* =========================
           REDIRECT
        ========================= */
        if ($row['role'] === 'super_admin') {
            header("Location: super_admin_dboard.php");
        }
        elseif ($row['role'] === 'admin') {
            header("Location: dboard.php");
        }
        else {
            header("Location: user_dboard.php");
        }
        exit;
    }
}

/* LOGIN FAILED */
echo "<script>
    alert('❌ Invalid Username or Password');
    window.history.back();
</script>";
exit;
?>