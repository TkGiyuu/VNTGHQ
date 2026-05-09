<?php
/**
 * admin/auth_admin.php
 * Include at the top of every admin page.
 * Redirects non-admins to admin login.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
