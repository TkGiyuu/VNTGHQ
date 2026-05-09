<?php
require 'auth_admin.php';
require_once '../db.php';

$pdo = db();
$adminName = trim(($_SESSION['admin_firstname'] ?? '') . ' ' . ($_SESSION['admin_lastname'] ?? ''));
$loginTime = $_SESSION['admin_login_time'] ?? time();

// Summary stats
$totalUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_admin=0")->fetchColumn();
$totalOrders  = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status!='cancelled'")->fetchColumn();
$pendingOrders= (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$newToday     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE() AND is_admin=0")->fetchColumn();

// Recent registrations (last 5)
$recentUsers = $pdo->query(
    "SELECT id,username,firstname,lastname,email,provider,status,created_at
     FROM users WHERE is_admin=0 ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

// Recent orders (last 5)
$recentOrders = $pdo->query(
    "SELECT o.order_ref, o.total, o.status, o.created_at,
            CONCAT(u.firstname,' ',u.lastname) AS customer
     FROM orders o JOIN users u ON u.id=o.user_id
     ORDER BY o.created_at DESC LIMIT 5"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../style.css">
  <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-body">
<?php include 'admin_navbar.php'; ?>

<div class="admin-layout">
  <?php include 'admin_sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-page-header mb-4">
      <div>
        <h1 class="admin-page-title">Dashboard</h1>
        <p class="admin-page-sub">
          Welcome back, <strong><?= htmlspecialchars($adminName) ?></strong> —
          <?= date('l, F j, Y', $loginTime) ?> at <?= date('h:i A', $loginTime) ?>
        </p>
      </div>
      <a href="users.php" class="btn admin-btn-primary">
        <i class="bi bi-people me-2"></i>Manage Users
      </a>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-xl-3">
        <div class="admin-stat-card">
          <div class="admin-stat-icon" style="background:rgba(14,165,233,0.12);color:#0EA5E9">
            <i class="bi bi-people-fill"></i>
          </div>
          <div>
            <span class="admin-stat-num"><?= number_format($totalUsers) ?></span>
            <span class="admin-stat-label d-block">Total Users</span>
          </div>
          <?php if ($newToday > 0): ?>
          <span class="admin-stat-badge up"><i class="bi bi-plus"></i><?= $newToday ?> today</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="admin-stat-card">
          <div class="admin-stat-icon" style="background:rgba(34,197,94,0.12);color:#22c55e">
            <i class="bi bi-bag-check-fill"></i>
          </div>
          <div>
            <span class="admin-stat-num"><?= number_format($totalOrders) ?></span>
            <span class="admin-stat-label d-block">Total Orders</span>
          </div>
          <?php if ($pendingOrders > 0): ?>
          <span class="admin-stat-badge warn"><i class="bi bi-clock"></i><?= $pendingOrders ?> pending</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="admin-stat-card">
          <div class="admin-stat-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div>
            <span class="admin-stat-num">₱<?= number_format($totalRevenue, 0) ?></span>
            <span class="admin-stat-label d-block">Total Revenue</span>
          </div>
        </div>
      </div>
      <div class="col-6 col-xl-3">
        <div class="admin-stat-card">
          <div class="admin-stat-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6">
            <i class="bi bi-clock-history"></i>
          </div>
          <div>
            <span class="admin-stat-num"><?= $pendingOrders ?></span>
            <span class="admin-stat-label d-block">Pending Orders</span>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <!-- Recent Users -->
      <div class="col-lg-6">
        <div class="admin-card h-100">
          <div class="admin-card-header">
            <h5 class="admin-card-title"><i class="bi bi-person-plus me-2"></i>Recent Registrations</h5>
            <a href="users.php" class="admin-see-all">View all →</a>
          </div>
          <?php if (empty($recentUsers)): ?>
          <p class="text-muted text-center py-4 small">No users yet.</p>
          <?php else: ?>
          <div class="admin-list">
            <?php foreach ($recentUsers as $u): ?>
            <div class="admin-list-item">
              <div class="admin-user-avatar">
                <?= strtoupper(substr($u['firstname'],0,1).substr($u['lastname'],0,1)) ?: strtoupper(substr($u['username'],0,1)) ?>
              </div>
              <div class="flex-grow-1 min-w-0">
                <p class="admin-list-name"><?= htmlspecialchars(trim($u['firstname'].' '.$u['lastname']) ?: $u['username']) ?></p>
                <p class="admin-list-sub">@<?= htmlspecialchars($u['username']) ?> · <?= htmlspecialchars($u['email']) ?></p>
              </div>
              <span class="admin-provider-badge admin-provider-<?= $u['provider'] ?>"><?= ucfirst($u['provider']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="col-lg-6">
        <div class="admin-card h-100">
          <div class="admin-card-header">
            <h5 class="admin-card-title"><i class="bi bi-receipt me-2"></i>Recent Orders</h5>
            <a href="orders.php" class="admin-see-all">View all →</a>
          </div>
          <?php if (empty($recentOrders)): ?>
          <p class="text-muted text-center py-4 small">No orders yet.</p>
          <?php else: ?>
          <div class="admin-list">
            <?php foreach ($recentOrders as $o):
              $sc = ['pending'=>'transit','processing'=>'transit','shipped'=>'transit','delivered'=>'delivered','cancelled'=>'cancelled'][$o['status']] ?? 'transit';
            ?>
            <div class="admin-list-item">
              <div class="admin-order-icon"><i class="bi bi-box-seam"></i></div>
              <div class="flex-grow-1 min-w-0">
                <p class="admin-list-name"><?= htmlspecialchars($o['order_ref']) ?></p>
                <p class="admin-list-sub"><?= htmlspecialchars($o['customer']) ?> · <?= date('M j, Y', strtotime($o['created_at'])) ?></p>
              </div>
              <div class="text-end">
                <p class="admin-order-amount">₱<?= number_format($o['total'],0) ?></p>
                <span class="order-badge order-<?= $sc ?>"><?= ucfirst($o['status']) ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
