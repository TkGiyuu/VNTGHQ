<?php require 'auth_check.php';
require 'db.php';
$uid = (int)$_SESSION['user_id'];

$firstname  = $_SESSION['firstname'] ?? 'User';
$lastname   = $_SESSION['lastname']  ?? '';
$username   = $_SESSION['user']      ?? 'user';
$loginTime  = $_SESSION['login_time'] ?? time();
$loginDay      = date('l', $loginTime);
$loginDate     = date('F j, Y', $loginTime);
$loginTimeStr  = date('h:i:s A', $loginTime);
$fullname      = trim("$firstname $lastname");

// Fetch real orders from MySQL
try {
    $ordStmt = db()->prepare(
        'SELECT o.order_ref, o.total, o.status, o.created_at,
                (SELECT oi.name FROM order_items oi WHERE oi.order_id=o.id LIMIT 1) AS first_item,
                (SELECT oi.img  FROM order_items oi WHERE oi.order_id=o.id LIMIT 1) AS first_img
         FROM orders o WHERE o.user_id=? ORDER BY o.created_at DESC LIMIT 5'
    );
    $ordStmt->execute([$uid]);
    $dbOrders = $ordStmt->fetchAll();
} catch(Exception $e){ $dbOrders=[]; }

// Fetch total order count and member since
try {
    $countRow = db()->prepare('SELECT COUNT(*) as cnt, MIN(created_at) as since FROM orders WHERE user_id=?');
    $countRow->execute([$uid]);
    $orderStats = $countRow->fetch();
    $totalOrders = (int)$orderStats['cnt'];
    $memberSince = $orderStats['since'] ? date('Y', strtotime($orderStats['since'])) : date('Y');
} catch(Exception $e){ $totalOrders=0; $memberSince=date('Y'); }

$statusMap = [
    'pending'    => ['label'=>'Pending',    'sc'=>'transit'],
    'processing' => ['label'=>'Processing', 'sc'=>'transit'],
    'shipped'    => ['label'=>'Shipped',    'sc'=>'transit'],
    'delivered'  => ['label'=>'Delivered',  'sc'=>'delivered'],
    'cancelled'  => ['label'=>'Cancelled',  'sc'=>'cancelled'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container-fluid px-4 py-4">

  <!-- Welcome Banner -->
  <div class="dash-welcome-banner mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <p class="dash-eyebrow mb-1">Good to see you back,</p>
      <h1 class="dash-welcome-name"><?= htmlspecialchars($fullname) ?> <span class="wave">👋</span></h1>
      <div class="dash-login-info mt-2">
        <i class="bi bi-clock-fill me-1"></i>
        Last login: <strong><?= $loginDay ?></strong>, <?= $loginDate ?> &mdash; <?= $loginTimeStr ?>
      </div>
    </div>
    <div class="dash-welcome-actions d-flex gap-2">
      <a href="homepage.php" class="btn dash-btn-primary"><i class="bi bi-bag me-2"></i>Shop Now</a>
      <a href="profile.php"  class="btn dash-btn-outline"><i class="bi bi-person me-2"></i>My Profile</a>
    </div>
  </div>

  <!-- Stat Cards Row -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:rgba(14,165,233,0.12);color:#0EA5E9">
          <i class="bi bi-bag-check-fill"></i>
        </div>
        <div class="dash-stat-info">
          <span class="dash-stat-num"><?= $totalOrders ?></span>
          <span class="dash-stat-label">Total Orders</span>
        </div>
        <span class="dash-stat-badge <?= $totalOrders>0?'up':'neutral' ?>">
          <?= $totalOrders>0 ? '<i class="bi bi-bag me-1"></i>'.$totalOrders.' order'.($totalOrders!==1?'s':'') : '<i class="bi bi-dash"></i>No orders yet' ?>
        </span>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:rgba(34,197,94,0.12);color:#22c55e">
          <i class="bi bi-cart3"></i>
        </div>
        <div class="dash-stat-info">
          <span class="dash-stat-num">2</span>
          <span class="dash-stat-label">Items in Cart</span>
        </div>
        <span class="dash-stat-badge neutral"><i class="bi bi-dash"></i>View cart</span>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:rgba(251,191,36,0.12);color:#f59e0b">
          <i class="bi bi-heart-fill"></i>
        </div>
        <div class="dash-stat-info">
          <span class="dash-stat-num">7</span>
          <span class="dash-stat-label">Wishlist Items</span>
        </div>
        <span class="dash-stat-badge up"><i class="bi bi-arrow-up-short"></i>+2 new</span>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:rgba(239,68,68,0.12);color:#ef4444">
          <i class="bi bi-tags-fill"></i>
        </div>
        <div class="dash-stat-info">
          <span class="dash-stat-num">5</span>
          <span class="dash-stat-label">My Listings</span>
        </div>
        <span class="dash-stat-badge neutral"><i class="bi bi-dash"></i>Manage</span>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Recent Orders -->
    <div class="col-lg-8">
      <div class="dash-card h-100">
        <div class="dash-card-header d-flex justify-content-between align-items-center mb-3">
          <h5 class="dash-card-title mb-0"><i class="bi bi-receipt me-2"></i>Recent Orders</h5>
          <a href="#" class="see-more">View all <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="table-responsive">
          <table class="table dash-table align-middle mb-0">
            <thead>
              <tr>
                <th>Item</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($dbOrders)): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">
                No orders yet. <a href="homepage.php">Start shopping!</a>
              </td></tr>
              <?php else: foreach ($dbOrders as $o):
                $st = $statusMap[$o['status']] ?? ['label'=>ucfirst($o['status']),'sc'=>'transit'];
              ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-3">
                    <img src="<?= htmlspecialchars($o['first_img'] ?: 'https://via.placeholder.com/48') ?>"
                         class="dash-order-img" alt="" onerror="this.src='https://via.placeholder.com/48'">
                    <span class="fw-semibold"><?= htmlspecialchars($o['first_item'] ?: 'Order') ?></span>
                  </div>
                </td>
                <td class="text-muted small"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                <td class="fw-bold" style="color:var(--brand-blue)">PHP <?= number_format($o['total'], 0) ?></td>
                <td><span class="order-badge order-<?= $st['sc'] ?>"><?= $st['label'] ?></span></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4 d-flex flex-column gap-4">

      <!-- Account Info Card -->
      <div class="dash-card">
        <h5 class="dash-card-title mb-3"><i class="bi bi-person-badge me-2"></i>Account Info</h5>
        <div class="dash-info-row">
          <span class="dash-info-label">Full Name</span>
          <span class="dash-info-val"><?= htmlspecialchars($fullname) ?></span>
        </div>
        <div class="dash-info-row">
          <span class="dash-info-label">Username</span>
          <span class="dash-info-val">@<?= htmlspecialchars($username) ?></span>
        </div>
        <div class="dash-info-row">
          <span class="dash-info-label">Member Since</span>
          <span class="dash-info-val"><?= $memberSince ?></span>
        </div>
        <div class="dash-info-row border-0 pb-0">
          <span class="dash-info-label">Rating</span>
          <span class="dash-info-val">
            <i class="bi bi-star-fill text-warning"></i>
            <i class="bi bi-star-fill text-warning"></i>
            <i class="bi bi-star-fill text-warning"></i>
            <i class="bi bi-star-fill text-warning"></i>
            <i class="bi bi-star-half text-warning"></i>
            <small class="ms-1 text-muted">4.9</small>
          </span>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="dash-card">
        <h5 class="dash-card-title mb-3"><i class="bi bi-lightning-fill me-2"></i>Quick Links</h5>
        <div class="d-flex flex-column gap-2">
          <a href="homepage.php" class="dash-quick-link"><i class="bi bi-house me-2"></i>Browse Items <i class="bi bi-chevron-right ms-auto"></i></a>
          <a href="cart.php"     class="dash-quick-link"><i class="bi bi-cart me-2"></i>My Cart <i class="bi bi-chevron-right ms-auto"></i></a>
          <a href="profile.php"  class="dash-quick-link"><i class="bi bi-gear me-2"></i>Account Settings <i class="bi bi-chevron-right ms-auto"></i></a>
          <a href="about.php"    class="dash-quick-link"><i class="bi bi-info-circle me-2"></i>About VNTG HQ <i class="bi bi-chevron-right ms-auto"></i></a>
        </div>
      </div>

    </div>
  </div>

  <!-- Recommended For You -->
  <div class="section-header d-flex justify-content-between align-items-center mt-5 mb-3">
    <h2 class="section-title"><i class="bi bi-stars me-2 text-warning"></i>Recommended For You</h2>
    <a href="homepage.php" class="see-more">See all <i class="bi bi-chevron-double-right"></i></a>
  </div>
  <div class="row g-3 mb-4">
    <?php
    $rec = [
      ['name'=>'Vintage Leather Jacket', 'price'=>'PHP 2,800', 'img'=>'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=300&q=80', 'tag'=>'Jacket'],
      ['name'=>'Classic White Tee',      'price'=>'PHP 450',   'img'=>'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=300&q=80', 'tag'=>'T-Shirt'],
      ['name'=>'Retro Sneakers',         'price'=>'PHP 1,950', 'img'=>'https://images.unsplash.com/photo-1465453869711-7e174808ace9?w=300&q=80', 'tag'=>'Shoes'],
      ['name'=>'Canvas Backpack',        'price'=>'PHP 990',   'img'=>'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=300&q=80', 'tag'=>'Bag'],
      ['name'=>'Gold Chain Necklace',    'price'=>'PHP 750',   'img'=>'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=300&q=80', 'tag'=>'Accessories'],
    ];
    foreach ($rec as $item): ?>
    <div class="col-6 col-md-4 col-lg-2half">
      <div class="product-card h-100 card-visible" data-pid="<?= strtolower(str_replace(' ','_',$item['name'])) ?>">
        <div class="product-img-wrap">
          <img src="<?= $item['img'] ?>" alt="<?= $item['name'] ?>" class="product-img">
          <span class="product-tag"><?= $item['tag'] ?></span>
          <button class="btn-wishlist" onclick="toggleFavourite(this)"><i class="bi bi-heart"></i></button>
        </div>
        <div class="product-info">
          <p class="product-name"><?= $item['name'] ?></p>
          <div class="d-flex justify-content-between align-items-center">
            <span class="product-price"><?= $item['price'] ?></span>
            <button class="btn-add-cart" onclick="addToCartAnim(this)"><i class="bi bi-cart-plus"></i></button>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</body>
</html>
