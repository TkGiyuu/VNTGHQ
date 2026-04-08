<?php require 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="about-hero py-5">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="about-eyebrow">Our Story</span>
        <h1 class="about-title mt-2">Welcome to<br>VNTG HQ</h1>
        <div class="about-divider my-4"></div>
        <p class="about-body">At VNTG HQ, we believe that great style never goes out of fashion. We are a modern online marketplace dedicated to buying and selling high-quality preloved items — giving timeless pieces a second life while promoting sustainable shopping.</p>
        <p class="about-body mt-3">VNTG HQ was created for individuals who appreciate value, uniqueness, and conscious consumption. Whether you're looking for vintage gems, rare finds, or gently used essentials, our platform connects sellers and buyers in one trusted space.</p>
      </div>
      <div class="col-lg-6">
        <div class="about-feature-grid">
          <div class="about-feature-card">
            <i class="bi bi-bag-heart-fill about-feat-icon"></i>
            <h5>Curated Pre-loved Items</h5>
            <p>Browse a wide selection of curated preloved items from trusted sellers.</p>
          </div>
          <div class="about-feature-card">
            <i class="bi bi-gem about-feat-icon"></i>
            <h5>Unique Vintage Finds</h5>
            <p>Discover unique vintage pieces you won't find anywhere else.</p>
          </div>
          <div class="about-feature-card">
            <i class="bi bi-tags-fill about-feat-icon"></i>
            <h5>Sell Your Items Easily</h5>
            <p>List and sell your own gently used items in just a few clicks.</p>
          </div>
          <div class="about-feature-card">
            <i class="bi bi-shield-fill-check about-feat-icon"></i>
            <h5>Secure & Trusted</h5>
            <p>Shop safely through our secure and user-friendly platform.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- At VNTG HQ You Can -->
<div class="container py-5">
  <div class="about-cta-box p-5">
    <div class="row align-items-center">
      <div class="col-lg-8">
        <h2 class="about-cta-title">At VNTG HQ, you can:</h2>
        <ul class="about-cta-list mt-3">
          <li><i class="bi bi-check-circle-fill me-2"></i>Browse a wide selection of curated preloved items</li>
          <li><i class="bi bi-check-circle-fill me-2"></i>Discover unique vintage pieces you won't find anywhere else</li>
          <li><i class="bi bi-check-circle-fill me-2"></i>Sell your own gently used items easily</li>
          <li><i class="bi bi-check-circle-fill me-2"></i>Shop safely through a secure and user-friendly platform</li>
        </ul>
        <p class="about-cta-foot mt-3">From clothing and accessories to collectibles and lifestyle products, VNTG HQ is your headquarters for all things vintage and value.</p>
      </div>
      <div class="col-lg-4 text-center">
        <div class="about-cta-badge">
          <i class="bi bi-bag-heart-fill"></i>
          <span>VNTG HQ</span>
          <small>Home of Pre-loved Items</small>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
