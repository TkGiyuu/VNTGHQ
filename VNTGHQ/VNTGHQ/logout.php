<?php
session_start();
// Only destroy admin session keys, not the whole session
unset($_SESSION['admin_id'], $_SESSION['admin_username'],
      $_SESSION['admin_firstname'], $_SESSION['admin_lastname'],
      $_SESSION['admin_email'], $_SESSION['admin_login_time']);
session_destroy();
header('Cache-Control: no-store');
header('Location: login.php');
exit;
