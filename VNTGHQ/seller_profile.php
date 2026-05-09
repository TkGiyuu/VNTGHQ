<?php require 'auth_check.php';
require 'db.php';
$uid      = (int)$_SESSION['user_id'];
$sellerId = (int)($_GET['id'] ?? 0);
if (!$sellerId) { header('Location: homepage.php'); exit; }

$pdo = db();
$seller = $pdo->prepare('SELECT * FROM users WHERE id=? AND is_seller=1 LIMIT 1');
$seller->execute([$sellerId]);
$s = $seller->fetch();
if (!$s) { header('Location: homepage.php'); exit; }

$sellerName = trim($s['firstname'].' '.$s['lastname']) ?: $s['username'];
$initials   = strtoupper(substr($s['firstname'],0,1).substr($s['lastname'],0,1)) ?: strtoupper(substr($s['username'],0,1));

// Stats
$stm = $pdo->prepare('SELECT COUNT(*) FROM listings WHERE seller_id=? AND status="active"');
$stm->execute([$sellerId]); $activeCount = (int)$stm->fetchColumn();
$stm = $pdo->prepare('SELECT COUNT(*) FROM listings WHERE seller_id=? AND status="sold"');
$stm->execute([$sellerId]); $soldCount = (int)$stm->fetchColumn();
$stm = $pdo->prepare('SELECT COUNT(*) FROM seller_reviews WHERE seller_id=?');
$stm->execute([$sellerId]); $reviewCount = (int)$stm->fetchColumn();

// Active listings
$filter   = $_GET['cat'] ?? '';
$whereSQL = 'seller_id=? AND status="active"'; $params = [$sellerId];
if ($filter) { $whereSQL .= ' AND category=?'; $params[] = $filter; }
$listStm = $pdo->prepare("SELECT * FROM listings WHERE $whereSQL ORDER BY created_at DESC");
$listStm->execute($params);
$listings = $listStm->fetchAll();

// Distinct categories this seller has
$catStm = $pdo->prepare('SELECT DISTINCT category FROM listings WHERE seller_id=? AND status="active" ORDER BY category');
$catStm->execute([$sellerId]);
$sellerCats = $catStm->fetchAll(PDO::FETCH_COLUMN);

// Reviews
$revStm = $pdo->prepare(
    'SELECT sr.*, u.username AS buyer_username FROM seller_reviews sr
     JOIN users u ON u.id=sr.buyer_id
     WHERE sr.seller_id=? ORDER BY sr.created_at DESC LIMIT 10'
);
$revStm->execute([$sellerId]);
$reviews = $revStm->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= htmlspecialchars($s['seller_shop_name'] ?: $sellerName) ?> – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container py-4">
  <a href="homepage.php" class="back-btn mb-4 d-inline-flex align-items-center gap-2">
    <i class="bi bi-arrow-left"></i> Back to Browse
  </a>

  <!-- Seller Header -->
  <div class="seller-profile-header mb-5">
    <div class="d-flex align-items-center gap-4 flex-wrap">
      <?php if ($s['photo_url']): ?>
      <img src="<?= htmlspecialchars($s['photo_url']) ?>"
           style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--brand-blue)" alt="">
      <?php else: ?>
      <div style="width:88px;height:88px;border-radius:50%;background:linear-gradient(135deg,var(--brand-blue),var(--brand-dark));color:white;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:800;flex-shrink:0">
        <?= $initials ?>
      </div>
      <?php endif; ?>
      <div>
        <h1 style="font-family:'Bebas Neue',cursive;font-size:36px;letter-spacing:1px;margin:0;color:var(--brand-dark)">
          <?= htmlspecialchars($s['seller_shop_name'] ?: $sellerName) ?>
        </h1>
        <p class="text-muted mb-2" style="font-size:14px">@<?= htmlspecialchars($s['username']) ?></p>
        <?php if ($s['seller_rating'] > 0): ?>
        <div class="d-flex align-items-center gap-1">
          <?php for($i=1;$i<=5;$i++) echo '<i class="bi bi-star'.($i<=$s['seller_rating']?'-fill text-warning':' text-muted').'"></i>'; ?>
          <span class="fw-bold ms-1"><?= number_format($s['seller_rating'],1) ?></span>
          <span class="text-muted small">(<?= $reviewCount ?> review<?= $reviewCount!==1?'s':'' ?>)</span>
        </div>
        <?php endif; ?>
      </div>
      <div class="ms-auto d-flex gap-3 text-center">
        <div><p class="fw-bold mb-0" style="font-size:22px;color:var(--brand-blue)"><?= $activeCount ?></p><p class="text-muted small mb-0">Active</p></div>
        <div><p class="fw-bold mb-0" style="font-size:22px;color:var(--green)"><?= $soldCount ?></p><p class="text-muted small mb-0">Sold</p></div>
        <div><p class="fw-bold mb-0" style="font-size:22px;color:var(--gray-600)"><?= date('Y',strtotime($s['created_at'])) ?></p><p class="text-muted small mb-0">Joined</p></div>
      </div>
    </div>
    <?php if ($s['seller_bio']): ?>
    <p class="mt-3 mb-0" style="color:var(--gray-600);max-width:600px"><?= htmlspecialchars($s['seller_bio']) ?></p>
    <?php endif; ?>
  </div>

  <!-- Category filter -->
  <?php if (!empty($sellerCats)): ?>
  <div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="seller_profile.php?id=<?= $sellerId ?>" class="btn btn-sm <?= !$filter?'dash-btn-primary':'admin-btn-outline' ?>">All</a>
    <?php foreach($sellerCats as $cat): ?>
    <a href="seller_profile.php?id=<?= $sellerId ?>&cat=<?= urlencode($cat) ?>"
       class="btn btn-sm <?= $filter===$cat?'dash-btn-primary':'admin-btn-outline' ?>">
      <?= htmlspecialchars($cat) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Listings grid -->
  <?php if (empty($listings)): ?>
  <div class="text-center py-5">
    <i class="bi bi-tags" style="font-size:52px;color:var(--gray-200)"></i>
    <p class="mt-3 text-muted">No active listings<?= $filter?' in this category':'' ?>.</p>
  </div>
  <?php else: ?>
  <div class="row g-3 mb-5">
    <?php foreach ($listings as $item): ?>
    <div class="col-6 col-md-4 col-lg-3">
      <a href="listing.php?id=<?= $item['id'] ?>" class="text-decoration-none">
        <div class="product-card h-100 card-visible">
          <div class="product-img-wrap">
            <img src="<?= htmlspecialchars($item['img1'] ?: 'https://via.placeholder.com/300') ?>"
                 alt="<?= htmlspecialchars($item['title']) ?>"
                 class="product-img" onerror="this.src='https://via.placeholder.com/300'">
            <span class="product-tag"><?= htmlspecialchars($item['category']) ?></span>
          </div>
          <div class="product-info">
            <p class="product-name"><?= htmlspecialchars($item['title']) ?></p>
            <div class="d-flex justify-content-between align-items-center">
              <span class="product-price">₱<?= number_format($item['price'],0) ?></span>
              <span class="text-muted" style="font-size:11px"><i class="bi bi-eye me-1"></i><?= number_format($item['views']) ?></span>
            </div>
          </div>
        </div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Reviews -->
  <?php if (!empty($reviews)): ?>
  <h4 class="section-title mb-4"><i class="bi bi-star me-2"></i>Reviews</h4>
  <div class="row g-3">
    <?php foreach ($reviews as $r): ?>
    <div class="col-md-6">
      <div style="background:white;border-radius:var(--radius);padding:16px;box-shadow:var(--shadow-card)">
        <div class="d-flex justify-content-between mb-1">
          <span class="fw-semibold small">@<?= htmlspecialchars($r['buyer_username']) ?></span>
          <div>
            <?php for($i=1;$i<=5;$i++) echo '<i class="bi bi-star'.($i<=$r['rating']?'-fill text-warning':' text-muted').'" style="font-size:12px"></i>'; ?>
          </div>
        </div>
        <?php if ($r['comment']): ?>
        <p class="text-muted small mb-1"><?= htmlspecialchars($r['comment']) ?></p>
        <?php endif; ?>
        <small class="text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></small>
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
