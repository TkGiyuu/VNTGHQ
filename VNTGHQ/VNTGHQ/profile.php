<?php require 'auth_check.php';
require 'db.php';
$uid = (int)$_SESSION['user_id'];

// Always load fresh from DB
$row = db()->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
$row->execute([$uid]);
$u = $row->fetch();
$user      = $u['username']  ?? '';
$firstname = $u['firstname'] ?? '';
$lastname  = $u['lastname']  ?? '';
$email     = $u['email']     ?? '';
$phone     = $u['phone']     ?? '';
$photo_url = $u['photo_url'] ?? '';
$fullname  = trim("$firstname $lastname") ?: $user;

// Real stats from DB
$statsRow = db()->prepare(
    'SELECT COUNT(*) as purchases,
            COALESCE(AVG(f.rating),0) as avg_rating,
            COUNT(DISTINCT f.id) as feedback_count
     FROM orders o
     LEFT JOIN feedback f ON f.user_id=o.user_id
     WHERE o.user_id=? AND o.status != "cancelled"'
);
$statsRow->execute([$uid]);
$stats = $statsRow->fetch();
$purchases  = (int)$stats['purchases'];
$avgRating  = round((float)$stats['avg_rating'], 1);
// Listings = count of orders with status pending/processing (items they're selling - demo)
// In a real marketplace this would be a listings table; we approximate with orders placed
$listingsRow = db()->prepare('SELECT COUNT(*) FROM orders WHERE user_id=?');
$listingsRow->execute([$uid]);
$listings = (int)$listingsRow->fetchColumn();

$toast = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Account Info (now includes username) ─────────────────────────────────
    if ($action === 'account_info') {
        $new_username  = trim($_POST['username']  ?? $user);
        $new_firstname = trim($_POST['firstname'] ?? $firstname);
        $new_lastname  = trim($_POST['lastname']  ?? $lastname);
        $new_email     = trim($_POST['email']     ?? $email);
        $new_phone     = trim($_POST['phone']     ?? $phone);

        // Validate username uniqueness (if changed)
        $usernameError = '';
        if ($new_username !== $user) {
            if (strlen($new_username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
                $usernameError = 'Username must be 3+ characters: letters, numbers, underscores only.';
            } else {
                $chk = db()->prepare('SELECT id FROM users WHERE username=? AND id!=? LIMIT 1');
                $chk->execute([$new_username, $uid]);
                if ($chk->fetch()) $usernameError = 'That username is already taken.';
            }
        }
        if ($usernameError) {
            $toast = 'ERROR:' . $usernameError;
        } else {
            try {
                db()->prepare('UPDATE users SET username=?,firstname=?,lastname=?,email=?,phone=?,updated_at=NOW() WHERE id=?')
                   ->execute([$new_username,$new_firstname,$new_lastname,$new_email,$new_phone,$uid]);
                $_SESSION['user']      = $new_username;
                $_SESSION['firstname'] = $new_firstname;
                $_SESSION['lastname']  = $new_lastname;
                $_SESSION['email']     = $new_email;
                $_SESSION['phone']     = $new_phone;
                $user      = $new_username;
                $firstname = $new_firstname;
                $lastname  = $new_lastname;
                $email     = $new_email;
                $phone     = $new_phone;
                $fullname  = trim("$firstname $lastname") ?: $user;
                $toast = 'Account information updated successfully!';
            } catch(PDOException $e) {
                $toast = 'ERROR:Could not save. ' . $e->getMessage();
            }
        }
    }

    // ── Password Change ────────────────────────────────────────────────────────
    if ($action === 'security') {
        $cur  = $_POST['current_password'] ?? '';
        $newp = $_POST['new_password']     ?? '';
        $conf = $_POST['confirm_password'] ?? '';

        // Fetch current hash from DB
        $hashRow = db()->prepare('SELECT password_hash FROM users WHERE id=? LIMIT 1');
        $hashRow->execute([$uid]);
        $currentHash = $hashRow->fetchColumn();

        if (empty($cur)) {
            $toast = 'ERROR:Please enter your current password.';
        } elseif (!password_verify($cur, $currentHash)) {
            $toast = 'ERROR:Current password is incorrect.';
        } elseif (strlen($newp) < 6) {
            $toast = 'ERROR:New password must be at least 6 characters.';
        } elseif (password_verify($newp, $currentHash)) {
            $toast = 'ERROR:New password cannot be the same as your current password.';
        } elseif ($newp !== $conf) {
            $toast = 'ERROR:New passwords do not match.';
        } else {
            $hash = password_hash($newp, PASSWORD_DEFAULT);
            db()->prepare('UPDATE users SET password_hash=?,updated_at=NOW() WHERE id=?')->execute([$hash,$uid]);
            $toast = 'Password changed successfully!';
        }
    }

    // ── Payment ────────────────────────────────────────────────────────────────
    if ($action === 'payment') {
        $_SESSION['payment_method'] = trim($_POST['card_number'] ?? '');
        $toast = 'Payment method saved!';
    }

    // ── Feedback ───────────────────────────────────────────────────────────────
    if ($action === 'feedback') {
        $fb_text   = trim($_POST['feedback_text']     ?? '');
        $fb_rating = max(1, min(5, intval($_POST['rating'] ?? 5)));
        $fb_cat    = trim($_POST['feedback_category'] ?? 'general');
        try {
            db()->prepare('INSERT INTO feedback (user_id,rating,category,message) VALUES (?,?,?,?)')
               ->execute([$uid,$fb_rating,$fb_cat,$fb_text]);
            $toast = 'Thank you for your feedback!';
        } catch(PDOException $e) {
            $toast = 'ERROR:Could not save feedback.';
        }
    }

    // ── 2FA toggle ─────────────────────────────────────────────────────────────
    if ($action === 'twofa') {
        $_SESSION['twofa_enabled'] = isset($_POST['twofa_enable']);
        $toast = $_SESSION['twofa_enabled'] ? '2FA enabled!' : '2FA disabled.';
    }

    // ── Avatar upload (base64 saved to DB) ─────────────────────────────────────
    if ($action === 'avatar' && isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $mime    = mime_content_type($_FILES['avatar_file']['tmp_name']);
        if (!in_array($mime, $allowed)) {
            $toast = 'ERROR:Only JPG, PNG, GIF, or WEBP images are allowed.';
        } elseif ($_FILES['avatar_file']['size'] > 2 * 1024 * 1024) {
            $toast = 'ERROR:Image must be under 2 MB.';
        } else {
            $data    = file_get_contents($_FILES['avatar_file']['tmp_name']);
            $base64  = 'data:' . $mime . ';base64,' . base64_encode($data);
            try {
                db()->prepare('UPDATE users SET photo_url=?,updated_at=NOW() WHERE id=?')->execute([$base64,$uid]);
                $_SESSION['photo_url'] = $base64;
                $photo_url = $base64;
                $toast = 'Profile photo updated!';
            } catch(PDOException $e) {
                $toast = 'ERROR:Could not save photo.';
            }
        }
    }

    // ── Remove Avatar ──────────────────────────────────────────────────────────
    if ($action === 'remove_avatar') {
        db()->prepare('UPDATE users SET photo_url=NULL WHERE id=?')->execute([$uid]);
        $_SESSION['photo_url'] = '';
        $photo_url = '';
        $toast = 'Profile photo removed.';
    }
}

$twofa   = $_SESSION['twofa_enabled']  ?? false;
$payment = $_SESSION['payment_method'] ?? '';
$memberYear = date('Y', strtotime($u['created_at'] ?? 'now'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    /* Avatar upload area */
    .avatar-upload-area {
      border: 2.5px dashed var(--gray-200);
      border-radius: 14px;
      padding: 28px 20px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
      background: var(--gray-100);
      position: relative;
    }
    .avatar-upload-area:hover, .avatar-upload-area.drag-over {
      border-color: var(--brand-blue);
      background: var(--brand-light);
    }
    .avatar-upload-area input[type="file"] {
      position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }
    .avatar-preview-wrap {
      display: flex; flex-direction: column; align-items: center; gap: 10px;
    }
    .avatar-preview-img {
      width: 80px; height: 80px; border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--brand-blue);
      display: none;
    }
    .avatar-preview-placeholder {
      width: 80px; height: 80px; border-radius: 50%;
      background: var(--gray-200);
      display: flex; align-items: center; justify-content: center;
      font-size: 32px; color: var(--gray-400);
    }
    .avatar-upload-label {
      font-size: 13px; font-weight: 600; color: var(--gray-600);
    }
    .avatar-upload-hint { font-size: 11px; color: var(--gray-400); }
    .pw-same-error { font-size: 12px; color: #ef4444; font-weight: 600; display: none; margin-top: 4px; }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<?php if ($toast): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  <?php if (str_starts_with($toast, 'ERROR:')): ?>
  showToast('<i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars(substr($toast,6)) ?>', 'fav');
  <?php else: ?>
  showToast('<i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($toast) ?>', 'cart');
  <?php endif; ?>
});
</script>
<?php endif; ?>

<div class="container py-5">
  <div class="row g-4">

    <!-- ── LEFT: Profile Card ── -->
    <div class="col-lg-4">
      <div class="profile-card text-center p-4">

        <!-- Avatar display — no placeholder image, just icon or photo -->
        <div class="profile-avatar-wrap mx-auto mb-3" style="position:relative">
          <div class="profile-avatar" id="profileAvatarCircle">
            <?php if ($photo_url): ?>
              <img src="<?= htmlspecialchars($photo_url) ?>" id="profileAvatarImg"
                   alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
            <?php else: ?>
              <i class="bi bi-person-fill" id="profileAvatarIcon"></i>
            <?php endif; ?>
          </div>
          <button class="profile-avatar-edit"
                  onclick="openModal('modalAvatar')"
                  title="Change profile photo">
            <i class="bi bi-camera-fill"></i>
          </button>
        </div>

        <h3 class="profile-name"><?= htmlspecialchars($fullname) ?></h3>
        <p class="profile-role">@<?= htmlspecialchars($user) ?></p>
        <?php if ($email): ?>
        <p class="profile-email-disp"><?= htmlspecialchars($email) ?></p>
        <?php endif; ?>

        <!-- Real stats from DB -->
        <div class="profile-stats d-flex justify-content-center gap-4 mt-3">
          <div class="text-center">
            <span class="stat-num"><?= $purchases ?></span>
            <span class="stat-label d-block">Purchases</span>
          </div>
          <div class="stat-divider"></div>
          <div class="text-center">
            <span class="stat-num"><?= $listings ?></span>
            <span class="stat-label d-block">Listings</span>
          </div>
          <div class="stat-divider"></div>
          <div class="text-center">
            <span class="stat-num"><?= $avgRating > 0 ? $avgRating : '—' ?></span>
            <span class="stat-label d-block">Rating</span>
          </div>
        </div>

        <p class="text-muted mt-3 mb-0" style="font-size:12px">
          Member since <?= $memberYear ?>
        </p>
      </div>
    </div>

    <!-- ── RIGHT: Settings Menu ── -->
    <div class="col-lg-8">
      <div class="profile-menu-box p-4">
        <h4 class="profile-menu-heading mb-4">Account Settings</h4>
        <div class="row g-3">

          <div class="col-12">
            <button class="profile-menu-item w-100 border-0 text-start d-flex align-items-center gap-3"
                    onclick="openModal('modalAccountInfo')">
              <div class="profile-menu-icon"><i class="bi bi-person"></i></div>
              <div class="flex-grow-1">
                <span class="pmenu-title">Account Information</span>
                <span class="pmenu-sub d-block">Name, username, email, phone</span>
              </div>
              <i class="bi bi-chevron-right pmenu-arrow"></i>
            </button>
          </div>

          <div class="col-12">
            <button class="profile-menu-item w-100 border-0 text-start d-flex align-items-center gap-3"
                    onclick="openModal('modalSecurity')">
              <div class="profile-menu-icon" style="background:#8b5cf6"><i class="bi bi-shield-lock"></i></div>
              <div class="flex-grow-1">
                <span class="pmenu-title">Account Security</span>
                <span class="pmenu-sub d-block">Change password, 2FA</span>
              </div>
              <i class="bi bi-chevron-right pmenu-arrow"></i>
            </button>
          </div>

          <div class="col-12">
            <button class="profile-menu-item w-100 border-0 text-start d-flex align-items-center gap-3"
                    onclick="openModal('modalPayment')">
              <div class="profile-menu-icon" style="background:#22c55e"><i class="bi bi-credit-card"></i></div>
              <div class="flex-grow-1">
                <span class="pmenu-title">Payment Setting</span>
                <span class="pmenu-sub d-block">Cards, wallets, payment methods</span>
              </div>
              <i class="bi bi-chevron-right pmenu-arrow"></i>
            </button>
          </div>

          <div class="col-12">
            <button class="profile-menu-item w-100 border-0 text-start d-flex align-items-center gap-3"
                    onclick="openModal('modalFeedback')">
              <div class="profile-menu-icon" style="background:#f59e0b"><i class="bi bi-chat-square-text"></i></div>
              <div class="flex-grow-1">
                <span class="pmenu-title">Feedback</span>
                <span class="pmenu-sub d-block">Rate your experience</span>
              </div>
              <i class="bi bi-chevron-right pmenu-arrow"></i>
            </button>
          </div>

          <div class="col-12">
            <button class="profile-menu-item w-100 border-0 text-start d-flex align-items-center gap-3"
                    onclick="openModal('modalPolicies')">
              <div class="profile-menu-icon" style="background:#0ea5e9"><i class="bi bi-file-text"></i></div>
              <div class="flex-grow-1">
                <span class="pmenu-title">Policies</span>
                <span class="pmenu-sub d-block">Terms, privacy, return policy</span>
              </div>
              <i class="bi bi-chevron-right pmenu-arrow"></i>
            </button>
          </div>

          <div class="col-12 mt-2">
            <a href="logout.php" class="btn logout-btn w-100">
              <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ════════════════════════════════════════════════
     MODALS
     ════════════════════════════════════════════════ -->
<div class="pmodal-overlay" id="modalOverlay" onclick="closeAllModals()"></div>

<!-- ── 0. Avatar Upload Modal ── -->
<div class="pmodal" id="modalAvatar">
  <div class="pmodal-header">
    <div class="pmodal-icon" style="background:rgba(14,165,233,0.12);color:#0ea5e9"><i class="bi bi-camera-fill"></i></div>
    <div>
      <h5 class="pmodal-title">Profile Photo</h5>
      <p class="pmodal-sub">Upload or remove your profile picture</p>
    </div>
    <button class="pmodal-close" onclick="closeAllModals()"><i class="bi bi-x-lg"></i></button>
  </div>
  <div class="pmodal-body">
    <!-- Current photo preview -->
    <div class="text-center mb-4">
      <div style="width:80px;height:80px;border-radius:50%;margin:0 auto 8px;
                  background:var(--gray-200);display:flex;align-items:center;justify-content:center;
                  font-size:32px;color:var(--gray-400);overflow:hidden;border:3px solid var(--brand-blue)"
           id="avatarModalPreviewWrap">
        <?php if ($photo_url): ?>
          <img src="<?= htmlspecialchars($photo_url) ?>" id="avatarModalPreviewImg"
               style="width:100%;height:100%;object-fit:cover">
        <?php else: ?>
          <i class="bi bi-person-fill" id="avatarModalIcon"></i>
          <img id="avatarModalPreviewImg" src="" style="display:none;width:100%;height:100%;object-fit:cover">
        <?php endif; ?>
      </div>
      <p class="text-muted mb-0" style="font-size:12px">Current photo</p>
    </div>

    <!-- Upload form -->
    <form method="POST" enctype="multipart/form-data" id="avatarForm">
      <input type="hidden" name="action" value="avatar">
      <div class="avatar-upload-area mb-3" id="avatarDropZone"
           ondragover="event.preventDefault();this.classList.add('drag-over')"
           ondragleave="this.classList.remove('drag-over')"
           ondrop="handleAvatarDrop(event)">
        <input type="file" name="avatar_file" id="avatarFileInput" accept="image/*"
               onchange="previewAvatarFile(this)">
        <div class="avatar-preview-wrap">
          <i class="bi bi-cloud-arrow-up" style="font-size:36px;color:var(--brand-blue);margin-bottom:4px"></i>
          <p class="avatar-upload-label mb-0">Click or drag &amp; drop an image</p>
          <p class="avatar-upload-hint mb-0">JPG, PNG, GIF or WEBP · max 2 MB</p>
          <p class="avatar-upload-hint" id="avatarFileName" style="color:var(--brand-blue);font-weight:600"></p>
        </div>
      </div>
      <div class="pmodal-footer">
        <?php if ($photo_url): ?>
        <form method="POST" style="margin:0">
          <input type="hidden" name="action" value="remove_avatar">
          <button type="submit" class="btn" style="background:rgba(239,68,68,0.1);color:#ef4444;border:none;border-radius:8px;font-weight:600;padding:9px 16px">
            <i class="bi bi-trash me-1"></i>Remove Photo
          </button>
        </form>
        <?php endif; ?>
        <button type="button" class="btn pmodal-btn-cancel" onclick="closeAllModals()">Cancel</button>
        <button type="submit" form="avatarForm" class="btn pmodal-btn-save">
          <i class="bi bi-check-circle me-1"></i>Save Photo
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── 1. Account Information (with username) ── -->
<div class="pmodal" id="modalAccountInfo">
  <div class="pmodal-header">
    <div class="pmodal-icon" style="background:rgba(14,165,233,0.12);color:#0ea5e9"><i class="bi bi-person"></i></div>
    <div>
      <h5 class="pmodal-title">Account Information</h5>
      <p class="pmodal-sub">Update your personal details</p>
    </div>
    <button class="pmodal-close" onclick="closeAllModals()"><i class="bi bi-x-lg"></i></button>
  </div>
  <form method="POST" class="pmodal-body">
    <input type="hidden" name="action" value="account_info">
    <div class="row g-3">
      <div class="col-6">
        <label class="pmodal-label">First Name</label>
        <div class="input-with-icon">
          <i class="bi bi-person input-icon"></i>
          <input type="text" name="firstname" class="form-control auth-input"
                 value="<?= htmlspecialchars($firstname) ?>" placeholder="First name">
        </div>
      </div>
      <div class="col-6">
        <label class="pmodal-label">Last Name</label>
        <div class="input-with-icon">
          <i class="bi bi-person input-icon"></i>
          <input type="text" name="lastname" class="form-control auth-input"
                 value="<?= htmlspecialchars($lastname) ?>" placeholder="Last name">
        </div>
      </div>
      <div class="col-12">
        <label class="pmodal-label">Username</label>
        <div class="input-with-icon">
          <i class="bi bi-at input-icon"></i>
          <input type="text" name="username" class="form-control auth-input"
                 value="<?= htmlspecialchars($user) ?>" placeholder="your_username"
                 oninput="checkUsernameChars(this)">
        </div>
        <small class="text-muted" style="font-size:11px">Letters, numbers, and underscores only. Min 3 characters.</small>
        <small class="pw-same-error" id="usernameError">Invalid username format.</small>
      </div>
      <div class="col-12">
        <label class="pmodal-label">Email Address</label>
        <div class="input-with-icon">
          <i class="bi bi-envelope input-icon"></i>
          <input type="email" name="email" class="form-control auth-input"
                 value="<?= htmlspecialchars($email) ?>" placeholder="your@email.com">
        </div>
      </div>
      <div class="col-12">
        <label class="pmodal-label">Phone Number</label>
        <div class="input-with-icon">
          <i class="bi bi-telephone input-icon"></i>
          <input type="tel" name="phone" class="form-control auth-input"
                 value="<?= htmlspecialchars($phone) ?>" placeholder="+63 9XX XXX XXXX">
        </div>
      </div>
    </div>
    <div class="pmodal-footer">
      <button type="button" class="btn pmodal-btn-cancel" onclick="closeAllModals()">Cancel</button>
      <button type="submit" class="btn pmodal-btn-save">Save Changes</button>
    </div>
  </form>
</div>

<!-- ── 2. Account Security ── -->
<div class="pmodal" id="modalSecurity">
  <div class="pmodal-header">
    <div class="pmodal-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6"><i class="bi bi-shield-lock"></i></div>
    <div>
      <h5 class="pmodal-title">Account Security</h5>
      <p class="pmodal-sub">Change password and manage 2FA</p>
    </div>
    <button class="pmodal-close" onclick="closeAllModals()"><i class="bi bi-x-lg"></i></button>
  </div>
  <form method="POST" class="pmodal-body" id="securityForm" onsubmit="return validateSecurityForm()">
    <input type="hidden" name="action" value="security">
    <div class="row g-3">
      <div class="col-12">
        <label class="pmodal-label">Current Password</label>
        <div class="input-with-icon">
          <i class="bi bi-lock input-icon"></i>
          <input type="password" name="current_password" id="sec_curpw"
                 class="form-control auth-input" placeholder="Enter current password">
          <button type="button" class="btn-pw-toggle" onclick="togglePassword(this)" tabindex="-1"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="col-12">
        <label class="pmodal-label">New Password</label>
        <div class="input-with-icon">
          <i class="bi bi-lock-fill input-icon"></i>
          <input type="password" name="new_password" id="sec_newpw"
                 class="form-control auth-input" placeholder="At least 6 characters">
          <button type="button" class="btn-pw-toggle" onclick="togglePassword(this)" tabindex="-1"><i class="bi bi-eye"></i></button>
        </div>
        <div class="pw-strength-wrap mt-2"><div class="pw-strength-bar" id="sec-strength-bar"></div></div>
        <small class="pw-strength-label" id="sec-strength-label"></small>
        <!-- Same-as-current warning -->
        <small class="pw-same-error" id="sec-same-error">
          <i class="bi bi-exclamation-triangle me-1"></i>New password cannot be the same as your current password.
        </small>
      </div>
      <div class="col-12">
        <label class="pmodal-label">Confirm New Password</label>
        <div class="input-with-icon">
          <i class="bi bi-shield-lock input-icon"></i>
          <input type="password" name="confirm_password" id="sec_confpw"
                 class="form-control auth-input" placeholder="Repeat new password">
          <button type="button" class="btn-pw-toggle" onclick="togglePassword(this)" tabindex="-1"><i class="bi bi-eye"></i></button>
        </div>
        <small class="fp-match-msg mt-1 d-block" id="sec-match-msg"></small>
      </div>
      <div class="col-12">
        <div class="twofa-toggle-row">
          <div>
            <p class="twofa-label">Two-Factor Authentication (2FA)</p>
            <p class="twofa-sub">Add an extra layer of security to your account</p>
          </div>
          <label class="toggle-switch">
            <input type="checkbox" name="twofa_enable" id="twofaCheck" <?= $twofa ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
          </label>
        </div>
      </div>
    </div>
    <div class="pmodal-footer">
      <button type="button" class="btn pmodal-btn-cancel" onclick="closeAllModals()">Cancel</button>
      <button type="submit" class="btn pmodal-btn-save">Save Security Settings</button>
    </div>
  </form>
</div>

<!-- ── 3. Payment Setting ── -->
<div class="pmodal" id="modalPayment">
  <div class="pmodal-header">
    <div class="pmodal-icon" style="background:rgba(34,197,94,0.12);color:#22c55e"><i class="bi bi-credit-card"></i></div>
    <div>
      <h5 class="pmodal-title">Payment Setting</h5>
      <p class="pmodal-sub">Manage your cards and payment methods</p>
    </div>
    <button class="pmodal-close" onclick="closeAllModals()"><i class="bi bi-x-lg"></i></button>
  </div>
  <form method="POST" class="pmodal-body">
    <input type="hidden" name="action" value="payment">
    <?php if ($payment): ?>
    <div class="payment-saved-card mb-3">
      <i class="bi bi-credit-card-2-front-fill me-2"></i>
      Card ending in <strong>•••• <?= htmlspecialchars(substr(preg_replace('/\D/','',$payment),-4)) ?></strong>
      <span class="payment-saved-badge">Saved</span>
    </div>
    <?php endif; ?>
    <div class="row g-3">
      <div class="col-12">
        <label class="pmodal-label">Card Number</label>
        <div class="input-with-icon">
          <i class="bi bi-credit-card input-icon"></i>
          <input type="text" name="card_number" class="form-control auth-input"
                 placeholder="1234 5678 9012 3456" maxlength="19"
                 oninput="formatCard(this)" value="<?= htmlspecialchars($payment) ?>">
        </div>
      </div>
      <div class="col-6">
        <label class="pmodal-label">Expiry Date</label>
        <div class="input-with-icon">
          <i class="bi bi-calendar input-icon"></i>
          <input type="text" name="expiry" class="form-control auth-input"
                 placeholder="MM/YY" maxlength="5" oninput="formatExpiry(this)">
        </div>
      </div>
      <div class="col-6">
        <label class="pmodal-label">CVV</label>
        <div class="input-with-icon">
          <i class="bi bi-lock input-icon"></i>
          <input type="password" name="cvv" class="form-control auth-input" placeholder="•••" maxlength="4">
        </div>
      </div>
      <div class="col-12">
        <label class="pmodal-label">Cardholder Name</label>
        <div class="input-with-icon">
          <i class="bi bi-person input-icon"></i>
          <input type="text" name="card_name" class="form-control auth-input"
                 placeholder="Name on card" value="<?= htmlspecialchars($fullname) ?>">
        </div>
      </div>
    </div>
    <div class="payment-methods-row mt-3">
      <span class="pmodal-label d-block mb-2">Quick Add Wallet</span>
      <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="pay-method-btn" onclick="selectPayMethod('GCash')"><i class="bi bi-phone me-1"></i>GCash</button>
        <button type="button" class="pay-method-btn" onclick="selectPayMethod('PayMaya')"><i class="bi bi-phone me-1"></i>Maya</button>
        <button type="button" class="pay-method-btn" onclick="selectPayMethod('COD')"><i class="bi bi-box me-1"></i>COD</button>
        <button type="button" class="pay-method-btn" onclick="selectPayMethod('Bank Transfer')"><i class="bi bi-bank me-1"></i>Bank</button>
      </div>
    </div>
    <div class="pmodal-footer">
      <button type="button" class="btn pmodal-btn-cancel" onclick="closeAllModals()">Cancel</button>
      <button type="submit" class="btn pmodal-btn-save">Save Payment</button>
    </div>
  </form>
</div>

<!-- ── 4. Feedback ── -->
<div class="pmodal" id="modalFeedback">
  <div class="pmodal-header">
    <div class="pmodal-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b"><i class="bi bi-chat-square-heart"></i></div>
    <div>
      <h5 class="pmodal-title">Feedback</h5>
      <p class="pmodal-sub">Share your experience with us</p>
    </div>
    <button class="pmodal-close" onclick="closeAllModals()"><i class="bi bi-x-lg"></i></button>
  </div>
  <form method="POST" class="pmodal-body">
    <input type="hidden" name="action" value="feedback">
    <div class="row g-3">
      <div class="col-12">
        <label class="pmodal-label">How would you rate your experience?</label>
        <div class="star-rating" id="starRating">
          <?php for ($s = 1; $s <= 5; $s++): ?>
          <button type="button" class="star-btn <?= ($s <= ($_SESSION['last_rating'] ?? 5)) ? 'active' : '' ?>"
                  data-val="<?= $s ?>" onclick="setRating(<?= $s ?>)">
            <i class="bi bi-star-fill"></i>
          </button>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" id="ratingInput" value="<?= $_SESSION['last_rating'] ?? 5 ?>">
      </div>
      <div class="col-12">
        <label class="pmodal-label">Your Feedback</label>
        <textarea name="feedback_text" class="form-control pmodal-textarea" rows="4"
                  placeholder="Tell us what you think about VNTG HQ..."><?= htmlspecialchars($_SESSION['last_feedback'] ?? '') ?></textarea>
      </div>
      <div class="col-12">
        <label class="pmodal-label">Category</label>
        <select name="feedback_category" class="form-select pmodal-select">
          <option value="general">General Experience</option>
          <option value="shipping">Shipping & Delivery</option>
          <option value="product">Product Quality</option>
          <option value="support">Customer Support</option>
          <option value="app">App / Website</option>
        </select>
      </div>
    </div>
    <div class="pmodal-footer">
      <button type="button" class="btn pmodal-btn-cancel" onclick="closeAllModals()">Cancel</button>
      <button type="submit" class="btn pmodal-btn-save">Submit Feedback</button>
    </div>
  </form>
</div>

<!-- ── 5. Policies ── -->
<div class="pmodal pmodal-wide" id="modalPolicies">
  <div class="pmodal-header">
    <div class="pmodal-icon" style="background:rgba(14,165,233,0.12);color:#0ea5e9"><i class="bi bi-file-text"></i></div>
    <div>
      <h5 class="pmodal-title">Policies</h5>
      <p class="pmodal-sub">Terms of Service, Privacy Policy & Returns</p>
    </div>
    <button class="pmodal-close" onclick="closeAllModals()"><i class="bi bi-x-lg"></i></button>
  </div>
  <div class="pmodal-body">
    <ul class="nav nav-pills policy-tabs mb-4">
      <li class="nav-item"><button class="nav-link active" onclick="switchTab(this,'tabTOS')">Terms of Service</button></li>
      <li class="nav-item"><button class="nav-link" onclick="switchTab(this,'tabPrivacy')">Privacy Policy</button></li>
      <li class="nav-item"><button class="nav-link" onclick="switchTab(this,'tabReturns')">Returns & Refunds</button></li>
    </ul>
    <div id="tabTOS" class="policy-content active">
      <h6 class="policy-heading">Terms of Service</h6>
      <p>By using VNTG HQ, you agree to buy and sell only genuine pre-loved items. Misrepresentation of items is strictly prohibited and may result in account suspension.</p>
      <p>VNTG HQ acts as a marketplace platform. We are not responsible for disputes between buyers and sellers, but we will mediate in good faith when notified.</p>
      <p>You agree not to use the platform for fraudulent activity, spam, or illegal transactions. Violations may result in permanent bans.</p>
    </div>
    <div id="tabPrivacy" class="policy-content" style="display:none">
      <h6 class="policy-heading">Privacy Policy</h6>
      <p>VNTG HQ collects minimal personal information required to operate your account — including your name, username, email, and transaction history.</p>
      <p>We do not sell your personal data to third parties. Your information is used solely to provide and improve the VNTG HQ platform experience.</p>
      <p>You have the right to request deletion of your account and associated data at any time by contacting our support team.</p>
    </div>
    <div id="tabReturns" class="policy-content" style="display:none">
      <h6 class="policy-heading">Returns & Refunds</h6>
      <p>Returns are accepted within <strong>7 days</strong> of delivery if the item received significantly differs from the listing description.</p>
      <p>Refunds are processed within <strong>5–10 business days</strong> once the return is approved and item received by the seller.</p>
      <p>Items marked as <em>Final Sale</em> are not eligible for return unless damaged or counterfeit.</p>
    </div>
    <div class="pmodal-footer">
      <button type="button" class="btn pmodal-btn-save" onclick="closeAllModals()">I Understand</button>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
<script>
/* ── Modal open/close ── */
function openModal(id) {
  document.getElementById('modalOverlay').classList.add('active');
  document.getElementById(id).classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeAllModals() {
  document.querySelectorAll('.pmodal').forEach(m => m.classList.remove('active'));
  document.getElementById('modalOverlay').classList.remove('active');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAllModals(); });

/* ── Avatar file preview (modal) ── */
function previewAvatarFile(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  document.getElementById('avatarFileName').textContent = file.name;
  const reader = new FileReader();
  reader.onload = e => {
    const previewImg = document.getElementById('avatarModalPreviewImg');
    const previewIcon = document.getElementById('avatarModalIcon');
    previewImg.src = e.target.result;
    previewImg.style.display = 'block';
    if (previewIcon) previewIcon.style.display = 'none';
  };
  reader.readAsDataURL(file);
}

function handleAvatarDrop(e) {
  e.preventDefault();
  document.getElementById('avatarDropZone').classList.remove('drag-over');
  const file = e.dataTransfer.files[0];
  if (file && file.type.startsWith('image/')) {
    const input = document.getElementById('avatarFileInput');
    const dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    previewAvatarFile(input);
  }
}

/* ── Username validation ── */
function checkUsernameChars(input) {
  const errEl = document.getElementById('usernameError');
  const valid = /^[a-zA-Z0-9_]{3,}$/.test(input.value);
  errEl.style.display = (!valid && input.value.length > 0) ? 'block' : 'none';
}

/* ── Password security form: same-as-current + match checks ── */
const secCur  = document.getElementById('sec_curpw');
const secNew  = document.getElementById('sec_newpw');
const secConf = document.getElementById('sec_confpw');

function checkSamePassword() {
  const errEl = document.getElementById('sec-same-error');
  if (!secCur || !secNew || !errEl) return false;
  const isSame = secCur.value.length > 0 && secNew.value.length > 0 && secCur.value === secNew.value;
  errEl.style.display = isSame ? 'block' : 'none';
  return isSame;
}

function validateSecurityForm() {
  if (checkSamePassword()) {
    showToast('<i class="bi bi-exclamation-triangle me-2"></i>New password cannot be the same as your current password.', 'fav');
    return false;
  }
  if (secNew.value && secConf.value && secNew.value !== secConf.value) {
    showToast('<i class="bi bi-exclamation-triangle me-2"></i>Passwords do not match.', 'fav');
    return false;
  }
  return true;
}

if (secNew) {
  secNew.addEventListener('input', () => {
    checkSamePassword();
    // Strength meter
    const pw = secNew.value;
    let score = 0;
    if (pw.length >= 6)  score++;
    if (pw.length >= 10) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    const labels = ['','Very Weak','Weak','Fair','Strong','Very Strong'];
    const colors = ['','#ef4444','#f97316','#eab308','#22c55e','#16a34a'];
    const pcts   = ['0%','20%','40%','60%','80%','100%'];
    document.getElementById('sec-strength-bar').style.width      = pw ? pcts[score] : '0%';
    document.getElementById('sec-strength-bar').style.background = colors[score] || '#e2e8f0';
    document.getElementById('sec-strength-label').textContent    = pw ? labels[score] : '';
    document.getElementById('sec-strength-label').style.color    = colors[score] || '';
  });
}
if (secCur) { secCur.addEventListener('input', checkSamePassword); }
if (secConf) {
  secConf.addEventListener('input', () => {
    const msg = document.getElementById('sec-match-msg');
    if (!secConf.value) { msg.textContent=''; return; }
    if (secConf.value === (secNew?.value||'')) {
      msg.textContent='✓ Passwords match'; msg.style.color='#22c55e';
    } else {
      msg.textContent='✗ Passwords do not match'; msg.style.color='#ef4444';
    }
  });
}

/* ── Card formatter ── */
function formatCard(i){ let v=i.value.replace(/\D/g,'').slice(0,16); i.value=v.replace(/(.{4})/g,'$1 ').trim(); }
function formatExpiry(i){ let v=i.value.replace(/\D/g,'').slice(0,4); if(v.length>=3)v=v.slice(0,2)+'/'+v.slice(2); i.value=v; }
function selectPayMethod(method) {
  showToast(`<i class="bi bi-check-circle me-2"></i><strong>${method}</strong> selected.`, 'cart');
}

/* ── Star rating ── */
function setRating(val) {
  document.getElementById('ratingInput').value = val;
  document.querySelectorAll('.star-btn').forEach(b => {
    b.classList.toggle('active', parseInt(b.dataset.val) <= val);
  });
}

/* ── Policy tabs ── */
function switchTab(btn, tabId) {
  document.querySelectorAll('.policy-tabs .nav-link').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.policy-content').forEach(t => t.style.display = 'none');
  btn.classList.add('active');
  document.getElementById(tabId).style.display = 'block';
}
</script>
</body>
</html>
