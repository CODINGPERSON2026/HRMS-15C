<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hide notices/warnings in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set("Asia/Kolkata");

// Define global constants
define("SITE_NAME", "Admin Dashboard");
define("SITE_URL", "http://localhost/your_project/");

// Security: regenerate session ID every 10 minutes
if (!isset($_SESSION['LAST_ACTIVITY'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
} elseif (time() - $_SESSION['LAST_ACTIVITY'] > 600) {
    session_regenerate_id(true);
    $_SESSION['LAST_ACTIVITY'] = time();
}
