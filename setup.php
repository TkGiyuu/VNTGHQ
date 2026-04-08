<?php

// Connect without selecting a DB first so we can create it
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'vntghq';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    //  users 
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
        `created_at`    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email    (`email`),
        INDEX idx_provider (`provider`, `provider_uid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    //cart 
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

    // favourites
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

    //orders
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
        `status`          ENUM('pending','processing','shipped','delivered','cancelled')
                          NOT NULL DEFAULT 'pending',
        `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        INDEX idx_user (`user_id`),
        INDEX idx_status (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    //  order_items 
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

    //  feedback
    $pdo->exec("CREATE TABLE IF NOT EXISTS `feedback` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id`    INT UNSIGNED NOT NULL,
        `rating`     TINYINT UNSIGNED NOT NULL DEFAULT 5,
        `category`   VARCHAR(60) NOT NULL DEFAULT 'general',
        `message`    TEXT,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo '<div style="font-family:sans-serif;padding:40px;max-width:600px">
          <h2 style="color:#16a34a">✅ Database Setup Complete!</h2>
          <p>The following tables were created in <strong>' . $dbname . '</strong>:</p>
          <ul>
            <li><code>users</code> — accounts, social login</li>
            <li><code>cart</code> — cart items per user</li>
            <li><code>favourites</code> — saved items per user</li>
            <li><code>orders</code> — placed orders</li>
            <li><code>order_items</code> — items in each order</li>
            <li><code>feedback</code> — user feedback & ratings</li>
          </ul>
          <p style="color:#dc2626"><strong>⚠️ Delete or rename setup.php now for security.</strong></p>
          <a href="login.php" style="display:inline-block;background:#0ea5e9;color:white;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:700">
            Go to Login →
          </a>
          </div>';

} catch (PDOException $e) {
    echo '<div style="font-family:sans-serif;padding:40px;color:#dc2626">
          <h2>Setup Failed</h2>
          <p>Make sure XAMPP MySQL is running and the root credentials are correct in this file.</p>
          <pre>' . htmlspecialchars($e->getMessage()) . '</pre>
          </div>';
}
