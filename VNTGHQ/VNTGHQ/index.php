<?php
session_start();
// If already logged in, go to dashboard; otherwise go to login
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
