<?php require 'auth_check.php';
require 'db.php';
$uid = (int)$_SESSION['user_id'];

// Already a seller → redirect to seller dashboard
$row = db()->prepare('SELECT is_seller, seller_shop_name FROM users WHERE id=? LIMIT 1');
$row->execute([$uid]);
$u = $row->fetch();
if ($u['is_seller']) { header('Location: seller_dashboard.php'); exit; }

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop = trim($_POST['shop_name'] ?? '');
    $bio  = trim($_POST['bio']       ?? '');
    if (empty($shop)) { $error = 'Please enter a shop name.'; }
    elseif (strlen($shop) < 3) { $error = 'Shop name must be at least 3 characters.'; }
    else {
        // Check uniqueness
        $chk = db()->prepare('SELECT id FROM users WHERE seller_shop_name=? AND id!=? LIMIT 1');
        $chk->execute([$shop, $uid]);
        if ($chk->fetch()) { $error = 'That shop name is already taken.'; }
        else {
            db()->prepare('UPDATE users SET is_seller=1, seller_shop_name=?, seller_bio=?, updated_at=NOW() WHERE id=?')
               ->execute([$shop, $bio, $uid]);
            $_SESSION['is_seller']        = 1;
            $_SESSION['seller_shop_name'] = $shop;
            header('Location: seller_dashboard.php?welcome=1'); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Become a Seller – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container py-5" style="max-width:780px">
  <a href="homepage.php" class="back-btn mb-4 d-inline-flex align-items-center gap-2">
    <i class="bi bi-arrow-left"></i> Back
  </a>

  <!-- Hero -->
  <div class="seller-onboard-hero mb-5">
    <div class="seller-onboard-icon"><i class="bi bi-shop-window"></i></div>
    <h1 class="seller-onboard-title">Start Selling on VNTG HQ</h1>
    <p class="seller-onboard-sub">Turn your pre-loved items into cash. Set up your shop in under a minute.</p>
  </div>

  <!-- Benefits -->
  <div class="row g-3 mb-5">
    <?php $benefits = [
      ['bi-tag-fill','#0ea5e9','List Items Free','No listing fees. Post as many items as you want.'],
      ['bi-people-fill','#22c55e','Reach Buyers','Connect directly with thousands of shoppers.'],
      ['bi-cash-stack','#f59e0b','Get Paid','Receive payments securely through the platform.'],
      ['bi-shield-fill-check','#8b5cf6','Seller Protection','Dispute resolution and buyer-seller mediation.'],
    ]; foreach($benefits as $b): ?>
    <div class="col-6 col-md-3">
      <div class="seller-benefit-card">
        <i class="bi <?= $b[0] ?>" style="font-size:28px;color:<?= $b[1] ?>;margin-bottom:10px;display:block"></i>
        <p class="seller-benefit-title"><?= $b[2] ?></p>
        <p class="seller-benefit-sub"><?= $b[3] ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Form -->
  <div class="admin-card">
    <h4 class="profile-menu-heading mb-4"><i class="bi bi-pencil-square me-2"></i>Set Up Your Shop</h4>
    <?php if ($error): ?>
    <div class="alert alert-danger d-flex gap-2 mb-4"><i class="bi bi-exclamation-triangle-fill"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" novalidate>
      <div class="mb-4">
        <label class="pmodal-label">Shop Name <span class="text-danger">*</span></label>
        <div class="input-with-icon">
          <i class="bi bi-shop input-icon"></i>
          <input type="text" name="shop_name" class="form-control auth-input"
                 placeholder="e.g. Juan's Vintage Closet"
                 value="<?= htmlspecialchars($_POST['shop_name'] ?? '') ?>" required maxlength="120">
        </div>
        <small class="text-muted">This is your public shop name visible to buyers.</small>
      </div>
      <div class="mb-4">
        <label class="pmodal-label">Shop Bio / Description</label>
        <textarea name="bio" class="form-control pmodal-textarea" rows="3"
                  placeholder="Tell buyers about yourself and what you sell…" maxlength="500"><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
        <small class="text-muted">Optional — helps build trust with buyers.</small>
      </div>
      <div class="d-flex gap-2">
        <a href="homepage.php" class="btn pmodal-btn-cancel px-5">Cancel</a>
        <button type="submit" class="btn pmodal-btn-save px-5">
          <i class="bi bi-shop-window me-2"></i>Open My Shop
        </button>
      </div>
    </form>
  </div>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</body></html>
