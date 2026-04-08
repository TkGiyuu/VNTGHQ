<?php
session_start();
session_unset();
session_destroy();

// Kill browser cache so back button won't restore a session page
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
header('Location: login.php');
exit;
