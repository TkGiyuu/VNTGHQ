<?php require 'auth_check.php';
require 'db.php';
$uid = (int)$_SESSION['user_id'];

// Must be a seller
$u = db()->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
$u->execute([$uid]);
$user = $u->fetch();
if (!$user['is_seller']) { header('Location: become_seller.php'); exit; }

$shopName = $user['seller_shop_name'] ?? 'My Shop';
$welcome  = isset($_GET['welcome']);

// Stats
$pdo = db();
$totalListings  = (int)$pdo->prepare('SELECT COUNT(*) FROM listings WHERE seller_id=?')->execute([$uid]) ?
                  $pdo->prepare('SELECT COUNT(*) FROM listings WHERE seller_id=?')->execute([$uid]) : 0;
$stm = $pdo->prepare('SELECT COUNT(*) FROM listings WHERE seller_id=?'); $stm->execute([$uid]); $totalListings = (int)$stm->fetchColumn();
$stm = $pdo->prepare('SELECT COUNT(*) FROM listings WHERE seller_id=? AND status="active"'); $stm->execute([$uid]); $activeListings = (int)$stm->fetchColumn();
$stm = $pdo->prepare('SELECT COUNT(*) FROM listings WHERE seller_id=? AND status="sold"'); $stm->execute([$uid]); $soldListings = (int)$stm->fetchColumn();
$stm = $pdo->prepare('SELECT COALESCE(SUM(views),0) FROM listings WHERE seller_id=?'); $stm->execute([$uid]); $totalViews = (int)$stm->fetchColumn();
$stm = $pdo->prepare('SELECT COALESCE(AVG(rating),0) FROM seller_reviews WHERE seller_id=?'); $stm->execute([$uid]); $avgRating = round((float)$stm->fetchColumn(),1);
$stm = $pdo->prepare('SELECT COUNT(*) FROM seller_reviews WHERE seller_id=?'); $stm->execute([$uid]); $reviewCount = (int)$stm->fetchColumn();

// Recent listings
$listStm = $pdo->prepare('SELECT * FROM listings WHERE seller_id=? ORDER BY created_at DESC LIMIT 5');
$listStm->execute([$uid]);
$recentListings = $listStm->fetchAll();

// Recent reviews
$revStm = $pdo->prepare(
    'SELECT sr.*, CONCAT(u.firstname," ",u.lastname) AS buyer_name, u.username AS buyer_username
     FROM seller_reviews sr JOIN users u ON u.id=sr.buyer_id
     WHERE sr.seller_id=? ORDER BY sr.created_at DESC LIMIT 5'
);
$revStm->execute([$uid]);
$recentReviews = $revStm->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seller Dashboard – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<?php if ($welcome): ?>
<div class="alert alert-success d-flex align-items-center gap-2 mb-0 px-4 py-3" style="border-radius:0;border:none">
  <i class="bi bi-party-popper-fill fs-5"></i>
  <strong>Welcome to your shop, <?= htmlspecialchars($shopName) ?>!</strong>&nbsp; You're now a verified VNTG HQ seller. Start listing your items below.
</div>
<?php endif; ?>

<div class="container-fluid px-4 py-4">

  <!-- Page Header -->
  <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
    <div>
      <h1 class="admin-page-title"><i class="bi bi-shop-window me-2"></i><?= htmlspecialchars($shopName) ?></h1>
      <p class="admin-page-sub">Seller Dashboard · <?= $activeListings ?> active listing<?= $activeListings!==1?'s':'' ?></p>
    </div>
    <div class="d-flex gap-2">
      <a href="my_listings.php" class="btn dash-btn-outline"><i class="bi bi-list-ul me-2"></i>My Listings</a>
      <a href="create_listing.php" class="btn dash-btn-primary"><i class="bi bi-plus-lg me-2"></i>New Listing</a>
    </div>
  </div>

  <!-- Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:rgba(14,165,233,0.12);color:#0ea5e9"><i class="bi bi-tags-fill"></i></div>
        <div><span class="dash-stat-num"><?= $totalListings ?></span><span class="dash-stat-label d-block">Total Listings</span></div>
        <span class="dash-stat-badge neutral"><i class="bi bi-dash"></i><?= $activeListings ?> active</span>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:rgba(34,197,94,0.12);color:#22c55e"><i class="bi bi-bag-check-fill"></i></div>
        <div><span class="dash-stat-num"><?= $soldListings ?></span><span class="dash-stat-label d-block">Items Sold</span></div>
        <span class="dash-stat-badge up"><i class="bi bi-check"></i>Sold</span>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b"><i class="bi bi-eye-fill"></i></div>
        <div><span class="dash-stat-num"><?= number_format($totalViews) ?></span><span class="dash-stat-label d-block">Total Views</span></div>
        <span class="dash-stat-badge neutral"><i class="bi bi-graph-up"></i>All time</span>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6"><i class="bi bi-star-fill"></i></div>
        <div><span class="dash-stat-num"><?= $avgRating > 0 ? $avgRating : '—' ?></span><span class="dash-stat-label d-block">Seller Rating</span></div>
        <span class="dash-stat-badge neutral"><?= $reviewCount ?> review<?= $reviewCount!==1?'s':'' ?></span>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Recent Listings -->
    <div class="col-lg-7">
      <div class="dash-card h-100">
        <div class="dash-card-header d-flex justify-content-between mb-3">
          <h5 class="dash-card-title mb-0"><i class="bi bi-tags me-2"></i>Recent Listings</h5>
          <a href="my_listings.php" class="see-more">View all →</a>
        </div>
        <?php if (empty($recentListings)): ?>
        <div class="text-center py-5">
          <i class="bi bi-tags" style="font-size:48px;color:var(--gray-200)"></i>
          <p class="mt-3 text-muted">No listings yet.</p>
          <a href="create_listing.php" class="btn dash-btn-primary mt-2">Create Your First Listing</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table dash-table align-middle mb-0">
            <thead><tr><th>Item</th><th>Price</th><th>Stock</th><th>Views</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recentListings as $l):
              $sc = ['active'=>'delivered','sold'=>'transit','inactive'=>'cancelled'][$l['status']] ?? 'transit';
            ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <img src="<?= htmlspecialchars($l['img1'] ?: 'https://via.placeholder.com/44') ?>"
                       style="width:44px;height:44px;border-radius:8px;object-fit:cover"
                       onerror="this.src='https://via.placeholder.com/44'" alt="">
                  <span class="fw-semibold small"><?= htmlspecialchars($l['title']) ?></span>
                </div>
              </td>
              <td class="fw-bold small" style="color:var(--brand-blue)">₱<?= number_format($l['price'],0) ?></td>
              <td class="small"><?= $l['stock'] ?></td>
              <td class="small text-muted"><?= number_format($l['views']) ?></td>
              <td><span class="order-badge order-<?= $sc ?>"><?= ucfirst($l['status']) ?></span></td>
              <td>
                <div class="d-flex gap-1">
                  <a href="edit_listing.php?id=<?= $l['id'] ?>" class="btn admin-action-btn admin-action-view" title="Edit"><i class="bi bi-pencil"></i></a>
                  <a href="listing.php?id=<?= $l['id'] ?>" class="btn admin-action-btn admin-action-success" title="View" target="_blank"><i class="bi bi-eye"></i></a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Reviews + Shop Info -->
    <div class="col-lg-5 d-flex flex-column gap-4">
      <!-- Shop Info -->
      <div class="dash-card">
        <h5 class="dash-card-title mb-3"><i class="bi bi-shop me-2"></i>Shop Info</h5>
        <div class="dash-info-row"><span class="dash-info-label">Shop Name</span><span class="dash-info-val"><?= htmlspecialchars($shopName) ?></span></div>
        <div class="dash-info-row"><span class="dash-info-label">Bio</span><span class="dash-info-val" style="font-size:12px;color:var(--gray-400)"><?= htmlspecialchars($user['seller_bio'] ?: 'Not set') ?></span></div>
        <div class="dash-info-row border-0"><span class="dash-info-label">Seller Since</span><span class="dash-info-val"><?= date('M Y', strtotime($user['created_at'])) ?></span></div>
        <a href="seller_settings.php" class="btn pmodal-btn-save w-100 mt-3">
          <i class="bi bi-gear me-2"></i>Edit Shop Settings
        </a>
      </div>

      <!-- Recent Reviews -->
      <div class="dash-card">
        <div class="d-flex justify-content-between mb-3">
          <h5 class="dash-card-title mb-0"><i class="bi bi-star me-2"></i>Recent Reviews</h5>
          <a href="seller_reviews.php" class="see-more">View all →</a>
        </div>
        <?php if (empty($recentReviews)): ?>
        <p class="text-muted text-center py-3 small">No reviews yet.</p>
        <?php else: foreach ($recentReviews as $r): ?>
        <div class="seller-review-item mb-3">
          <div class="d-flex justify-content-between mb-1">
            <span class="fw-semibold small">@<?= htmlspecialchars($r['buyer_username']) ?></span>
            <span class="small">
              <?php for($s=1;$s<=5;$s++) echo '<i class="bi bi-star'.($s<=$r['rating']?'-fill text-warning':' text-muted').'"></i>'; ?>
            </span>
          </div>
          <p class="text-muted small mb-0"><?= htmlspecialchars($r['comment'] ?: 'No comment.') ?></p>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</body></html>
