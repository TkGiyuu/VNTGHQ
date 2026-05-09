<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Prevent browser caching protected pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

// user_id is set on login (MySQL row id)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
