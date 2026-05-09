<?php require 'auth_check.php';
require 'db.php';
$uid = (int)$_SESSION['user_id'];
$u = db()->prepare('SELECT is_seller,seller_shop_name FROM users WHERE id=? LIMIT 1');
$u->execute([$uid]); $u = $u->fetch();
if (!$u['is_seller']) { header('Location: become_seller.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title']       ?? '');
    $desc    = trim($_POST['description'] ?? '');
    $price   = (float)str_replace(',','',$_POST['price'] ?? 0);
    $cat     = trim($_POST['category']    ?? 'Other');
    $cond    = trim($_POST['condition']   ?? 'Good');
    $stock   = max(1,(int)($_POST['stock'] ?? 1));
    $img1    = trim($_POST['img1']        ?? '');
    $img2    = trim($_POST['img2']        ?? '');
    $img3    = trim($_POST['img3']        ?? '');

    if (empty($title))   { $error = 'Title is required.'; }
    elseif ($price <= 0) { $error = 'Price must be greater than 0.'; }
    else {
        // Handle file uploads (base64)
        foreach (['img1_file'=>'img1','img2_file'=>'img2','img3_file'=>'img3'] as $fileKey=>$varKey) {
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error']===UPLOAD_ERR_OK) {
                $mime = mime_content_type($_FILES[$fileKey]['tmp_name']);
                if (in_array($mime,['image/jpeg','image/png','image/webp','image/gif']) && $_FILES[$fileKey]['size']<3*1024*1024) {
                    $$varKey = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($_FILES[$fileKey]['tmp_name']));
                }
            }
        }
        try {
            db()->prepare(
                'INSERT INTO listings (seller_id,title,description,price,category,condition_val,img1,img2,img3,stock)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            )->execute([$uid,$title,$desc,$price,$cat,$cond,$img1,$img2,$img3,$stock]);
            $lid = db()->lastInsertId();
            header('Location: listing.php?id='.$lid.'&new=1'); exit;
        } catch(PDOException $e) { $error = 'Could not save listing: '.$e->getMessage(); }
    }
}
$categories = ['Clothing','Shoes','Bags','Accessories','Electronics','Books','Collectibles','Furniture','Sports','Toys','Music','Art','Other'];
$conditions = ['Brand New','Like New','Good','Fair','For Parts'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Listing – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container py-4" style="max-width:820px">
  <a href="seller_dashboard.php" class="back-btn mb-4 d-inline-flex align-items-center gap-2">
    <i class="bi bi-arrow-left"></i> Back to Dashboard
  </a>
  <h1 class="admin-page-title mb-1">Create Listing</h1>
  <p class="admin-page-sub mb-4">Fill in the details for your pre-loved item</p>

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
                   placeholder="e.g. Vintage Levi's 501 Jeans" maxlength="200"
                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="pmodal-label">Description</label>
            <textarea name="description" class="form-control pmodal-textarea" rows="4"
                      placeholder="Describe the item — size, color, brand, any flaws…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="pmodal-label">Category</label>
              <select name="category" class="form-select pmodal-select">
                <?php foreach($categories as $c): ?><option value="<?= $c ?>" <?= ($_POST['category']??'')===$c?'selected':'' ?>><?= $c ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="pmodal-label">Condition</label>
              <select name="condition" class="form-select pmodal-select">
                <?php foreach($conditions as $c): ?><option value="<?= $c ?>" <?= ($_POST['condition']??'Good')===$c?'selected':'' ?>><?= $c ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="pmodal-label">Price (₱) <span class="text-danger">*</span></label>
              <div class="input-with-icon">
                <i class="bi bi-currency-exchange input-icon"></i>
                <input type="number" name="price" class="form-control auth-input" placeholder="0.00" min="1" step="0.01"
                       value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
              </div>
            </div>
            <div class="col-6">
              <label class="pmodal-label">Stock Quantity</label>
              <div class="input-with-icon">
                <i class="bi bi-boxes input-icon"></i>
                <input type="number" name="stock" class="form-control auth-input" min="1" max="999"
                       value="<?= htmlspecialchars($_POST['stock'] ?? 1) ?>">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Photos -->
      <div class="col-lg-5">
        <div class="admin-card">
          <h5 class="dash-card-title mb-3"><i class="bi bi-images me-2"></i>Photos</h5>
          <p class="text-muted small mb-3">Upload up to 3 photos. First photo is the main listing image.</p>
          <?php for($i=1;$i<=3;$i++): ?>
          <div class="listing-photo-slot mb-3" id="slot<?= $i ?>">
            <label class="listing-photo-label" for="img<?= $i ?>_file">
              <div class="listing-photo-preview" id="preview<?= $i ?>">
                <i class="bi bi-plus-circle" style="font-size:28px;color:var(--gray-300)"></i>
                <span class="listing-photo-hint">Photo <?= $i ?><?= $i===1?' (main)':'' ?></span>
              </div>
            </label>
            <input type="file" name="img<?= $i ?>_file" id="img<?= $i ?>_file"
                   accept="image/*" style="display:none"
                   onchange="previewListingPhoto(this,<?= $i ?>)">
            <input type="hidden" name="img<?= $i ?>" id="img<?= $i ?>_url" value="">
            <input type="url" name="img<?= $i ?>_link" class="form-control mt-2" style="font-size:12px;border-radius:8px"
                   placeholder="…or paste an image URL" oninput="previewFromUrl(this.value,<?= $i ?>)">
          </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mt-2">
      <a href="seller_dashboard.php" class="btn pmodal-btn-cancel px-5">Cancel</a>
      <button type="submit" class="btn pmodal-btn-save px-5">
        <i class="bi bi-cloud-upload me-2"></i>Publish Listing
      </button>
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
    showPreviewImg(n, e.target.result);
  };
  reader.readAsDataURL(input.files[0]);
}
function previewFromUrl(url, n) {
  if (!url) return;
  document.getElementById('img'+n+'_url').value = url;
  showPreviewImg(n, url);
}
function showPreviewImg(n, src) {
  const prev = document.getElementById('preview'+n);
  prev.innerHTML = `<img src="${src}" style="width:100%;height:100%;object-fit:cover;border-radius:10px"
    onerror="this.parentElement.innerHTML='<i class=\\'bi bi-x-circle\\' style=\\'font-size:28px;color:#ef4444\\'></i><span class=\\'listing-photo-hint\\'>Invalid image</span>'">`;
}
</script>
</body></html>
