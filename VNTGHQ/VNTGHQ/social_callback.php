<?php
/**
 * social_callback.php — Firebase OAuth → PHP session + MySQL upsert
 */
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');
require 'db.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || empty($data['uid'])) {
    echo json_encode(['ok'=>false,'error'=>'Missing payload']); exit;
}

$provider_uid = preg_replace('/[^a-zA-Z0-9_\-]/', '', $data['uid']);
$firstname    = htmlspecialchars(strip_tags(trim($data['firstname'] ?? 'User')));
$lastname     = htmlspecialchars(strip_tags(trim($data['lastname']  ?? '')));
$email        = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
$photo_url    = filter_var($data['photoURL'] ?? '', FILTER_SANITIZE_URL);
$provider     = in_array($data['provider'] ?? '', ['google','facebook']) ? $data['provider'] : 'google';

try {
    $pdo = db();
    // Upsert: if user with this provider_uid exists, update; else insert
    $stmt = $pdo->prepare(
        'SELECT id FROM users WHERE provider_uid=? AND provider=? LIMIT 1'
    );
    $stmt->execute([$provider_uid, $provider]);
    $row = $stmt->fetch();

    if ($row) {
        // Update profile
        $pdo->prepare(
            'UPDATE users SET firstname=?,lastname=?,email=?,photo_url=?,updated_at=NOW() WHERE id=?'
        )->execute([$firstname, $lastname, $email, $photo_url, $row['id']]);
        $user_id = $row['id'];
    } else {
        // Try to find by email first (might already exist as local user)
        $byEmail = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $byEmail->execute([$email]);
        $emailRow = $byEmail->fetch();
        if ($emailRow) {
            // Link social provider to existing account
            $pdo->prepare(
                'UPDATE users SET provider=?,provider_uid=?,photo_url=?,updated_at=NOW() WHERE id=?'
            )->execute([$provider, $provider_uid, $photo_url, $emailRow['id']]);
            $user_id = $emailRow['id'];
        } else {
            // New user — auto-generate a username from email/provider
            $baseUser = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0]));
            $username = $baseUser ?: strtolower($provider) . '_user';
            // Ensure unique username
            $count = 0;
            $tryUser = $username;
            while (true) {
                $u = $pdo->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
                $u->execute([$tryUser]);
                if (!$u->fetch()) break;
                $tryUser = $username . (++$count);
            }
            $pdo->prepare(
                'INSERT INTO users (username,firstname,lastname,email,password_hash,photo_url,provider,provider_uid)
                 VALUES (?,?,?,?,\'\',?,?,?)'
            )->execute([$tryUser, $firstname, $lastname, $email, $photo_url, $provider, $provider_uid]);
            $user_id  = $pdo->lastInsertId();
            $username = $tryUser;
        }
    }

    // Fetch full row
    $full = $pdo->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
    $full->execute([$user_id]);
    $user = $full->fetch();

    // Populate session
    $_SESSION['user_id']         = $user['id'];
    $_SESSION['user']            = $user['username'];
    $_SESSION['firstname']       = $user['firstname'];
    $_SESSION['lastname']        = $user['lastname'];
    $_SESSION['email']           = $user['email'];
    $_SESSION['phone']           = $user['phone'];
    $_SESSION['photo_url']       = $photo_url ?: ($user['photo_url'] ?? '');
    $_SESSION['social_provider'] = ucfirst($provider);
    $_SESSION['login_time']      = time();

    echo json_encode(['ok'=>true,'uid'=>$user_id]);

} catch (PDOException $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
