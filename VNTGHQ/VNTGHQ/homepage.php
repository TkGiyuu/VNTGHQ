<?php require 'auth_check.php';
$searchQuery = trim($_GET['search'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VNTG HQ – Home of Pre-loved Items</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- HERO SECTION -->
<div class="container-fluid px-4 pt-4">

<?php if ($searchQuery): ?>
<!-- ── SEARCH RESULTS ── -->
<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="section-title"><i class="bi bi-search me-2"></i>Results for "<?= htmlspecialchars($searchQuery) ?>"</h2>
    <a href="homepage.php" class="back-btn d-inline-flex align-items-center gap-1"><i class="bi bi-arrow-left"></i> Back to Home</a>
  </div>
  <div id="searchResultsGrid" class="row g-3 mb-4"></div>
  <div id="searchNoResults" class="text-center py-5" style="display:none">
    <i class="bi bi-search" style="font-size:52px;color:var(--gray-200)"></i>
    <h5 class="mt-3" style="color:var(--gray-400)">No results found for "<?= htmlspecialchars($searchQuery) ?>"</h5>
    <a href="homepage.php" class="btn dash-btn-primary mt-3 px-4">Browse All Items</a>
  </div>
</div>
<script>
// Pre-fill search bar with query
document.addEventListener('DOMContentLoaded', () => {
  const inp = document.getElementById('navSearchInput');
  if (inp) inp.value = <?= json_encode($searchQuery) ?>;
  renderSearchResults(<?= json_encode($searchQuery) ?>);
});
function renderSearchResults(q) {
  const grid = document.getElementById('searchResultsGrid');
  const none = document.getElementById('searchNoResults');
  const ql = q.toLowerCase();
  const results = ALL_PRODUCTS.filter(p =>
    p.name.toLowerCase().includes(ql) || p.tag.toLowerCase().includes(ql)
  );
  if (!results.length) { none.style.display='block'; grid.innerHTML=''; return; }
  none.style.display='none';
  grid.innerHTML = results.map(p => {
    const id = p.name.replace(/\s+/g,'_').toLowerCase();
    return `<div class="col-6 col-md-4 col-lg-2half">
      <div class="product-card h-100 card-visible" data-pid="${id}">
        <div class="product-img-wrap">
          <img src="${p.img}" alt="${p.name}" class="product-img" onerror="this.src='https://via.placeholder.com/300'">
          <span class="product-tag">${p.tag}</span>
          <button class="btn-wishlist" onclick="toggleFavourite(this)"><i class="bi bi-heart"></i></button>
        </div>
        <div class="product-info">
          <p class="product-name">${p.name}</p>
          <div class="d-flex justify-content-between align-items-center">
            <span class="product-price">${p.price}</span>
            <button class="btn-add-cart" onclick="addToCartAnim(this)"><i class="bi bi-cart-plus"></i></button>
          </div>
        </div>
      </div>
    </div>`;
  }).join('');
  syncWishlistButtons();
}
</script>
<?php else: ?>
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="hero-banner d-flex align-items-center justify-content-between px-5">
        <div>
          <p class="hero-eyebrow mb-1">New Arrivals This Week</p>
          <h1 class="hero-title mb-2">Pre-loved<br>Finds.</h1>
          <p class="hero-sub mb-3">Unique vintage pieces you won't find anywhere else.</p>
          <a href="#top-picks" class="btn btn-light btn-lg fw-bold px-4">Shop Now <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="hero-badge">
          <span>VNTG<br>HQ</span>
        </div>
      </div>
    </div>
  </div>

  <!-- TOP PICKS -->
  <div class="section-header d-flex justify-content-between align-items-center mb-3" id="top-picks">
    <h2 class="section-title"><i class="bi bi-star-fill text-warning me-2"></i>Top Picks</h2>
    <a href="#" class="see-more">See more <i class="bi bi-chevron-double-right"></i></a>
  </div>
  <div class="row g-3 mb-4">
    <?php
    $topPicks = [
      ['name'=>'Black Puma Hoodie','price'=>'PHP 1,200','img'=>'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=300&q=80','tag'=>'Hoodie'],
      ['name'=>'Vintage Orange Hoodie','price'=>'PHP 950','img'=>'https://images.unsplash.com/photo-1556821840-3a63f15732ce?w=300&q=80','tag'=>'Hoodie'],
      ['name'=>'Classic White Sneakers','price'=>'PHP 2,100','img'=>'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=300&q=80','tag'=>'Shoes'],
      ['name'=>'Men\'s Black Watch','price'=>'PHP 1,800','img'=>'https://images.unsplash.com/photo-1523170335258-f5ed11844a49?w=300&q=80','tag'=>'Accessories'],
      ['name'=>'Streetwear Jacket','price'=>'PHP 1,450','img'=>'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=300&q=80','tag'=>'Jacket'],
    ];
    foreach($topPicks as $item): ?>
    <div class="col-6 col-md-4 col-lg-2half">
      <div class="product-card h-100" data-pid="<?= strtolower(str_replace(' ','_',$item['name'])) ?>">
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

  <!-- NIKE COLLECTION HIGHLIGHT -->
  <div class="collection-banner mb-4 d-flex align-items-center justify-content-between px-5">
    <div>
      <p class="coll-eyebrow">Featured Collection</p>
      <h2 class="coll-title">Pre-loved Nike<br>Collection</h2>
      <a href="#" class="btn btn-dark mt-2 px-4 fw-bold">View All <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="d-flex gap-3">
      <div class="coll-product-card">
        <img src="https://images.unsplash.com/photo-1556048219-bb6978360b84?w=200&q=80" alt="Air Jordan 1 Low">
        <p class="coll-pname mt-2">Air Jordan 1 Low</p>
        <span class="coll-pprice">PHP 3,800</span>
      </div>
      <div class="coll-product-card">
        <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=200&q=80" alt="Nike Windrunner">
        <p class="coll-pname mt-2">Nike Windrunner</p>
        <span class="coll-pprice">PHP 2,500</span>
      </div>
    </div>
  </div>

  <!-- MORE COLLECTIONS -->
  <div class="section-header d-flex justify-content-between align-items-center mb-3">
    <h2 class="section-title">More Collections</h2>
    <a href="#" class="see-more">See more <i class="bi bi-chevron-double-right"></i></a>
  </div>
  <div class="row g-3 mb-5">
    <?php
    $more = [
      ['name'=>'Oversized Tee','price'=>'PHP 650','img'=>'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=300&q=80','tag'=>'T-Shirt'],
      ['name'=>'Vintage Hoodie','price'=>'PHP 880','img'=>'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=300&q=80','tag'=>'Hoodie'],
      ['name'=>'Washed Denim Jacket','price'=>'PHP 1,700','img'=>'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=300&q=80','tag'=>'Jacket'],
      ['name'=>'Painted Sneakers','price'=>'PHP 2,200','img'=>'https://images.unsplash.com/photo-1465453869711-7e174808ace9?w=300&q=80','tag'=>'Shoes'],
      ['name'=>'Leather Crossbody','price'=>'PHP 1,100','img'=>'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=300&q=80','tag'=>'Bag'],
    ];
    foreach($more as $item): ?>
    <div class="col-6 col-md-4 col-lg-2half">
      <div class="product-card h-100" data-pid="<?= strtolower(str_replace(' ','_',$item['name'])) ?>">
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
<?php endif; // end search conditional ?>

<?php include 'footer.php'; ?>
<!-- Firebase + main MUST come after DOM so ALL_PRODUCTS is defined when search renders -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</body>
</html>
