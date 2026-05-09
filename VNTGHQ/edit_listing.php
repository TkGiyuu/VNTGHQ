<?php require 'auth_check.php';
require 'db.php';
$uid = (int)$_SESSION['user_id'];
$lid = (int)($_GET['id'] ?? 0);
if (!$lid) { header('Location: my_listings.php'); exit; }

// Fetch listing — must belong to this seller
$stm = db()->prepare('SELECT * FROM listings WHERE id=? AND seller_id=? LIMIT 1');
$stm->execute([$lid, $uid]);
$l = $stm->fetch();
if (!$l) { header('Location: my_listings.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title  = trim($_POST['title']       ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $price  = (float)str_replace(',', '', $_POST['price'] ?? 0);
    $cat    = trim($_POST['category']    ?? 'Other');
    $cond   = trim($_POST['condition']   ?? 'Good');
    $stock  = max(0, (int)($_POST['stock'] ?? 0));
    $status = trim($_POST['status']      ?? 'active');
    $img1   = trim($_POST['img1']        ?? $l['img1']);
    $img2   = trim($_POST['img2']        ?? $l['img2']);
    $img3   = trim($_POST['img3']        ?? $l['img3']);

    if (empty($title))   { $error = 'Title is required.'; }
    elseif ($price <= 0) { $error = 'Price must be greater than 0.'; }
    else {
        // Handle new file uploads
        foreach (['img1_file'=>'img1','img2_file'=>'img2','img3_file'=>'img3'] as $fk=>$vk) {
            if (isset($_FILES[$fk]) && $_FILES[$fk]['error']===UPLOAD_ERR_OK) {
                $mime = mime_content_type($_FILES[$fk]['tmp_name']);
                if (in_array($mime,['image/jpeg','image/png','image/webp','image/gif']) && $_FILES[$fk]['size']<3*1024*1024) {
                    $$vk = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($_FILES[$fk]['tmp_name']));
                }
            }
        }
        // Remove image buttons
        if (isset($_POST['remove_img1'])) $img1='';
        if (isset($_POST['remove_img2'])) $img2='';
        if (isset($_POST['remove_img3'])) $img3='';

        try {
            db()->prepare(
                'UPDATE listings SET title=?,description=?,price=?,category=?,condition_val=?,
                 stock=?,status=?,img1=?,img2=?,img3=?,updated_at=NOW() WHERE id=? AND seller_id=?'
            )->execute([$title,$desc,$price,$cat,$cond,$stock,$status,$img1,$img2,$img3,$lid,$uid]);
            header('Location: listing.php?id='.$lid); exit;
        } catch (PDOException $e) { $error = 'Could not save: '.$e->getMessage(); }
    }
}

$categories = ['Clothing','Shoes','Bags','Accessories','Electronics','Books','Collectibles','Furniture','Sports','Toys','Music','Art','Other'];
$conditions = ['Brand New','Like New','Good','Fair','For Parts'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Edit Listing – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container py-4" style="max-width:820px">
  <a href="my_listings.php" class="back-btn mb-4 d-inline-flex align-items-center gap-2">
    <i class="bi bi-arrow-left"></i> Back to My Listings
  </a>
  <h1 class="admin-page-title mb-1">Edit Listing</h1>
  <p class="admin-page-sub mb-4">Update the details for your item</p>

  <?php if ($error): ?>
  <div class="alert alert-danger d-flex gap-2 mb-4"><i class="bi bi-exclamation-triangle-fill"></i><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" novalidate>
    <div class="row g-4">
      <!-- Left -->
      <div class="col-lg-7">
        <div class="admin-card mb-4">
          <h5 class="dash-card-title mb-3"><i class="bi bi-card-text me-2"></i>Item Details</h5>
          <div class="mb-3">
            <label class="pmodal-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control auth-input ps-3"
                   placeholder="Item title" maxlength="200"
                   value="<?= htmlspecialchars($l['title']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="pmodal-label">Description</label>
            <textarea name="description" class="form-control pmodal-textarea" rows="4"
                      placeholder="Describe the item…"><?= htmlspecialchars($l['description']) ?></textarea>
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="pmodal-label">Category</label>
              <select name="category" class="form-select pmodal-select">
                <?php foreach($categories as $c): ?>
                <option value="<?= $c ?>" <?= $l['category']===$c?'selected':'' ?>><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="pmodal-label">Condition</label>
              <select name="condition" class="form-select pmodal-select">
                <?php foreach($conditions as $c): ?>
                <option value="<?= $c ?>" <?= $l['condition_val']===$c?'selected':'' ?>><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-4">
              <label class="pmodal-label">Price (₱) *</label>
              <div class="input-with-icon">
                <i class="bi bi-currency-exchange input-icon"></i>
                <input type="number" name="price" class="form-control auth-input" placeholder="0.00"
                       min="1" step="0.01" value="<?= $l['price'] ?>" required>
              </div>
            </div>
            <div class="col-4">
              <label class="pmodal-label">Stock</label>
              <div class="input-with-icon">
                <i class="bi bi-boxes input-icon"></i>
                <input type="number" name="stock" class="form-control auth-input"
                       min="0" max="999" value="<?= $l['stock'] ?>">
              </div>
            </div>
            <div class="col-4">
              <label class="pmodal-label">Status</label>
              <select name="status" class="form-select pmodal-select">
                <option value="active"   <?= $l['status']==='active'  ?'selected':'' ?>>Active</option>
                <option value="sold"     <?= $l['status']==='sold'    ?'selected':'' ?>>Sold</option>
                <option value="inactive" <?= $l['status']==='inactive'?'selected':'' ?>>Inactive</option>
              </select>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2">
          <a href="listing.php?id=<?= $lid ?>" class="btn pmodal-btn-cancel px-5">Cancel</a>
          <button type="submit" class="btn pmodal-btn-save px-5">
            <i class="bi bi-check-circle me-2"></i>Save Changes
          </button>
        </div>
      </div>

      <!-- Right: Photos -->
      <div class="col-lg-5">
        <div class="admin-card">
          <h5 class="dash-card-title mb-3"><i class="bi bi-images me-2"></i>Photos</h5>
          <?php foreach([1,2,3] as $n):
            $imgVal = $l['img'.$n] ?? '';
          ?>
          <div class="mb-3">
            <label class="pmodal-label">Photo <?= $n ?><?= $n===1?' (main)':'' ?></label>
            <div class="listing-photo-slot">
              <?php if ($imgVal): ?>
              <div class="mb-2 position-relative" style="width:80px">
                <img src="<?= htmlspecialchars($imgVal) ?>" id="preview<?= $n ?>"
                     style="width:80px;height:80px;border-radius:10px;object-fit:cover"
                     onerror="this.src='https://via.placeholder.com/80'" alt="">
                <button type="submit" name="remove_img<?= $n ?>" value="1"
                        class="btn" style="position:absolute;top:-6px;right:-6px;width:22px;height:22px;background:#ef4444;color:white;border:none;border-radius:50%;font-size:11px;padding:0;display:flex;align-items:center;justify-content:center"
                        title="Remove">
                  <i class="bi bi-x"></i>
                </button>
              </div>
              <?php else: ?>
              <div id="preview<?= $n ?>" style="width:80px;height:80px;border-radius:10px;background:var(--gray-200);display:flex;align-items:center;justify-content:center;margin-bottom:8px">
                <i class="bi bi-image" style="color:var(--gray-400);font-size:24px"></i>
              </div>
              <?php endif; ?>
              <label class="btn admin-btn-outline btn-sm" for="img<?= $n ?>_file" style="font-size:12px">
                <i class="bi bi-upload me-1"></i>Upload New
              </label>
              <input type="file" name="img<?= $n ?>_file" id="img<?= $n ?>_file"
                     accept="image/*" style="display:none"
                     onchange="previewListingPhoto(this,<?= $n ?>)">
              <input type="hidden" name="img<?= $n ?>" id="img<?= $n ?>_url" value="<?= htmlspecialchars($imgVal) ?>">
              <input type="url" class="form-control mt-1" style="font-size:12px;border-radius:8px"
                     placeholder="or paste image URL"
                     oninput="previewFromUrl(this.value,<?= $n ?>)">
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </form>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
<script>
function previewListingPhoto(input, n) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('img'+n+'_url').value = e.target.result;
    showEditPreview(n, e.target.result);
  };
  reader.readAsDataURL(input.files[0]);
}
function previewFromUrl(url, n) {
  if (!url) return;
  document.getElementById('img'+n+'_url').value = url;
  showEditPreview(n, url);
}
function showEditPreview(n, src) {
  const el = document.getElementById('preview'+n);
  if (el.tagName === 'IMG') { el.src = src; }
  else { el.innerHTML = `<img src="${src}" style="width:80px;height:80px;border-radius:10px;object-fit:cover">`; }
}
</script>
</body></html>
