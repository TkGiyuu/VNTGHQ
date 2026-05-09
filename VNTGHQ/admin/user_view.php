<?php
require 'auth_admin.php';
require_once '../db.php';

$pdo = db();
$userId = (int)($_GET['id'] ?? 0);
if (!$userId) { header('Location: users.php'); exit; }

$user = $pdo->prepare(
    "SELECT * FROM users WHERE id=? AND is_admin=0 LIMIT 1"
);
$user->execute([$userId]);
$u = $user->fetch();
if (!$u) { header('Location: users.php'); exit; }

$fullname = trim($u['firstname'].' '.$u['lastname']) ?: $u['username'];
$initials = strtoupper(substr($u['firstname'],0,1).substr($u['lastname'],0,1)) ?: strtoupper(substr($u['username'],0,1));

// Orders
$orders = $pdo->prepare(
    "SELECT o.*, GROUP_CONCAT(oi.name SEPARATOR ', ') AS item_names
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id=o.id
     WHERE o.user_id=?
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
$orders->execute([$userId]);
$userOrders = $orders->fetchAll();

// Stats
$stats = $pdo->prepare(
    "SELECT COUNT(*) as order_count,
            COALESCE(SUM(total),0) as total_spent,
            COALESCE(SUM(CASE WHEN status='delivered' THEN total ELSE 0 END),0) as delivered_val
     FROM orders WHERE user_id=?"
);
$stats->execute([$userId]);
$s = $stats->fetch();

$statusMap = ['pending'=>'transit','processing'=>'transit','shipped'=>'transit','delivered'=>'delivered','cancelled'=>'cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($fullname) ?> – VNTG HQ Admin</title>
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
        <a href="users.php" class="back-btn d-inline-flex align-items-center gap-1 mb-2">
          <i class="bi bi-arrow-left"></i> Back to Users
        </a>
        <h1 class="admin-page-title">User Profile</h1>
      </div>
    </div>

    <div class="row g-4">
      <!-- Profile Card -->
      <div class="col-lg-4">
        <div class="admin-card text-center p-4">
          <div class="admin-profile-avatar mx-auto mb-3"><?= $initials ?></div>
          <h4 class="fw-bold mb-0"><?= htmlspecialchars($fullname) ?></h4>
          <p class="text-muted small mb-1">@<?= htmlspecialchars($u['username']) ?></p>
          <span class="admin-provider-badge admin-provider-<?= $u['provider'] ?> mb-3 d-inline-block">
            <?= ucfirst($u['provider']) ?>
          </span>

          <div class="admin-status-badge admin-status-<?= $u['status'] ?> d-block mb-3">
            <?= ucfirst($u['status']) ?>
          </div>

          <div class="row g-2 text-center mb-4">
            <div class="col-4">
              <p class="fw-bold mb-0" style="color:var(--brand-blue);font-size:20px"><?= $s['order_count'] ?></p>
              <p class="text-muted mb-0" style="font-size:11px">Orders</p>
            </div>
            <div class="col-4">
              <p class="fw-bold mb-0" style="color:var(--green);font-size:20px">₱<?= number_format($s['total_spent'],0) ?></p>
              <p class="text-muted mb-0" style="font-size:11px">Spent</p>
            </div>
            <div class="col-4">
              <p class="fw-bold mb-0" style="color:var(--gray-600);font-size:20px"><?= date('Y',strtotime($u['created_at'])) ?></p>
              <p class="text-muted mb-0" style="font-size:11px">Joined</p>
            </div>
          </div>

          <!-- Quick actions -->
          <div class="d-flex flex-column gap-2">
            <?php if ($u['status']==='active'): ?>
            <form method="POST" action="users.php">
              <input type="hidden" name="action" value="suspend">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button type="submit" class="btn w-100" style="background:#fef3c7;color:#92400e;border:none;border-radius:8px;font-weight:600">
                <i class="bi bi-pause-circle me-2"></i>Suspend User
              </button>
            </form>
            <?php else: ?>
            <form method="POST" action="users.php">
              <input type="hidden" name="action" value="activate">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button type="submit" class="btn w-100" style="background:#dcfce7;color:#166534;border:none;border-radius:8px;font-weight:600">
                <i class="bi bi-play-circle me-2"></i>Activate User
              </button>
            </form>
            <?php endif; ?>
            <button class="btn w-100 logout-btn"
                    onclick="confirmDeleteUser(<?= $u['id'] ?>,'<?= htmlspecialchars(addslashes($fullname)) ?>')">
              <i class="bi bi-trash3 me-2"></i>Delete Account
            </button>
          </div>
        </div>
      </div>

      <!-- Details + Orders -->
      <div class="col-lg-8 d-flex flex-column gap-4">
        <!-- Account Info -->
        <div class="admin-card">
          <h5 class="admin-card-title mb-3"><i class="bi bi-person-vcard me-2"></i>Account Information</h5>
          <div class="row g-3">
            <div class="col-6">
              <label class="pmodal-label">First Name</label>
              <p class="mb-0 fw-semibold"><?= htmlspecialchars($u['firstname'] ?: '—') ?></p>
            </div>
            <div class="col-6">
              <label class="pmodal-label">Last Name</label>
              <p class="mb-0 fw-semibold"><?= htmlspecialchars($u['lastname'] ?: '—') ?></p>
            </div>
            <div class="col-6">
              <label class="pmodal-label">Email</label>
              <p class="mb-0 fw-semibold"><?= htmlspecialchars($u['email']) ?></p>
            </div>
            <div class="col-6">
              <label class="pmodal-label">Phone</label>
              <p class="mb-0 fw-semibold"><?= htmlspecialchars($u['phone'] ?: '—') ?></p>
            </div>
            <div class="col-6">
              <label class="pmodal-label">Provider</label>
              <p class="mb-0 fw-semibold"><?= ucfirst($u['provider']) ?></p>
            </div>
            <div class="col-6">
              <label class="pmodal-label">Registered</label>
              <p class="mb-0 fw-semibold"><?= date('M j, Y g:i A', strtotime($u['created_at'])) ?></p>
            </div>
          </div>
        </div>

        <!-- Orders Table -->
        <div class="admin-card">
          <h5 class="admin-card-title mb-3"><i class="bi bi-receipt me-2"></i>Order History (<?= count($userOrders) ?>)</h5>
          <?php if (empty($userOrders)): ?>
          <p class="text-muted text-center py-3 small">No orders yet.</p>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
              <thead>
                <tr><th>Order Ref</th><th>Items</th><th>Total</th><th>Method</th><th>Date</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($userOrders as $o):
                  $sc = $statusMap[$o['status']] ?? 'transit';
                ?>
                <tr>
                  <td class="fw-semibold small"><?= htmlspecialchars($o['order_ref']) ?></td>
                  <td class="small text-muted" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?= htmlspecialchars($o['item_names'] ?: '—') ?>
                  </td>
                  <td class="fw-bold" style="color:var(--brand-blue)">₱<?= number_format($o['total'],0) ?></td>
                  <td class="small"><?= ucfirst($o['payment_method']) ?></td>
                  <td class="small text-muted"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                  <td><span class="order-badge order-<?= $sc ?>"><?= ucfirst($o['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- Delete Modal -->
<div class="pmodal-overlay" id="deleteOverlay" onclick="cancelDelete()"></div>
<div class="pmodal" id="deleteModal" style="max-width:380px">
  <div class="pmodal-body text-center py-3">
    <div style="width:64px;height:64px;background:rgba(239,68,68,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#ef4444">
      <i class="bi bi-person-x-fill"></i>
    </div>
    <h5 class="fw-bold mb-1">Delete User?</h5>
    <p class="text-muted mb-3 small" id="deleteMsg">This will permanently delete this account and all related data.</p>
    <form method="POST" action="users.php">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="user_id" id="deleteId">
      <div class="d-flex gap-2">
        <button type="button" class="btn pmodal-btn-cancel flex-fill" onclick="cancelDelete()">Cancel</button>
        <button type="submit" class="btn logout-btn flex-fill">Yes, Delete</button>
      </div>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDeleteUser(id,name){
  document.getElementById('deleteId').value=id;
  document.getElementById('deleteMsg').textContent='Permanently delete "'+name+'" and all their data?';
  document.getElementById('deleteOverlay').classList.add('active');
  document.getElementById('deleteModal').classList.add('active');
}
function cancelDelete(){
  document.getElementById('deleteOverlay').classList.remove('active');
  document.getElementById('deleteModal').classList.remove('active');
}
document.addEventListener('keydown',e=>{if(e.key==='Escape')cancelDelete();});
</script>
</body>
</html>
