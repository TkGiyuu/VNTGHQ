<?php
require 'auth_admin.php';
require_once '../db.php';

$pdo = db();
$msg = '';
$msgType = 'success';

// ── Actions ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    // Prevent admin from deleting themselves
    if ($userId === (int)$_SESSION['admin_id']) {
        $msg = 'You cannot modify your own admin account from here.';
        $msgType = 'danger';
    } elseif ($action === 'delete' && $userId > 0) {
        try {
            $pdo->prepare('DELETE FROM users WHERE id=? AND is_admin=0')->execute([$userId]);
            $msg = 'User deleted successfully.';
        } catch (PDOException $e) {
            $msg = 'Error deleting user: ' . $e->getMessage();
            $msgType = 'danger';
        }
    } elseif ($action === 'suspend' && $userId > 0) {
        $pdo->prepare("UPDATE users SET status='suspended' WHERE id=? AND is_admin=0")->execute([$userId]);
        $msg = 'User suspended.';
    } elseif ($action === 'activate' && $userId > 0) {
        $pdo->prepare("UPDATE users SET status='active' WHERE id=? AND is_admin=0")->execute([$userId]);
        $msg = 'User activated.';
    }
}

// ── Search + Filter ───────────────────────────────────────────────────────────
$search   = trim($_GET['q']        ?? '');
$provider = trim($_GET['provider'] ?? '');
$status   = trim($_GET['status']   ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;
$offset   = ($page - 1) * $perPage;

$where  = ['u.is_admin = 0'];
$params = [];
if ($search) {
    $where[]  = '(u.username LIKE ? OR u.firstname LIKE ? OR u.lastname LIKE ? OR u.email LIKE ?)';
    $like     = '%' . $search . '%';
    $params   = array_merge($params, [$like, $like, $like, $like]);
}
if ($provider) { $where[] = 'u.provider = ?'; $params[] = $provider; }
if ($status)   { $where[] = 'u.status = ?';   $params[] = $status;   }

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$total = (int)$pdo->prepare("SELECT COUNT(*) FROM users u $whereSQL")->execute($params) ?
         $pdo->prepare("SELECT COUNT(*) FROM users u $whereSQL")->execute($params) : 0;
// Correct count query
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$dataStmt = $pdo->prepare(
    "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.phone,
            u.provider, u.status, u.created_at,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) AS order_count,
            (SELECT COALESCE(SUM(o.total),0) FROM orders o WHERE o.user_id=u.id AND o.status!='cancelled') AS total_spent
     FROM users u $whereSQL
     ORDER BY u.created_at DESC
     LIMIT $perPage OFFSET $offset"
);
$dataStmt->execute($params);
$users = $dataStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users – VNTG HQ Admin</title>
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

    <!-- Page Header -->
    <div class="admin-page-header mb-4">
      <div>
        <h1 class="admin-page-title">Manage Users</h1>
        <p class="admin-page-sub"><?= number_format($total) ?> registered user<?= $total !== 1 ? 's' : '' ?></p>
      </div>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-<?= $msgType==='success' ? 'check-circle' : 'exclamation-triangle' ?>-fill"></i>
      <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <!-- Search + Filter Bar -->
    <div class="admin-card mb-4">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-md-5">
          <label class="pmodal-label">Search</label>
          <div class="input-with-icon">
            <i class="bi bi-search input-icon"></i>
            <input type="text" name="q" class="form-control auth-input"
                   placeholder="Name, username, or email…"
                   value="<?= htmlspecialchars($search) ?>">
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="pmodal-label">Provider</label>
          <select name="provider" class="form-select pmodal-select">
            <option value="">All</option>
            <option value="local"    <?= $provider==='local'    ?'selected':'' ?>>Local</option>
            <option value="google"   <?= $provider==='google'   ?'selected':'' ?>>Google</option>
            <option value="facebook" <?= $provider==='facebook' ?'selected':'' ?>>Facebook</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="pmodal-label">Status</label>
          <select name="status" class="form-select pmodal-select">
            <option value="">All</option>
            <option value="active"    <?= $status==='active'    ?'selected':'' ?>>Active</option>
            <option value="suspended" <?= $status==='suspended' ?'selected':'' ?>>Suspended</option>
          </select>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
          <button type="submit" class="btn admin-btn-primary flex-fill">
            <i class="bi bi-search me-1"></i>Search
          </button>
          <a href="users.php" class="btn admin-btn-outline">Reset</a>
        </div>
      </form>
    </div>

    <!-- Users Table -->
    <div class="admin-card">
      <?php if (empty($users)): ?>
      <div class="text-center py-5">
        <i class="bi bi-people" style="font-size:48px;color:var(--gray-200)"></i>
        <p class="mt-3 text-muted">No users found<?= $search ? ' for "'.htmlspecialchars($search).'"' : '' ?>.</p>
      </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table admin-table align-middle mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Email</th>
              <th>Provider</th>
              <th>Orders</th>
              <th>Spent</th>
              <th>Joined</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $i => $u):
              $fullname = trim($u['firstname'].' '.$u['lastname']) ?: $u['username'];
              $initials = strtoupper(substr($u['firstname'],0,1).substr($u['lastname'],0,1)) ?: strtoupper(substr($u['username'],0,1));
            ?>
            <tr>
              <td class="text-muted small"><?= $offset + $i + 1 ?></td>
              <td>
                <div class="d-flex align-items-center gap-3">
                  <div class="admin-user-avatar"><?= $initials ?></div>
                  <div>
                    <p class="admin-list-name mb-0"><?= htmlspecialchars($fullname) ?></p>
                    <p class="admin-list-sub mb-0">@<?= htmlspecialchars($u['username']) ?></p>
                  </div>
                </div>
              </td>
              <td class="small text-muted"><?= htmlspecialchars($u['email']) ?></td>
              <td><span class="admin-provider-badge admin-provider-<?= $u['provider'] ?>"><?= ucfirst($u['provider']) ?></span></td>
              <td class="text-center fw-semibold"><?= $u['order_count'] ?></td>
              <td class="fw-semibold" style="color:var(--brand-blue)">₱<?= number_format($u['total_spent'],0) ?></td>
              <td class="small text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
              <td>
                <span class="admin-status-badge admin-status-<?= $u['status'] ?>">
                  <?= ucfirst($u['status']) ?>
                </span>
              </td>
              <td class="text-end">
                <div class="d-flex gap-1 justify-content-end">
                  <!-- View -->
                  <a href="user_view.php?id=<?= $u['id'] ?>"
                     class="btn admin-action-btn admin-action-view"
                     title="View Profile">
                    <i class="bi bi-eye"></i>
                  </a>
                  <!-- Suspend / Activate -->
                  <?php if ($u['status'] === 'active'): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="suspend">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn admin-action-btn admin-action-warn" title="Suspend">
                      <i class="bi bi-pause-circle"></i>
                    </button>
                  </form>
                  <?php else: ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="activate">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn admin-action-btn admin-action-success" title="Activate">
                      <i class="bi bi-play-circle"></i>
                    </button>
                  </form>
                  <?php endif; ?>
                  <!-- Delete -->
                  <button type="button"
                          class="btn admin-action-btn admin-action-danger"
                          title="Delete"
                          onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($fullname)) ?>')">
                    <i class="bi bi-trash3"></i>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="d-flex justify-content-between align-items-center mt-4 px-2">
        <p class="text-muted small mb-0">
          Showing <?= $offset+1 ?>–<?= min($offset+$perPage,$total) ?> of <?= $total ?> users
        </p>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $page<=1?'disabled':'' ?>">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>">‹</a>
            </li>
            <?php for ($p=1;$p<=$totalPages;$p++): ?>
            <li class="page-item <?= $p===$page?'active':'' ?>">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>">›</a>
            </li>
          </ul>
        </nav>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>

  </main>
</div>

<!-- Delete Confirmation Modal -->
<div class="pmodal-overlay" id="deleteOverlay" onclick="cancelDelete()"></div>
<div class="pmodal" id="deleteModal" style="max-width:380px">
  <div class="pmodal-body text-center py-3">
    <div style="width:64px;height:64px;background:rgba(239,68,68,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;color:#ef4444">
      <i class="bi bi-person-x-fill"></i>
    </div>
    <h5 class="fw-bold mb-1">Delete User?</h5>
    <p class="text-muted mb-3 small" id="deleteModalMsg">This will permanently delete this user and all their data.</p>
    <form method="POST" id="deleteForm">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="user_id" id="deleteUserId">
      <div class="d-flex gap-2">
        <button type="button" class="btn pmodal-btn-cancel flex-fill" onclick="cancelDelete()">Cancel</button>
        <button type="submit" class="btn logout-btn flex-fill">Yes, Delete</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(id, name) {
  document.getElementById('deleteUserId').value = id;
  document.getElementById('deleteModalMsg').textContent =
    'This will permanently delete "' + name + '" and all their orders, cart items and data. This cannot be undone.';
  document.getElementById('deleteOverlay').classList.add('active');
  document.getElementById('deleteModal').classList.add('active');
}
function cancelDelete() {
  document.getElementById('deleteOverlay').classList.remove('active');
  document.getElementById('deleteModal').classList.remove('active');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') cancelDelete(); });
</script>
</body>
</html>
