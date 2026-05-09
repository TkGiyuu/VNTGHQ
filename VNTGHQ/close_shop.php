<?php require 'auth_check.php';
require 'db.php';
$uid = (int)$_SESSION['user_id'];
db()->prepare('UPDATE users SET is_seller=0 WHERE id=?')->execute([$uid]);
db()->prepare('UPDATE listings SET status="inactive" WHERE seller_id=?')->execute([$uid]);
unset($_SESSION['is_seller'], $_SESSION['seller_shop_name']);
header('Location: profile.php'); exit;
