<?php require 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Favourites – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="cart-page-title"><i class="bi bi-heart-fill text-danger me-2"></i>My Favourites</h1>
    <button class="btn btn-outline-danger btn-sm" onclick="clearAllFavs()">
      <i class="bi bi-trash me-1"></i>Clear All
    </button>
  </div>

  <!-- Rendered by JS from localStorage -->
  <div id="favs-grid" class="row g-3"></div>

  <div id="favs-empty" class="text-center py-5" style="display:none">
    <i class="bi bi-heart" style="font-size:64px;color:var(--gray-200)"></i>
    <h4 class="mt-3" style="color:var(--gray-400)">No favourites yet</h4>
    <p style="color:var(--gray-400)">Browse items and tap the heart to save them here.</p>
    <a href="homepage.php" class="btn dash-btn-primary mt-2 px-4">Browse Items</a>
  </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  renderFavsPage();
});

function renderFavsPage() {
  const favs = getFavs();
  const grid  = document.getElementById('favs-grid');
  const empty = document.getElementById('favs-empty');

  if (favs.length === 0) {
    grid.innerHTML  = '';
    empty.style.display = 'block';
    return;
  }
  empty.style.display = 'none';

  grid.innerHTML = favs.map(item => `
    <div class="col-6 col-md-4 col-lg-2half">
      <div class="product-card h-100 card-visible" data-pid="${item.id}">
        <div class="product-img-wrap">
          <img src="${item.img}" alt="${item.name}" class="product-img">
          <button class="btn-wishlist fav-active" onclick="removeFavFromPage('${item.id}', this)">
            <i class="bi bi-heart-fill"></i>
          </button>
        </div>
        <div class="product-info">
          <p class="product-name">${item.name}</p>
          <div class="d-flex justify-content-between align-items-center">
            <span class="product-price">${item.price}</span>
            <button class="btn-add-cart" onclick="addToCartAnim(this)"><i class="bi bi-cart-plus"></i></button>
          </div>
        </div>
      </div>
    </div>
  `).join('');
}

function removeFavFromPage(id, btn) {
  let favs = getFavs().filter(f => f.id !== id);
  saveFavs(favs);
  updateFavBadge();
  renderFavsPage();
  showToast('<i class="bi bi-heart me-2"></i>Removed from favourites.', 'info');
}

function clearAllFavs() {
  if (!confirm('Remove all favourites?')) return;
  saveFavs([]);
  updateFavBadge();
  renderFavsPage();
}
</script>
</body>
</html>
