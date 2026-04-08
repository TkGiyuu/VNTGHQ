<?php require 'auth_check.php';
require 'db.php';
$uid       = (int)$_SESSION['user_id'];
$user      = $_SESSION['user']      ?? '';
$firstname = $_SESSION['firstname'] ?? '';
$lastname  = $_SESSION['lastname']  ?? '';
$email     = $_SESSION['email']     ?? '';
$phone     = $_SESSION['phone']     ?? '';
$fullname  = trim("$firstname $lastname") ?: $user;

//Handle POST saves 
$toast = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'account_info') {
        $firstname = trim($_POST['firstname'] ?? $firstname);
        $lastname  = trim($_POST['lastname']  ?? $lastname);
        $email     = trim($_POST['email']     ?? $email);
        $phone     = trim($_POST['phone']     ?? $phone);
        try {
            db()->prepare('UPDATE users SET firstname=?,lastname=?,email=?,phone=?,updated_at=NOW() WHERE id=?')
               ->execute([$firstname,$lastname,$email,$phone,$uid]);
        } catch(PDOException $e){}
        $_SESSION['firstname'] = $firstname;
        $_SESSION['lastname']  = $lastname;
        $_SESSION['email']     = $email;
        $_SESSION['phone']     = $phone;
        $fullname  = trim("$firstname $lastname") ?: $user;
        $toast = 'Account information updated successfully!';
    }

    if ($action === 'security') {
        $cur  = $_POST['current_password'] ?? '';
        $newp = $_POST['new_password']     ?? '';
        $conf = $_POST['confirm_password'] ?? '';
        if (empty($cur)) {
            $toast = 'ERROR:Please enter your current password.';
        } elseif (strlen($newp) < 6) {
            $toast = 'ERROR:New password must be at least 6 characters.';
        } elseif ($newp !== $conf) {
            $toast = 'ERROR:New passwords do not match.';
        } else {
            $hash = password_hash($newp, PASSWORD_DEFAULT);
            try {
                db()->prepare('UPDATE users SET password_hash=?,updated_at=NOW() WHERE id=?')
                   ->execute([$hash,$uid]);
            } catch(PDOException $e){}
            $_SESSION['pw_hash'] = $hash;
            $toast = 'Password changed successfully!';
        }
    }

    if ($action === 'payment') {
        $_SESSION['payment_method'] = trim($_POST['card_number'] ?? '');
        $toast = 'Payment method saved!';
    }

    if ($action === 'feedback') {
        $fb_text = trim($_POST['feedback_text'] ?? '');
        $fb_rating = max(1,min(5,intval($_POST['rating'] ?? 5)));
        $fb_cat  = trim($_POST['feedback_category'] ?? 'general');
        try {
            db()->prepare('INSERT INTO feedback (user_id,rating,category,message) VALUES (?,?,?,?)')
               ->execute([$uid,$fb_rating,$fb_cat,$fb_text]);
        } catch(PDOException $e){}
        $_SESSION['last_feedback'] = $fb_text;
        $_SESSION['last_rating']   = $fb_rating;
        $toast = 'Thank you for your feedback!';
    }

    if ($action === 'twofa') {
        $_SESSION['twofa_enabled'] = isset($_POST['twofa_enable']);
        $toast = $_SESSION['twofa_enabled'] ? '2FA enabled!' : '2FA disabled.';
    }
}

$twofa   = $_SESSION['twofa_enabled']  ?? false;
$payment = $_SESSION['payment_method'] ?? '';
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

    <!-- LEFT: Profile Card -->
    <div class="col-lg-4">
      <div class="profile-card text-center p-4">
        <div class="profile-avatar-wrap mx-auto mb-3">
          <div class="profile-avatar" id="avatarDisplay">
            <i class="bi bi-person-fill" id="avatarIcon"></i>
            <img id="avatarImg" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:50%">
          </div>
          <label class="profile-avatar-edit" for="avatarUpload" title="Change photo">
            <i class="bi bi-camera-fill"></i>
          </label>
          <input type="file" id="avatarUpload" accept="image/*" style="display:none" onchange="previewAvatar(this)">
        </div>
        <h3 class="profile-name" id="profileName"><?= htmlspecialchars($fullname) ?></h3>
        <p class="profile-role">@<?= htmlspecialchars($user) ?></p>
        <?php if ($email): ?>
        <p class="profile-email-disp"><?= htmlspecialchars($email) ?></p>
        <?php endif; ?>
        <div class="profile-stats d-flex justify-content-center gap-4 mt-3">
          <div class="text-center">
            <span class="stat-num">12</span>
            <span class="stat-label d-block">Purchases</span>
          </div>
          <div class="stat-divider"></div>
          <div class="text-center">
            <span class="stat-num">5</span>
            <span class="stat-label d-block">Listings</span>
          </div>
          <div class="stat-divider"></div>
          <div class="text-center">
            <span class="stat-num">4.9</span>
            <span class="stat-label d-block">Rating</span>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT: Settings Menu -->
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
                <span class="pmenu-sub d-block">Name, email, phone number</span>
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
                <span class="pmenu-sub d-block">Password, 2FA settings</span>
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



<!-- Modal Overlay -->
<div class="pmodal-overlay" id="modalOverlay" onclick="closeAllModals()"></div>

<!--  1. Account Information -->
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
          <input type="text" class="form-control auth-input"
                 value="<?= htmlspecialchars($user) ?>" disabled>
        </div>
        <small class="text-muted">Username cannot be changed.</small>
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

<!-- 2. Account Security -->
<div class="pmodal" id="modalSecurity">
  <div class="pmodal-header">
    <div class="pmodal-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6"><i class="bi bi-shield-lock"></i></div>
    <div>
      <h5 class="pmodal-title">Account Security</h5>
      <p class="pmodal-sub">Change password and manage 2FA</p>
    </div>
    <button class="pmodal-close" onclick="closeAllModals()"><i class="bi bi-x-lg"></i></button>
  </div>
  <form method="POST" class="pmodal-body">
    <input type="hidden" name="action" value="security">
    <div class="row g-3">
      <div class="col-12">
        <label class="pmodal-label">Current Password</label>
        <div class="input-with-icon">
          <i class="bi bi-lock input-icon"></i>
          <input type="password" name="current_password" class="form-control auth-input" placeholder="Enter current password">
          <button type="button" class="btn-pw-toggle" onclick="togglePassword(this)" tabindex="-1"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div class="col-12">
        <label class="pmodal-label">New Password</label>
        <div class="input-with-icon">
          <i class="bi bi-lock-fill input-icon"></i>
          <input type="password" name="new_password" id="sec_newpw" class="form-control auth-input" placeholder="At least 6 characters">
          <button type="button" class="btn-pw-toggle" onclick="togglePassword(this)" tabindex="-1"><i class="bi bi-eye"></i></button>
        </div>
        <div class="pw-strength-wrap mt-2"><div class="pw-strength-bar" id="sec-strength-bar"></div></div>
        <small class="pw-strength-label" id="sec-strength-label"></small>
      </div>
      <div class="col-12">
        <label class="pmodal-label">Confirm New Password</label>
        <div class="input-with-icon">
          <i class="bi bi-shield-lock input-icon"></i>
          <input type="password" name="confirm_password" id="sec_confpw" class="form-control auth-input" placeholder="Repeat new password">
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

<!-- 3. Payment Setting -->
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

    <!-- Saved method display -->
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
                 oninput="formatCard(this)"
                 value="<?= htmlspecialchars($payment) ?>">
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
          <input type="password" name="cvv" class="form-control auth-input"
                 placeholder="•••" maxlength="4">
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
    <!-- Payment method quick picks -->
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

<!-- 4. Feedback -->
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

<!-- 5. Policies -->
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
    <!-- Tab nav -->
    <ul class="nav nav-pills policy-tabs mb-4" id="policyTab">
      <li class="nav-item"><button class="nav-link active" onclick="switchTab(this,'tabTOS')">Terms of Service</button></li>
      <li class="nav-item"><button class="nav-link" onclick="switchTab(this,'tabPrivacy')">Privacy Policy</button></li>
      <li class="nav-item"><button class="nav-link" onclick="switchTab(this,'tabReturns')">Returns & Refunds</button></li>
    </ul>
    <div id="tabTOS" class="policy-content active">
      <h6 class="policy-heading">Terms of Service</h6>
      <p>By using VNTG HQ, you agree to buy and sell only genuine pre-loved items. Misrepresentation of items is strictly prohibited and may result in account suspension.</p>
      <p>VNTG HQ acts as a marketplace platform. We are not responsible for disputes between buyers and sellers, but we will mediate in good faith when notified.</p>
      <p>You agree not to use the platform for fraudulent activity, spam, or illegal transactions. Violations may result in permanent bans and may be reported to authorities.</p>
      <p>All listed prices are final unless the seller explicitly marks items as negotiable. Service fees may apply on successful transactions.</p>
    </div>
    <div id="tabPrivacy" class="policy-content" style="display:none">
      <h6 class="policy-heading">Privacy Policy</h6>
      <p>VNTG HQ collects minimal personal information required to operate your account — including your name, username, email, and transaction history.</p>
      <p>We do not sell your personal data to third parties. Your information is used solely to provide and improve the VNTG HQ platform experience.</p>
      <p>Cookies are used to maintain your session and remember your preferences. You may disable cookies in your browser settings, though this may affect functionality.</p>
      <p>You have the right to request deletion of your account and associated data at any time by contacting our support team.</p>
    </div>
    <div id="tabReturns" class="policy-content" style="display:none">
      <h6 class="policy-heading">Returns & Refunds</h6>
      <p>Returns are accepted within <strong>7 days</strong> of delivery if the item received significantly differs from the listing description or photos.</p>
      <p>To initiate a return, contact the seller directly through the platform. If no resolution is reached within 48 hours, escalate to VNTG HQ support.</p>
      <p>Refunds are processed back to the original payment method within <strong>5–10 business days</strong> once the return is approved and the item is received by the seller.</p>
      <p>Items marked as <em>Final Sale</em> or <em>No Returns</em> in the listing are not eligible for return unless the item is damaged or counterfeit.</p>
    </div>
    <div class="pmodal-footer">
      <button type="button" class="btn pmodal-btn-save" onclick="closeAllModals()">I Understand</button>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
<script>const FIREBASE_UID = '<?= htmlspecialchars($uid) ?>';</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
<script>
/* Modal open/close */
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

/* Avatar preview */
function previewAvatar(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = async e => {
    const dataUrl = e.target.result;
    // Update profile page display
    document.getElementById('avatarIcon').style.display = 'none';
    const img = document.getElementById('avatarImg');
    img.src = dataUrl;
    img.style.display = 'block';
    // Save to localStorage so navbar can pick it up on every page
    localStorage.setItem('vntghq_avatar', dataUrl);
    // Also update navbar avatar immediately without reload
    const navImg = document.querySelector('.nav-avatar-img');
    if (navImg) { navImg.src = dataUrl; }
    const navInit = document.getElementById('navInitials') || document.getElementById('navFallback');
    if (navInit && !navImg) {
      const ni = document.createElement('img');
      ni.src = dataUrl; ni.className = 'nav-avatar-img';
      navInit.replaceWith(ni);
    }
    // Save to Firebase if user is logged in via Firebase
    try {
      const uid = typeof FIREBASE_UID !== 'undefined' ? FIREBASE_UID : null;
      if (uid && uid !== 'guest' && typeof fbSaveProfile === 'function') {
        await fbSaveProfile(uid, { photoURL: dataUrl.substring(0, 500) });
      }
    } catch(e) { console.warn('Avatar Firebase save skipped:', e.message); }
    showToast('<i class="bi bi-check-circle me-2"></i>Profile photo updated!', 'cart');
  };
  reader.readAsDataURL(input.files[0]);
}
// Restore saved avatar on page load
const savedAvatar = localStorage.getItem('vntghq_avatar');
if (savedAvatar) {
  document.getElementById('avatarIcon').style.display = 'none';
  const img = document.getElementById('avatarImg');
  img.src = savedAvatar;
  img.style.display = 'block';
}

/* Password strength (security modal)*/
const secPw   = document.getElementById('sec_newpw');
const secConf = document.getElementById('sec_confpw');
if (secPw) {
  secPw.addEventListener('input', () => {
    const pw = secPw.value;
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
if (secConf) {
  secConf.addEventListener('input', () => {
    const msg = document.getElementById('sec-match-msg');
    if (!secConf.value) { msg.textContent=''; return; }
    if (secConf.value === (secPw?.value||'')) {
      msg.textContent='✓ Passwords match'; msg.style.color='#22c55e';
    } else {
      msg.textContent='✗ Passwords do not match'; msg.style.color='#ef4444';
    }
  });
}

/* Card number formatter*/
function formatCard(input) {
  let v = input.value.replace(/\D/g,'').slice(0,16);
  input.value = v.replace(/(.{4})/g,'$1 ').trim();
}
function formatExpiry(input) {
  let v = input.value.replace(/\D/g,'').slice(0,4);
  if (v.length >= 3) v = v.slice(0,2)+'/'+v.slice(2);
  input.value = v;
}
function selectPayMethod(method) {
  showToast(`<i class="bi bi-check-circle me-2"></i><strong>${method}</strong> selected as payment method.`, 'cart');
}

/* Star rating */
function setRating(val) {
  document.getElementById('ratingInput').value = val;
  document.querySelectorAll('.star-btn').forEach(b => {
    b.classList.toggle('active', parseInt(b.dataset.val) <= val);
  });
}

/* Policy tabs */
function switchTab(btn, tabId) {
  document.querySelectorAll('.policy-tabs .nav-link').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.policy-content').forEach(t => t.style.display = 'none');
  btn.classList.add('active');
  document.getElementById(tabId).style.display = 'block';
}
</script>
</body>
</html>
