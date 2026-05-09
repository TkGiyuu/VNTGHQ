<?php require 'auth_check.php';
require 'db.php';
$uid = (int)$_SESSION['user_id'];
$lid = (int)($_GET['id'] ?? 0);
if (!$lid) { header('Location: homepage.php'); exit; }

$pdo = db();
$stm = $pdo->prepare(
    'SELECT l.*, u.username AS seller_username, u.firstname, u.lastname,
            u.seller_shop_name, u.seller_bio, u.seller_rating, u.photo_url AS seller_photo
     FROM listings l JOIN users u ON u.id=l.seller_id
     WHERE l.id=? AND l.status != "inactive" LIMIT 1'
);
$stm->execute([$lid]);
$l = $stm->fetch();
if (!$l) { header('Location: homepage.php'); exit; }

// Increment view count (only once per session)
$viewKey = 'viewed_listing_'.$lid;
if (empty($_SESSION[$viewKey])) {
    $pdo->prepare('UPDATE listings SET views=views+1 WHERE id=?')->execute([$lid]);
    $_SESSION[$viewKey] = true;
}

// Handle add to cart / review submission
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'review') {
        $rating  = max(1, min(5, (int)($_POST['rating']  ?? 5)));
        $comment = trim($_POST['comment'] ?? '');
        // Can't review own listing
        if ($l['seller_id'] == $uid) {
            $msg = 'error:You cannot review your own listing.';
        } else {
            try {
                $pdo->prepare(
                    'INSERT INTO seller_reviews (seller_id,buyer_id,listing_id,rating,comment)
                     VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment)'
                )->execute([$l['seller_id'], $uid, $lid, $rating, $comment]);
                // Update seller's avg rating
                $avgStm = $pdo->prepare('SELECT AVG(rating) FROM seller_reviews WHERE seller_id=?');
                $avgStm->execute([$l['seller_id']]);
                $newAvg = round((float)$avgStm->fetchColumn(), 2);
                $pdo->prepare('UPDATE users SET seller_rating=? WHERE id=?')->execute([$newAvg, $l['seller_id']]);
                $msg = 'success:Review submitted!';
            } catch (PDOException $e) {
                $msg = 'error:Could not submit review.';
            }
        }
    }
}

// Load reviews
$reviews = $pdo->prepare(
    'SELECT sr.*, CONCAT(u.firstname," ",u.lastname) AS buyer_name, u.username AS buyer_username, u.photo_url AS buyer_photo
     FROM seller_reviews sr JOIN users u ON u.id=sr.buyer_id
     WHERE sr.seller_id=? AND sr.listing_id=? ORDER BY sr.created_at DESC'
);
$reviews->execute([$l['seller_id'], $lid]);
$reviewList = $reviews->fetchAll();

// More from seller
$moreStm = $pdo->prepare(
    'SELECT id,title,price,img1,category FROM listings
     WHERE seller_id=? AND id!=? AND status="active" ORDER BY RAND() LIMIT 4'
);
$moreStm->execute([$l['seller_id'], $lid]);
$moreListing = $moreStm->fetchAll();

$isOwnListing = ($l['seller_id'] == $uid);
$sellerName   = trim($l['firstname'].' '.$l['lastname']) ?: $l['seller_username'];
$condColors   = ['Brand New'=>'#22c55e','Like New'=>'#0ea5e9','Good'=>'#f59e0b','Fair'=>'#f97316','For Parts'=>'#ef4444'];
$condColor    = $condColors[$l['condition_val']] ?? '#94a3b8';

if ($msg) {
    [$msgType, $msgText] = explode(':', $msg, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($l['title']) ?> – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .listing-img-main { width:100%;aspect-ratio:1;object-fit:cover;border-radius:16px;cursor:pointer;transition:transform .2s; }
    .listing-img-main:hover { transform:scale(1.02); }
    .listing-img-thumb { width:72px;height:72px;object-fit:cover;border-radius:10px;cursor:pointer;border:2.5px solid transparent;transition:all .2s; }
    .listing-img-thumb.active,.listing-img-thumb:hover { border-color:var(--brand-blue); }
    .listing-price { font-family:'Bebas Neue',cursive;font-size:42px;color:var(--brand-blue);letter-spacing:1px; }
    .listing-title { font-size:24px;font-weight:800;color:var(--gray-800);margin:0 0 8px; }
    .cond-badge { display:inline-block;padding:4px 14px;border-radius:50px;font-size:12px;font-weight:700;color:white; }
    .seller-card { background:white;border-radius:var(--radius);padding:20px;box-shadow:var(--shadow-card); }
    .seller-avatar-sm { width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--brand-blue),var(--brand-dark));color:white;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;flex-shrink:0; }
    .review-card { background:var(--gray-100);border-radius:var(--radius-sm);padding:16px;margin-bottom:10px; }
    .more-listing-card { background:white;border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow-card);transition:transform .2s; }
    .more-listing-card:hover { transform:translateY(-3px); }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<?php if (!empty($msgText)): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('<i class="bi bi-<?= $msgType==="success"?"check-circle":"exclamation-triangle" ?> me-2"></i><?= htmlspecialchars($msgText) ?>','<?= $msgType==="success"?"cart":"fav" ?>'));</script>
<?php endif; ?>

<div class="container py-4">
  <a href="homepage.php" class="back-btn mb-4 d-inline-flex align-items-center gap-2">
    <i class="bi bi-arrow-left"></i> Back to Browse
  </a>

  <?php if (isset($_GET['new'])): ?>
  <div class="alert alert-success d-flex gap-2 mb-4">
    <i class="bi bi-check-circle-fill"></i>
    <strong>Listing published!</strong> Buyers can now find your item.
    <a href="my_listings.php" class="ms-auto fw-bold text-success">Manage Listings →</a>
  </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Images -->
    <div class="col-lg-5">
      <?php $imgs = array_filter([$l['img1'],$l['img2'],$l['img3']]); if(empty($imgs)) $imgs=['https://via.placeholder.com/500']; $imgs=array_values($imgs); ?>
      <img src="<?= htmlspecialchars($imgs[0]) ?>" class="listing-img-main mb-3" id="mainImg" alt="<?= htmlspecialchars($l['title']) ?>"
           onerror="this.src='https://via.placeholder.com/500'">
      <?php if (count($imgs) > 1): ?>
      <div class="d-flex gap-2">
        <?php foreach($imgs as $i=>$img): ?>
        <img src="<?= htmlspecialchars($img) ?>" class="listing-img-thumb <?= $i===0?'active':'' ?>"
             onclick="switchImg(this,'<?= htmlspecialchars($img) ?>')"
             onerror="this.style.display='none'" alt="">
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Details -->
    <div class="col-lg-4">
      <span class="cond-badge mb-2" style="background:<?= $condColor ?>"><?= htmlspecialchars($l['condition_val']) ?></span>
      <span class="text-muted small ms-2"><?= htmlspecialchars($l['category']) ?></span>
      <h1 class="listing-title mt-2"><?= htmlspecialchars($l['title']) ?></h1>
      <p class="listing-price">₱<?= number_format($l['price'], 0) ?></p>

      <div class="d-flex gap-3 mb-3 text-muted small">
        <span><i class="bi bi-eye me-1"></i><?= number_format($l['views']) ?> views</span>
        <span><i class="bi bi-box me-1"></i><?= $l['stock'] ?> in stock</span>
        <span><i class="bi bi-clock me-1"></i><?= date('M j, Y', strtotime($l['created_at'])) ?></span>
      </div>

      <?php if ($l['description']): ?>
      <div class="mb-4">
        <p class="pmodal-label mb-1">Description</p>
        <p style="font-size:14px;color:var(--gray-600);line-height:1.7"><?= nl2br(htmlspecialchars($l['description'])) ?></p>
      </div>
      <?php endif; ?>

      <?php if ($isOwnListing): ?>
      <div class="d-flex gap-2">
        <a href="edit_listing.php?id=<?= $l['id'] ?>" class="btn pmodal-btn-save flex-fill"><i class="bi bi-pencil me-2"></i>Edit Listing</a>
        <a href="my_listings.php" class="btn pmodal-btn-cancel flex-fill"><i class="bi bi-list-ul me-2"></i>All Listings</a>
      </div>
      <?php elseif ($l['stock'] > 0 && $l['status']==='active'): ?>
      <button class="btn pmodal-btn-save w-100 mb-2"
              onclick='addListingToCart(<?= json_encode([
                "id"    => "listing_".$l['id'],
                "name"  => $l['title'],
                "price" => "PHP ".number_format($l['price'],0),
                "img"   => $l['img1'] ?: "https://via.placeholder.com/120",
                "tag"   => $l['category'],
                "qty"   => 1
              ]) ?>)'>
        <i class="bi bi-cart-plus me-2"></i>Add to Cart
      </button>
      <button class="btn admin-btn-outline w-100" onclick='toggleFavFromListing(<?= json_encode([
        "id"=>"listing_".$l['id'],"name"=>$l['title'],
        "price"=>"PHP ".number_format($l['price'],0),
        "img"=>$l['img1']?:"","tag"=>$l['category']]) ?>)'>
        <i class="bi bi-heart me-2"></i>Save to Favourites
      </button>
      <?php else: ?>
      <div class="alert alert-secondary">This item is <?= $l['status']==='sold'?'sold out':'currently unavailable' ?>.</div>
      <?php endif; ?>
    </div>

    <!-- Seller Info -->
    <div class="col-lg-3">
      <div class="seller-card mb-3">
        <div class="d-flex align-items-center gap-3 mb-3">
          <?php if ($l['seller_photo']): ?>
          <img src="<?= htmlspecialchars($l['seller_photo']) ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover" alt="">
          <?php else: ?>
          <div class="seller-avatar-sm"><?= strtoupper(substr($sellerName,0,1)) ?></div>
          <?php endif; ?>
          <div>
            <p class="fw-bold mb-0" style="font-size:15px"><?= htmlspecialchars($l['seller_shop_name'] ?: $sellerName) ?></p>
            <p class="text-muted mb-0" style="font-size:12px">@<?= htmlspecialchars($l['seller_username']) ?></p>
          </div>
        </div>
        <?php if ($l['seller_rating'] > 0): ?>
        <div class="d-flex align-items-center gap-1 mb-2">
          <?php for($s=1;$s<=5;$s++) echo '<i class="bi bi-star'.($s<=$l['seller_rating']?'-fill text-warning':' text-muted').'"></i>'; ?>
          <span class="small fw-bold ms-1"><?= number_format($l['seller_rating'],1) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($l['seller_bio']): ?>
        <p style="font-size:12px;color:var(--gray-400);margin:0"><?= htmlspecialchars($l['seller_bio']) ?></p>
        <?php endif; ?>
        <a href="seller_profile.php?id=<?= $l['seller_id'] ?>" class="btn admin-btn-outline w-100 mt-3" style="font-size:12px">
          <i class="bi bi-shop me-2"></i>View Shop
        </a>
      </div>

      <!-- Review Form -->
      <?php if (!$isOwnListing): ?>
      <div class="seller-card">
        <h6 class="fw-bold mb-3"><i class="bi bi-star me-2"></i>Leave a Review</h6>
        <form method="POST">
          <input type="hidden" name="action" value="review">
          <div class="star-rating mb-2" id="reviewStars">
            <?php for($s=1;$s<=5;$s++): ?>
            <button type="button" class="star-btn <?= $s<=5?'active':'' ?>" data-val="<?= $s ?>" onclick="setReviewRating(<?= $s ?>)">
              <i class="bi bi-star-fill"></i>
            </button>
            <?php endfor; ?>
          </div>
          <input type="hidden" name="rating" id="reviewRatingInput" value="5">
          <textarea name="comment" class="form-control pmodal-textarea mb-2" rows="2"
                    placeholder="Share your experience…" style="font-size:13px"></textarea>
          <button type="submit" class="btn pmodal-btn-save w-100" style="font-size:13px">
            <i class="bi bi-send me-1"></i>Submit
          </button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Reviews section -->
  <?php if (!empty($reviewList)): ?>
  <hr class="my-5">
  <h4 class="section-title mb-4"><i class="bi bi-chat-left-text me-2"></i>Reviews (<?= count($reviewList) ?>)</h4>
  <div class="row g-3">
    <?php foreach($reviewList as $r): ?>
    <div class="col-md-6">
      <div class="review-card">
        <div class="d-flex justify-content-between mb-1">
          <span class="fw-semibold small">@<?= htmlspecialchars($r['buyer_username']) ?></span>
          <div>
            <?php for($s=1;$s<=5;$s++) echo '<i class="bi bi-star'.($s<=$r['rating']?'-fill text-warning':' text-muted').'" style="font-size:12px"></i>'; ?>
          </div>
        </div>
        <?php if($r['comment']): ?><p class="text-muted small mb-1"><?= htmlspecialchars($r['comment']) ?></p><?php endif; ?>
        <small class="text-muted"><?= date('M j, Y', strtotime($r['created_at'])) ?></small>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- More from Seller -->
  <?php if (!empty($moreListing)): ?>
  <hr class="my-5">
  <h4 class="section-title mb-4"><i class="bi bi-grid me-2"></i>More from <?= htmlspecialchars($l['seller_shop_name'] ?: $sellerName) ?></h4>
  <div class="row g-3">
    <?php foreach($moreListing as $m): ?>
    <div class="col-6 col-md-3">
      <a href="listing.php?id=<?= $m['id'] ?>" class="text-decoration-none">
        <div class="more-listing-card">
          <img src="<?= htmlspecialchars($m['img1'] ?: 'https://via.placeholder.com/300') ?>"
               onerror="this.src='https://via.placeholder.com/300'"
               style="width:100%;aspect-ratio:1;object-fit:cover" alt="">
          <div style="padding:12px">
            <p class="fw-semibold small mb-1" style="color:var(--gray-800);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= htmlspecialchars($m['title']) ?>
            </p>
            <p class="fw-bold mb-0" style="color:var(--brand-blue)">₱<?= number_format($m['price'],0) ?></p>
          </div>
        </div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
<script>
function switchImg(thumb, src) {
  document.getElementById('mainImg').src = src;
  document.querySelectorAll('.listing-img-thumb').forEach(t => t.classList.remove('active'));
  thumb.classList.add('active');
}
function setReviewRating(val) {
  document.getElementById('reviewRatingInput').value = val;
  document.querySelectorAll('#reviewStars .star-btn').forEach(b => b.classList.toggle('active', parseInt(b.dataset.val) <= val));
}
function addListingToCart(product) {
  let cart = getCart();
  const ex = cart.find(i => i.id === product.id);
  if (ex) { ex.qty = (ex.qty||1)+1; } else { cart.push({...product, qty:1}); }
  saveCart(cart); updateCartBadge();
  showToast('<i class="bi bi-cart-check me-2"></i><strong>'+product.name+'</strong> added to cart!','cart');
}
function toggleFavFromListing(product) {
  let favs = getFavs();
  const idx = favs.findIndex(f => f.id === product.id);
  if (idx === -1) { favs.push(product); saveFavs(favs); showToast('<i class="bi bi-heart-fill me-2"></i>Saved to favourites!','fav'); }
  else { favs.splice(idx,1); saveFavs(favs); showToast('<i class="bi bi-heart me-2"></i>Removed from favourites.','info'); }
  updateFavBadge();
}
</script>
</body></html>
