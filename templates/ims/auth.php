<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* NO CACHE */
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/* SUPER ADMIN */
function require_super_admin(){

    if(
        !isset($_SESSION['id']) ||
        ($_SESSION['role'] ?? '') !== 'super_admin'
    ){

        header("Location: logout.php");
        exit();
    }
}

/* ADMIN */
function require_admin(){

    if(
        !isset($_SESSION['id']) ||
        !in_array($_SESSION['role'] ?? '', ['admin','super_admin'])
    ){

        header("Location: logout.php");
        exit();
    }
}

/* USER */
function require_user(){

    if(
        !isset($_SESSION['id']) ||
        ($_SESSION['role'] ?? '') !== 'user'
    ){

        header("Location: logout.php");
        exit();
    }
}
?>