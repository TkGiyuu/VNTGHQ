<?php
/**
 * api_order.php — Place an order into MySQL
 * POST JSON order payload → saves to orders + order_items tables
 */
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok'=>false,'error'=>'Not logged in']); exit;
}
$uid  = (int)$_SESSION['user_id'];
$pdo  = db();
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) { echo json_encode(['ok'=>false,'error'=>'No data']); exit; }

$ship    = $data['shipping'] ?? [];
$summary = $data['summary']  ?? [];
$items   = $data['items']    ?? [];
$payment = $data['payment']  ?? [];

if (empty($items)) { echo json_encode(['ok'=>false,'error'=>'No items']); exit; }

try {
    $pdo->beginTransaction();

    // Generate order reference
    $orderRef = 'ORD-' . strtoupper(substr(uniqid(), -6)) . '-' . date('Ymd');

    $ins = $pdo->prepare(
        'INSERT INTO orders
         (order_ref,user_id,ship_firstname,ship_lastname,ship_email,ship_phone,
          ship_street,ship_city,ship_province,ship_zip,delivery_option,
          payment_method,subtotal,discount,shipping_fee,total,status)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,\'pending\')'
    );
    $ins->execute([
        $orderRef, $uid,
        substr($ship['firstname'] ?? '', 0, 80),
        substr($ship['lastname']  ?? '', 0, 80),
        substr($ship['email']     ?? '', 0, 160),
        substr($ship['phone']     ?? '', 0, 30),
        substr($ship['street']    ?? '', 0, 255),
        substr($ship['city']      ?? '', 0, 100),
        substr($ship['province']  ?? '', 0, 100),
        substr($ship['zip']       ?? '', 0, 10),
        substr($ship['delivery']  ?? 'standard', 0, 30),
        substr($payment['method'] ?? 'cod', 0, 30),
        (float)($summary['subtotal'] ?? 0),
        (float)($summary['discount'] ?? 0),
        (float)($summary['shipping'] ?? 0),
        (float)($summary['total']    ?? 0),
    ]);
    $orderId = (int)$pdo->lastInsertId();

    // Insert order items
    $itemIns = $pdo->prepare(
        'INSERT INTO order_items (order_id,product_id,name,price,img,qty) VALUES (?,?,?,?,?,?)'
    );
    foreach ($items as $item) {
        $itemIns->execute([
            $orderId,
            substr($item['id']   ?? '', 0, 120),
            substr($item['name'] ?? '', 0, 200),
            substr($item['price']?? '', 0, 30),
            $item['img'] ?? '',
            max(1, (int)($item['qty'] ?? 1))
        ]);
    }

    // Clear cart for this user
    $pdo->prepare('DELETE FROM cart WHERE user_id=?')->execute([$uid]);

    $pdo->commit();
    echo json_encode(['ok'=>true,'order_id'=>$orderId,'order_ref'=>$orderRef]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
