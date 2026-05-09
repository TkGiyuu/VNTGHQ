<?php
require 'auth_admin.php';
require_once '../db.php';
$pdo = db();
$msg = '';

// Update order status
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['order_id'],$_POST['status'])) {
    $allowed = ['pending','processing','shipped','delivered','cancelled'];
    $newStatus = $_POST['status'];
    if (in_array($newStatus,$allowed)) {
        $pdo->prepare('UPDATE orders SET status=? WHERE id=?')
            ->execute([$newStatus,(int)$_POST['order_id']]);
        $msg = 'Order status updated.';
    }
}

$search  = trim($_GET['q']      ?? '');
$status  = trim($_GET['status'] ?? '');
$page    = max(1,(int)($_GET['page']??1));
$perPage = 12;
$offset  = ($page-1)*$perPage;

$where  = ['1=1'];
$params = [];
if ($search) {
    $where[]  = '(o.order_ref LIKE ? OR CONCAT(u.firstname," ",u.lastname) LIKE ? OR u.username LIKE ?)';
    $like     = '%'.$search.'%';
    $params   = array_merge($params,[$like,$like,$like]);
}
if ($status) { $where[]='o.status=?'; $params[]=$status; }
$whereSQL = implode(' AND ',$where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON u.id=o.user_id WHERE $whereSQL");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1,(int)ceil($total/$perPage));

$dataStmt = $pdo->prepare(
    "SELECT o.id, o.order_ref, o.total, o.status, o.payment_method, o.created_at,
            CONCAT(u.firstname,' ',u.lastname) AS customer, u.username,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id=o.id) AS item_count
     FROM orders o JOIN users u ON u.id=o.user_id
     WHERE $whereSQL ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset"
);
$dataStmt->execute($params);
$orders = $dataStmt->fetchAll();
$statusMap = ['pending'=>'transit','processing'=>'transit','shipped'=>'transit','delivered'=>'delivered','cancelled'=>'cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orders – VNTG HQ Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../style.css"><link rel="stylesheet" href="admin.css">
</head>
<body class="admin-body">
<?php include 'admin_navbar.php'; ?>
<div class="admin-layout">
  <?php include 'admin_sidebar.php'; ?>
  <main class="admin-main">
    <div class="admin-page-header mb-4">
      <div>
        <h1 class="admin-page-title">Orders</h1>
        <p class="admin-page-sub"><?= number_format($total) ?> total order<?= $total!==1?'s':'' ?></p>
      </div>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success d-flex gap-2 mb-4"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="admin-card mb-4">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-md-6">
          <label class="pmodal-label">Search</label>
          <div class="input-with-icon">
            <i class="bi bi-search input-icon"></i>
            <input type="text" name="q" class="form-control auth-input" placeholder="Order ref or customer name…" value="<?= htmlspecialchars($search) ?>">
          </div>
        </div>
        <div class="col-6 col-md-3">
          <label class="pmodal-label">Status</label>
          <select name="status" class="form-select pmodal-select">
            <option value="">All Statuses</option>
            <?php foreach(['pending','processing','shipped','delivered','cancelled'] as $st): ?>
            <option value="<?= $st ?>" <?= $status===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-3 d-flex gap-2">
          <button type="submit" class="btn admin-btn-primary flex-fill"><i class="bi bi-search me-1"></i>Search</button>
          <a href="orders.php" class="btn admin-btn-outline">Reset</a>
        </div>
      </form>
    </div>

    <div class="admin-card">
      <?php if (empty($orders)): ?>
      <div class="text-center py-5"><i class="bi bi-receipt" style="font-size:48px;color:var(--gray-200)"></i><p class="mt-3 text-muted">No orders found.</p></div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
          <thead>
            <tr><th>Order Ref</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Date</th><th>Status</th><th>Update</th></tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $o):
              $sc = $statusMap[$o['status']] ?? 'transit';
            ?>
            <tr>
              <td class="fw-bold small"><?= htmlspecialchars($o['order_ref']) ?></td>
              <td>
                <p class="mb-0 fw-semibold small"><?= htmlspecialchars($o['customer'] ?: $o['username']) ?></p>
                <p class="mb-0 text-muted" style="font-size:11px">@<?= htmlspecialchars($o['username']) ?></p>
              </td>
              <td class="text-center small"><?= $o['item_count'] ?> item<?= $o['item_count']!==1?'s':'' ?></td>
              <td class="fw-bold" style="color:var(--brand-blue)">₱<?= number_format($o['total'],0) ?></td>
              <td class="small"><?= ucfirst($o['payment_method']) ?></td>
              <td class="small text-muted"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
              <td><span class="order-badge order-<?= $sc ?>"><?= ucfirst($o['status']) ?></span></td>
              <td>
                <form method="POST" class="d-flex gap-1">
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                  <select name="status" class="form-select form-select-sm" style="width:130px;border-radius:8px;font-size:12px">
                    <?php foreach(['pending','processing','shipped','delivered','cancelled'] as $st): ?>
                    <option value="<?= $st ?>" <?= $o['status']===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-sm admin-btn-primary px-2"><i class="bi bi-check2"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($totalPages>1): ?>
      <div class="d-flex justify-content-between align-items-center mt-4 px-2">
        <p class="text-muted small mb-0">Showing <?= $offset+1 ?>–<?= min($offset+$perPage,$total) ?> of <?= $total ?></p>
        <nav><ul class="pagination pagination-sm mb-0">
          <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>">‹</a></li>
          <?php for($p=1;$p<=$totalPages;$p++): ?>
          <li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"><?= $p ?></a></li>
          <?php endfor; ?>
          <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>">›</a></li>
        </ul></nav>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
