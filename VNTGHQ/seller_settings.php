<?php require 'auth_check.php';
require 'db.php';
$uid = (int)$_SESSION['user_id'];
$u = db()->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
$u->execute([$uid]); $u = $u->fetch();
if (!$u['is_seller']) { header('Location: become_seller.php'); exit; }

$msg = ''; $msgType = 'success';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $shop = trim($_POST['shop_name'] ?? '');
    $bio  = trim($_POST['bio']       ?? '');
    if (empty($shop)) { $msg='Shop name is required.'; $msgType='danger'; }
    else {
        $chk = db()->prepare('SELECT id FROM users WHERE seller_shop_name=? AND id!=? LIMIT 1');
        $chk->execute([$shop,$uid]);
        if ($chk->fetch()) { $msg='That shop name is already taken.'; $msgType='danger'; }
        else {
            db()->prepare('UPDATE users SET seller_shop_name=?,seller_bio=?,updated_at=NOW() WHERE id=?')
               ->execute([$shop,$bio,$uid]);
            $_SESSION['seller_shop_name']=$shop;
            $u['seller_shop_name']=$shop; $u['seller_bio']=$bio;
            $msg='Shop settings saved!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Shop Settings – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container py-4" style="max-width:620px">
  <a href="seller_dashboard.php" class="back-btn mb-4 d-inline-flex align-items-center gap-2">
    <i class="bi bi-arrow-left"></i> Back to Dashboard
  </a>
  <h1 class="admin-page-title mb-1">Shop Settings</h1>
  <p class="admin-page-sub mb-4">Manage how buyers see your shop</p>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?> d-flex gap-2 mb-4">
    <i class="bi bi-<?= $msgType==='success'?'check-circle':'exclamation-triangle' ?>-fill"></i>
    <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <div class="admin-card">
    <form method="POST" novalidate>
      <div class="mb-4">
        <label class="pmodal-label">Shop Name <span class="text-danger">*</span></label>
        <div class="input-with-icon">
          <i class="bi bi-shop input-icon"></i>
          <input type="text" name="shop_name" class="form-control auth-input"
                 placeholder="Your shop name" maxlength="120"
                 value="<?= htmlspecialchars($u['seller_shop_name']) ?>" required>
        </div>
      </div>
      <div class="mb-4">
        <label class="pmodal-label">Shop Bio</label>
        <textarea name="bio" class="form-control pmodal-textarea" rows="4"
                  placeholder="Tell buyers about your shop…" maxlength="500"><?= htmlspecialchars($u['seller_bio']) ?></textarea>
      </div>
      <div class="d-flex gap-2">
        <a href="seller_dashboard.php" class="btn pmodal-btn-cancel px-5">Cancel</a>
        <button type="submit" class="btn pmodal-btn-save px-5">
          <i class="bi bi-check-circle me-2"></i>Save Settings
        </button>
      </div>
    </form>
  </div>

  <!-- Danger Zone -->
  <div class="admin-card mt-4" style="border:2px solid rgba(239,68,68,0.2)">
    <h5 class="fw-bold mb-1" style="color:#dc2626"><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h5>
    <p class="text-muted small mb-3">Closing your shop will hide all listings. This can be undone by contacting support.</p>
    <button class="btn" style="background:rgba(239,68,68,0.1);color:#dc2626;border:none;border-radius:8px;font-weight:600;padding:9px 20px"
            onclick="if(confirm('Close your shop? All listings will be hidden.'))window.location='close_shop.php'">
      <i class="bi bi-shop me-2"></i>Close My Shop
    </button>
  </div>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</body></html>
