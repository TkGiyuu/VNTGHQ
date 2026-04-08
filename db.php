<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');      
define('DB_PASS', '');           
define('DB_NAME', 'vntghq');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            // Show a clean error instead of stack trace
            http_response_code(500);
            die('<div style="font-family:sans-serif;padding:40px;color:#dc2626">
                <h2>Database Error</h2>
                <p>Could not connect to MySQL. Please check:<br>
                1. XAMPP Apache &amp; MySQL are running<br>
                2. The database <strong>' . DB_NAME . '</strong> exists (run <code>setup.php</code>)<br>
                3. Credentials in <code>db.php</code> are correct</p>
                <pre>' . htmlspecialchars($e->getMessage()) . '</pre>
                <a href="setup.php">Run Database Setup →</a>
                </div>');
        }
    }
    return $pdo;
}
