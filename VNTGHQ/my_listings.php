<?php require 'auth_check.php';
require 'db.php';
$uid = (int)$_SESSION['user_id'];
$u = db()->prepare('SELECT is_seller FROM users WHERE id=? LIMIT 1');
$u->execute([$uid]); $u = $u->fetch();
if (!$u['is_seller']) { header('Location: become_seller.php'); exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $lid = (int)($_POST['listing_id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if ($act === 'delete' && $lid) {
        db()->prepare('DELETE FROM listings WHERE id=? AND seller_id=?')->execute([$lid,$uid]);
        $msg = 'Listing deleted.';
    } elseif (in_array($act,['active','sold','inactive']) && $lid) {
        db()->prepare('UPDATE listings SET status=? WHERE id=? AND seller_id=?')->execute([$act,$lid,$uid]);
        $msg = 'Status updated to '.ucfirst($act).'.';
    }
}

$filter = $_GET['status'] ?? '';
$where  = 'seller_id=?'; $params = [$uid];
if ($filter) { $where .= ' AND status=?'; $params[] = $filter; }
$stm = db()->prepare("SELECT * FROM listings WHERE $where ORDER BY created_at DESC");
$stm->execute($params);
$listings = $stm->fetchAll();
$statusMap = ['active'=>'delivered','sold'=>'transit','inactive'=>'cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Listings – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container-fluid px-4 py-4">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
      <a href="seller_dashboard.php" class="back-btn d-inline-flex align-items-center gap-1 mb-2"><i class="bi bi-arrow-left"></i> Dashboard</a>
      <h1 class="admin-page-title">My Listings</h1>
    </div>
    <a href="create_listing.php" class="btn dash-btn-primary"><i class="bi bi-plus-lg me-2"></i>New Listing</a>
  </div>

  <?php if ($msg): ?><div class="alert alert-success mb-4"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <!-- Filter tabs -->
  <div class="d-flex gap-2 mb-4 flex-wrap">
    <?php foreach ([''=> 'All', 'active'=>'Active', 'sold'=>'Sold', 'inactive'=>'Inactive'] as $val=>$label): ?>
    <a href="?status=<?= $val ?>" class="btn btn-sm <?= $filter===$val?'dash-btn-primary':'admin-btn-outline' ?>">
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($listings)): ?>
  <div class="admin-card text-center py-5">
    <i class="bi bi-tags" style="font-size:52px;color:var(--gray-200)"></i>
    <h5 class="mt-3 text-muted">No listings yet.</h5>
    <a href="create_listing.php" class="btn dash-btn-primary mt-3">Create Your First Listing</a>
  </div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($listings as $l):
      $sc = $statusMap[$l['status']] ?? 'transit';
    ?>
    <div class="col-12 col-md-6 col-xl-4">
      <div class="admin-card listing-manage-card">
        <div class="d-flex gap-3">
          <img src="<?= htmlspecialchars($l['img1'] ?: 'https://via.placeholder.com/80') ?>"
               onerror="this.src='https://via.placeholder.com/80'"
               style="width:80px;height:80px;border-radius:10px;object-fit:cover;flex-shrink:0" alt="">
          <div class="flex-grow-1 min-w-0">
            <h6 class="fw-bold mb-1" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= htmlspecialchars($l['title']) ?>
            </h6>
            <p class="mb-1" style="color:var(--brand-blue);font-weight:700">₱<?= number_format($l['price'],0) ?></p>
            <div class="d-flex gap-2 align-items-center flex-wrap">
              <span class="order-badge order-<?= $sc ?>"><?= ucfirst($l['status']) ?></span>
              <span class="text-muted small"><?= $l['stock'] ?> in stock</span>
              <span class="text-muted small"><i class="bi bi-eye me-1"></i><?= number_format($l['views']) ?></span>
            </div>
          </div>
        </div>
        <div class="d-flex gap-2 mt-3 pt-3" style="border-top:1px solid var(--gray-100)">
          <a href="listing.php?id=<?= $l['id'] ?>" class="btn btn-sm admin-btn-outline flex-fill" target="_blank">
            <i class="bi bi-eye me-1"></i>View
          </a>
          <a href="edit_listing.php?id=<?= $l['id'] ?>" class="btn btn-sm pmodal-btn-save flex-fill">
            <i class="bi bi-pencil me-1"></i>Edit
          </a>
          <div class="dropdown">
            <button class="btn btn-sm admin-btn-outline dropdown-toggle" data-bs-toggle="dropdown">
              <i class="bi bi-three-dots"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end vntg-dropdown">
              <?php foreach(['active'=>'Set Active','sold'=>'Mark as Sold','inactive'=>'Deactivate'] as $val=>$lbl): ?>
              <?php if ($val !== $l['status']): ?>
              <li>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                  <input type="hidden" name="action" value="<?= $val ?>">
                  <button type="submit" class="dropdown-item"><?= $lbl ?></button>
                </form>
              </li>
              <?php endif; endforeach; ?>
              <li><hr class="dropdown-divider my-1"></li>
              <li>
                <form method="POST" onsubmit="return confirm('Delete this listing?')">
                  <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                  <input type="hidden" name="action" value="delete">
                  <button type="submit" class="dropdown-item text-danger">Delete</button>
                </form>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</body></html>
