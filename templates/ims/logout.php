<?php

session_start();

/* REMOVE ALL SESSION */
$_SESSION = array();

/* DESTROY SESSION */
session_destroy();

/* DELETE SESSION COOKIE */
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* CACHE DISABLE */
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

header("Location: index.php");
exit();

?>