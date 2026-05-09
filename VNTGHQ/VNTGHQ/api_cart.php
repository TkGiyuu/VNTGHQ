<?php
/**
 * api_cart.php — Cart sync API (called by main.js)
 * POST {"action":"sync","items":[...]}  → save cart to MySQL
 * GET  ?action=load                     → return cart from MySQL
 */
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit;
}
$uid = (int)$_SESSION['user_id'];
$pdo = db();

$action = $_GET['action'] ?? (json_decode(file_get_contents('php://input'),true)['action'] ?? '');

if ($action === 'load') {
    $rows = $pdo->prepare('SELECT product_id as id,name,price,img,tag,qty FROM cart WHERE user_id=? ORDER BY added_at');
    $rows->execute([$uid]);
    echo json_encode(['ok'=>true,'items'=>$rows->fetchAll()]); exit;
}

if ($action === 'sync') {
    $data  = json_decode(file_get_contents('php://input'), true);
    $items = $data['items'] ?? [];
    // Clear existing cart and replace
    $pdo->prepare('DELETE FROM cart WHERE user_id=?')->execute([$uid]);
    $ins = $pdo->prepare(
        'INSERT INTO cart (user_id,product_id,name,price,img,tag,qty) VALUES (?,?,?,?,?,?,?)'
    );
    foreach ($items as $item) {
        $ins->execute([
            $uid,
            substr($item['id']   ?? '', 0, 120),
            substr($item['name'] ?? '', 0, 200),
            substr($item['price']?? '', 0, 30),
            $item['img'] ?? '',
            substr($item['tag']  ?? '', 0, 60),
            max(1, (int)($item['qty'] ?? 1))
        ]);
    }
    echo json_encode(['ok'=>true]); exit;
}

echo json_encode(['ok'=>false,'error'=>'Unknown action']);
