<?php
/**
 * setup.php — Run this ONCE to create all database tables + admin account.
 * Visit: http://localhost/vntghq/setup.php
 * ⚠️ Delete or rename this file after running it.
 */

$host   = 'localhost';
$user   = 'root';
$pass   = '';
$dbname = 'vntghq';

// Admin credentials — change before running if you want a different password
$adminUsername  = 'admin';
$adminFirstname = 'VNTG';
$adminLastname  = 'Admin';
$adminEmail     = 'admin@vntghq.com';
$adminPassword  = 'Admin@1234';   // ← Change this!

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // ── users ──────────────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username`      VARCHAR(60)  NOT NULL UNIQUE,
        `firstname`     VARCHAR(80)  NOT NULL DEFAULT '',
        `lastname`      VARCHAR(80)  NOT NULL DEFAULT '',
        `email`         VARCHAR(160) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL DEFAULT '',
        `phone`         VARCHAR(30)  NOT NULL DEFAULT '',
        `photo_url`     TEXT,
        `provider`      ENUM('local','google','facebook') NOT NULL DEFAULT 'local',
        `provider_uid`  VARCHAR(200) DEFAULT NULL,
        `is_admin`      TINYINT(1)  NOT NULL DEFAULT 0,
        `status`        ENUM('active','suspended') NOT NULL DEFAULT 'active',
        `created_at`    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email    (`email`),
        INDEX idx_provider (`provider`, `provider_uid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── cart ───────────────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `cart` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id`    INT UNSIGNED NOT NULL,
        `product_id` VARCHAR(120) NOT NULL,
        `name`       VARCHAR(200) NOT NULL,
        `price`      VARCHAR(30)  NOT NULL,
        `img`        TEXT,
        `tag`        VARCHAR(60)  NOT NULL DEFAULT '',
        `qty`        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
        `added_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_product (`user_id`, `product_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── favourites ─────────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `favourites` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id`    INT UNSIGNED NOT NULL,
        `product_id` VARCHAR(120) NOT NULL,
        `name`       VARCHAR(200) NOT NULL,
        `price`      VARCHAR(30)  NOT NULL,
        `img`        TEXT,
        `tag`        VARCHAR(60)  NOT NULL DEFAULT '',
        `added_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_fav (`user_id`, `product_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── orders ─────────────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `order_ref`       VARCHAR(30)  NOT NULL UNIQUE,
        `user_id`         INT UNSIGNED NOT NULL,
        `ship_firstname`  VARCHAR(80)  NOT NULL,
        `ship_lastname`   VARCHAR(80)  NOT NULL,
        `ship_email`      VARCHAR(160) NOT NULL,
        `ship_phone`      VARCHAR(30)  NOT NULL DEFAULT '',
        `ship_street`     VARCHAR(255) NOT NULL,
        `ship_city`       VARCHAR(100) NOT NULL,
        `ship_province`   VARCHAR(100) NOT NULL DEFAULT '',
        `ship_zip`        VARCHAR(10)  NOT NULL DEFAULT '',
        `delivery_option` VARCHAR(30)  NOT NULL DEFAULT 'standard',
        `payment_method`  VARCHAR(30)  NOT NULL DEFAULT 'cod',
        `subtotal`        DECIMAL(10,2) NOT NULL DEFAULT 0,
        `discount`        DECIMAL(10,2) NOT NULL DEFAULT 0,
        `shipping_fee`    DECIMAL(10,2) NOT NULL DEFAULT 0,
        `total`           DECIMAL(10,2) NOT NULL DEFAULT 0,
        `status`          ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
        `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        INDEX idx_user (`user_id`),
        INDEX idx_status (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── order_items ────────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `order_items` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `order_id`   INT UNSIGNED NOT NULL,
        `product_id` VARCHAR(120) NOT NULL,
        `name`       VARCHAR(200) NOT NULL,
        `price`      VARCHAR(30)  NOT NULL,
        `img`        TEXT,
        `qty`        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── feedback ───────────────────────────────────────────────────────────────
    $pdo->exec("CREATE TABLE IF NOT EXISTS `feedback` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id`    INT UNSIGNED NOT NULL,
        `rating`     TINYINT UNSIGNED NOT NULL DEFAULT 5,
        `category`   VARCHAR(60) NOT NULL DEFAULT 'general',
        `message`    TEXT,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Create admin account ───────────────────────────────────────────────────
    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $chk  = $pdo->prepare('SELECT id FROM users WHERE username=? OR email=? LIMIT 1');
    $chk->execute([$adminUsername, $adminEmail]);
    if (!$chk->fetch()) {
        $pdo->prepare(
            'INSERT INTO users (username,firstname,lastname,email,password_hash,provider,is_admin)
             VALUES (?,?,?,?,?,\'local\',1)'
        )->execute([$adminUsername, $adminFirstname, $adminLastname, $adminEmail, $hash]);
        $adminCreated = true;
    } else {
        // Ensure existing admin account has is_admin = 1
        $pdo->prepare('UPDATE users SET is_admin=1 WHERE username=?')->execute([$adminUsername]);
        $adminCreated = false;
    }

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <title>VNTG HQ Setup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body{background:#f1f5f9;font-family:"DM Sans",sans-serif}
    .card{border-radius:16px;border:none;box-shadow:0 4px 24px rgba(0,0,0,.08)}
    .brand{font-family:"Bebas Neue",cursive;letter-spacing:2px;font-size:28px;color:#0c1a2e}
    .badge-success{background:#dcfce7;color:#16a34a;padding:4px 12px;border-radius:50px;font-size:12px;font-weight:700}
    .cred-box{background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:16px 20px}
    </style></head><body class="d-flex align-items-center justify-content-center min-vh-100 p-3">';

    echo '<div class="card p-5" style="max-width:560px;width:100%">
      <div class="text-center mb-4">
        <div style="width:64px;height:64px;background:#0ea5e9;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:white">
          <i class="bi bi-bag-heart-fill" style="font-family:bootstrap-icons"></i>✅
        </div>
        <p class="brand">VNTG HQ</p>
        <h4 class="fw-bold mb-1">Database Setup Complete!</h4>
        <span class="badge-success">All tables created</span>
      </div>

      <p class="text-muted small mb-3">The following tables were created in <strong>' . $dbname . '</strong>:</p>
      <ul class="list-unstyled mb-4" style="font-size:14px">
        <li class="mb-1">✅ <code>users</code> — accounts + admin flag</li>
        <li class="mb-1">✅ <code>cart</code> — cart items per user</li>
        <li class="mb-1">✅ <code>favourites</code> — saved items</li>
        <li class="mb-1">✅ <code>orders</code> — placed orders</li>
        <li class="mb-1">✅ <code>order_items</code> — items per order</li>
        <li class="mb-1">✅ <code>feedback</code> — ratings & messages</li>
      </ul>

      <div class="cred-box mb-4">
        <p class="fw-bold mb-2" style="font-size:14px">🔐 Admin Account ' . ($adminCreated ? 'Created' : 'Already Exists — Updated') . ':</p>
        <table style="font-size:13px;width:100%">
          <tr><td class="text-muted pe-3">Username</td><td><strong>' . htmlspecialchars($adminUsername) . '</strong></td></tr>
          <tr><td class="text-muted pe-3">Password</td><td><strong>' . htmlspecialchars($adminPassword) . '</strong></td></tr>
          <tr><td class="text-muted pe-3">Admin URL</td><td><a href="admin/login.php">admin/login.php</a></td></tr>
        </table>
      </div>

      <div class="alert alert-danger py-2 mb-4" style="font-size:13px">
        ⚠️ <strong>Delete or rename setup.php</strong> after this setup is complete.
      </div>

      <div class="d-flex gap-2">
        <a href="login.php" class="btn btn-primary fw-bold flex-fill" style="background:#0ea5e9;border:none;border-radius:8px">User Login →</a>
        <a href="admin/login.php" class="btn fw-bold flex-fill" style="background:#0c1a2e;color:white;border:none;border-radius:8px">Admin Login →</a>
      </div>
    </div></body></html>';

} catch (PDOException $e) {
    echo '<div style="font-family:sans-serif;padding:40px;color:#dc2626">
          <h2>Setup Failed</h2>
          <p>Make sure XAMPP MySQL is running.</p>
          <pre>' . htmlspecialchars($e->getMessage()) . '</pre></div>';
}
