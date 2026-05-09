<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
require 'db.php';

// Already logged in — redirect to correct destination
if (isset($_SESSION['user_id']))  { header('Location: dashboard.php');       exit; }
if (isset($_SESSION['admin_id'])) { header('Location: admin/index.php');     exit; }

$error    = '';
$mode     = $_POST['mode'] ?? $_GET['mode'] ?? 'choose'; // choose | user | admin

// ── USER LOGIN ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'user') {
    $username = trim($_POST['username'] ?? '');
    $pass     = $_POST['password']      ?? '';
    if (empty($username) || empty($pass)) {
        $error = 'Please enter your username and password.';
    } else {
        try {
            $stmt = db()->prepare("SELECT * FROM users WHERE username=? AND provider='local' LIMIT 1");
            $stmt->execute([$username]);
            $row  = $stmt->fetch();
            if ($row && password_verify($pass, $row['password_hash'])) {
                // Reject admins trying to log in via user form
                if ($row['is_admin']) {
                    $error = 'Admin accounts must use the Admin login.';
                } else {
                    $_SESSION['user_id']    = $row['id'];
                    $_SESSION['user']       = $row['username'];
                    $_SESSION['firstname']  = $row['firstname'];
                    $_SESSION['lastname']   = $row['lastname'];
                    $_SESSION['email']      = $row['email'];
                    $_SESSION['phone']      = $row['phone'];
                    $_SESSION['photo_url']  = $row['photo_url'] ?? '';
                    $_SESSION['provider']   = 'local';
                    $_SESSION['login_time'] = time();
                    db()->prepare('UPDATE users SET updated_at=NOW() WHERE id=?')->execute([$row['id']]);
                    header('Location: dashboard.php'); exit;
                }
            } else {
                $error = 'Incorrect username or password.';
            }
        } catch (PDOException $e) { $error = 'Login error. Please try again.'; }
    }
}

// ── ADMIN LOGIN ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'admin') {
    $username = trim($_POST['username'] ?? '');
    $pass     = $_POST['password']      ?? '';
    if (empty($username) || empty($pass)) {
        $error = 'Please enter your admin credentials.';
    } else {
        try {
            $stmt = db()->prepare("SELECT * FROM users WHERE username=? AND is_admin=1 AND provider='local' LIMIT 1");
            $stmt->execute([$username]);
            $row  = $stmt->fetch();
            if ($row && password_verify($pass, $row['password_hash'])) {
                $_SESSION['admin_id']         = $row['id'];
                $_SESSION['admin_username']   = $row['username'];
                $_SESSION['admin_firstname']  = $row['firstname'];
                $_SESSION['admin_lastname']   = $row['lastname'];
                $_SESSION['admin_email']      = $row['email'];
                $_SESSION['admin_login_time'] = time();
                header('Location: admin/index.php'); exit;
            } else {
                $error = 'Invalid admin credentials.';
            }
        } catch (PDOException $e) { $error = 'Login error. Please try again.'; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    /* ── Role selector cards ── */
    .role-selector { display: flex; gap: 16px; margin-bottom: 8px; }

    .role-card {
      flex: 1;
      border: 2.5px solid var(--gray-200);
      border-radius: 14px;
      padding: 22px 16px;
      text-align: center;
      cursor: pointer;
      transition: all 0.25s cubic-bezier(.4,0,.2,1);
      background: white;
      position: relative;
      overflow: hidden;
      user-select: none;
    }
    .role-card::before {
      content: '';
      position: absolute; inset: 0;
      opacity: 0;
      transition: opacity 0.25s;
    }
    .role-card.role-user::before  { background: linear-gradient(135deg,rgba(14,165,233,.06),rgba(56,189,248,.04)); }
    .role-card.role-admin::before { background: linear-gradient(135deg,rgba(139,92,246,.06),rgba(109,40,217,.04)); }

    .role-card:hover { border-color: var(--brand-blue); transform: translateY(-3px); box-shadow: 0 8px 24px rgba(14,165,233,.14); }
    .role-card:hover::before { opacity: 1; }

    .role-card.selected.role-user  { border-color: var(--brand-blue); background: var(--brand-light); box-shadow: 0 0 0 4px rgba(14,165,233,.12); }
    .role-card.selected.role-admin { border-color: #8b5cf6; background: rgba(139,92,246,.06); box-shadow: 0 0 0 4px rgba(139,92,246,.12); }
    .role-card.selected { transform: translateY(-3px); }

    .role-icon-wrap {
      width: 54px; height: 54px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 24px; margin: 0 auto 12px;
      transition: all 0.25s;
    }
    .role-user  .role-icon-wrap { background: rgba(14,165,233,.12); color: var(--brand-blue); }
    .role-admin .role-icon-wrap { background: rgba(139,92,246,.12); color: #8b5cf6; }
    .role-card.selected .role-icon-wrap { transform: scale(1.1); }

    .role-title { font-size: 14px; font-weight: 700; color: var(--gray-800); margin: 0 0 2px; }
    .role-sub   { font-size: 11px; color: var(--gray-400); margin: 0; }

    .role-check {
      position: absolute; top: 10px; right: 10px;
      width: 20px; height: 20px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 800;
      opacity: 0; transition: opacity 0.2s, transform 0.2s;
      transform: scale(0.6);
    }
    .role-user  .role-check { background: var(--brand-blue); color: white; }
    .role-admin .role-check { background: #8b5cf6; color: white; }
    .role-card.selected .role-check { opacity: 1; transform: scale(1); }

    /* ── Form panels ── */
    .login-panels { position: relative; overflow: hidden; }
    .login-panel {
      display: none;
      animation: slideUp 0.3s cubic-bezier(.4,0,.2,1) both;
    }
    .login-panel.visible { display: block; }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Admin badge on form ── */
    .admin-form-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(139,92,246,.1); color: #7c3aed;
      font-size: 11px; font-weight: 800;
      padding: 4px 12px; border-radius: 50px;
      text-transform: uppercase; letter-spacing: 1px;
      margin-bottom: 8px;
    }
    .auth-btn.admin-btn {
      background: #7c3aed;
    }
    .auth-btn.admin-btn:hover { background: #5b21b6; box-shadow: 0 6px 20px rgba(124,58,237,.3); }
    .auth-divider-note {
      text-align: center; font-size: 12px; color: var(--gray-400); margin-top: 6px;
    }
    .auth-divider-note a { color: var(--brand-blue); font-weight: 600; text-decoration: none; }
  </style>
</head>
<body class="auth-body">
<div class="auth-layout">

  <!-- ── LEFT PANEL ── -->
  <div class="auth-panel-left d-none d-lg-flex flex-column justify-content-between">
    <div class="auth-brand">
      <i class="bi bi-bag-heart-fill auth-brand-icon"></i>
      <h2 class="auth-brand-name">VNTG HQ</h2>
      <p class="auth-brand-tag">Home of Pre-loved Items</p>
    </div>
    <div class="auth-panel-content" id="leftPanelContent">
      <h3 class="auth-panel-headline" id="leftHeadline">Welcome<br>Back.</h3>
      <p class="auth-panel-sub" id="leftSub">Choose how you'd like to sign in to continue.</p>
      <div class="auth-features mt-4" id="leftFeatures">
        <div class="auth-feature-item"><i class="bi bi-person-circle me-2"></i>User — Shop pre-loved items</div>
        <div class="auth-feature-item"><i class="bi bi-shield-fill-check me-2"></i>Admin — Manage the platform</div>
      </div>
    </div>
    <p class="auth-panel-footer">Don't have an account? <a href="register.php">Register</a></p>
  </div>

  <!-- ── RIGHT PANEL ── -->
  <div class="auth-panel-right d-flex flex-column justify-content-center align-items-center">
    <div class="auth-form-box">

      <a href="register.php" class="fp-back-link mb-4 d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> Back to Register
      </a>

      <div class="auth-form-header mb-4">
        <h2 class="auth-form-title">Login</h2>
        <p class="auth-form-sub">Select your account type to continue</p>
      </div>

      <!-- ── ROLE SELECTOR ── -->
      <div class="role-selector mb-4">
        <div class="role-card role-user <?= ($mode==='user'||$mode==='choose') ? 'selected':'' ?>"
             id="roleUser" onclick="selectRole('user')">
          <div class="role-check"><i class="bi bi-check-lg"></i></div>
          <div class="role-icon-wrap"><i class="bi bi-person-fill"></i></div>
          <p class="role-title">User</p>
          <p class="role-sub">Shop & browse items</p>
        </div>
        <div class="role-card role-admin <?= $mode==='admin' ? 'selected':'' ?>"
             id="roleAdmin" onclick="selectRole('admin')">
          <div class="role-check"><i class="bi bi-check-lg"></i></div>
          <div class="role-icon-wrap"><i class="bi bi-shield-fill-check"></i></div>
          <p class="role-title">Admin</p>
          <p class="role-sub">Manage the platform</p>
        </div>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-danger py-2 mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <!-- ── PANELS ── -->
      <div class="login-panels">

        <!-- USER FORM -->
        <div class="login-panel <?= ($mode==='user'||$mode==='choose') ? 'visible':'' ?>" id="panelUser">
          <!-- Social login -->
          <div class="social-login-row mb-3">
            <button type="button" class="social-btn social-fb" onclick="signInWithFacebook()">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
              Continue with Facebook
            </button>
            <button type="button" class="social-btn social-google" onclick="signInWithGoogle()">
              <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
              Continue with Google
            </button>
          </div>
          <div class="auth-divider mb-3"><span>or sign in with username</span></div>

          <form method="POST" novalidate>
            <input type="hidden" name="mode" value="user">
            <div class="mb-3">
              <label class="form-label auth-label">Username</label>
              <div class="input-with-icon">
                <i class="bi bi-person input-icon"></i>
                <input type="text" name="username" class="form-control auth-input"
                       placeholder="Your username"
                       value="<?= ($mode==='user') ? htmlspecialchars($_POST['username'] ?? '') : '' ?>"
                       id="userUsernameInput" autocomplete="username">
              </div>
            </div>
            <div class="mb-2">
              <label class="form-label auth-label">Password</label>
              <div class="input-with-icon">
                <i class="bi bi-lock input-icon"></i>
                <input type="password" name="password" class="form-control auth-input"
                       placeholder="Your password" id="userPasswordInput" autocomplete="current-password">
                <button type="button" class="btn-pw-toggle" onclick="togglePassword(this)" tabindex="-1">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
            <div class="text-end mb-4">
              <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
            </div>
            <button type="submit" class="btn auth-btn w-100 mb-3">
              <i class="bi bi-person me-2"></i>Login as User
            </button>
            <p class="auth-switch-link text-center">
              Don't have an account? <a href="register.php">Register here</a>
            </p>
          </form>
        </div>

        <!-- ADMIN FORM -->
        <div class="login-panel <?= $mode==='admin' ? 'visible':'' ?>" id="panelAdmin">
          <div class="admin-form-badge">
            <i class="bi bi-shield-fill-check"></i> Administrator Access
          </div>
          <p class="auth-form-sub mb-3">Sign in with your administrator credentials.</p>

          <form method="POST" novalidate>
            <input type="hidden" name="mode" value="admin">
            <div class="mb-3">
              <label class="form-label auth-label">Admin Username</label>
              <div class="input-with-icon">
                <i class="bi bi-shield input-icon"></i>
                <input type="text" name="username" class="form-control auth-input"
                       placeholder="Admin username"
                       value="<?= ($mode==='admin') ? htmlspecialchars($_POST['username'] ?? '') : '' ?>"
                       id="adminUsernameInput" autocomplete="username">
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label auth-label">Password</label>
              <div class="input-with-icon">
                <i class="bi bi-lock input-icon"></i>
                <input type="password" name="password" class="form-control auth-input"
                       placeholder="Admin password" id="adminPasswordInput" autocomplete="current-password">
                <button type="button" class="btn-pw-toggle" onclick="togglePassword(this)" tabindex="-1">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
            <button type="submit" class="btn auth-btn admin-btn w-100 mb-3">
              <i class="bi bi-shield-lock me-2"></i>Login as Admin
            </button>
            <p class="auth-divider-note">
              Not an admin? <a href="#" onclick="selectRole('user');return false">Switch to User Login</a>
            </p>
          </form>
        </div>

      </div><!-- /login-panels -->
    </div><!-- /auth-form-box -->
  </div><!-- /auth-panel-right -->

</div><!-- /auth-layout -->

<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
<script src="firebase-config.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
<script>
const leftHeadlines = {
  user:  { title: 'Welcome<br>Back.',         sub: 'Continue your journey through unique pre-loved finds.' },
  admin: { title: 'Admin<br>Panel.',           sub: 'Manage users, orders, and the VNTG HQ platform.' },
};

function selectRole(role) {
  // Update role cards
  document.getElementById('roleUser').classList.toggle('selected',  role === 'user');
  document.getElementById('roleAdmin').classList.toggle('selected', role === 'admin');

  // Show correct form panel
  document.querySelectorAll('.login-panel').forEach(p => p.classList.remove('visible'));
  const panel = document.getElementById(role === 'user' ? 'panelUser' : 'panelAdmin');
  panel.classList.add('visible');

  // Update left panel text
  const lh = leftHeadlines[role];
  if (lh) {
    document.getElementById('leftHeadline').innerHTML = lh.title;
    document.getElementById('leftSub').textContent    = lh.sub;
    document.getElementById('leftFeatures').style.display = 'none';
  }

  // Auto-focus username field
  setTimeout(() => {
    const inp = panel.querySelector('input[type="text"]');
    if (inp) inp.focus();
  }, 50);
}

// On page load, initialise to whichever mode is active
document.addEventListener('DOMContentLoaded', () => {
  const activeMode = <?= json_encode($mode === 'admin' ? 'admin' : 'user') ?>;
  selectRole(activeMode);
});
</script>
</body>
</html>
