<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
require 'db.php';

if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname']  ?? '');
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $pass      = $_POST['password']       ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (empty($firstname)||empty($lastname)||empty($username)||empty($email)||empty($pass)||empty($confirm)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username may only contain letters, numbers, and underscores.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        try {
            $pdo = db();
            // Check duplicate username or email
            $chk = $pdo->prepare('SELECT id FROM users WHERE username=? OR email=? LIMIT 1');
            $chk->execute([$username, $email]);
            if ($chk->fetch()) {
                $error = 'Username or email already in use. Please choose another.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $ins  = $pdo->prepare(
                    'INSERT INTO users (username,firstname,lastname,email,password_hash,provider)
                     VALUES (?,?,?,?,?,\'local\')'
                );
                $ins->execute([$username, $firstname, $lastname, $email, $hash]);
                $success = 'Account created! <a href="login.php" class="alert-link fw-bold">Login here</a>.';
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again. (' . $e->getMessage() . ')';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
<div class="auth-layout">
  <div class="auth-panel-left d-none d-lg-flex flex-column justify-content-between">
    <div class="auth-brand">
      <i class="bi bi-bag-heart-fill auth-brand-icon"></i>
      <h2 class="auth-brand-name">VNTG HQ</h2>
      <p class="auth-brand-tag">Home of Pre-loved Items</p>
    </div>
    <div class="auth-panel-content">
      <h3 class="auth-panel-headline">Join the<br>Pre-loved<br>Community.</h3>
      <p class="auth-panel-sub">Buy and sell vintage, unique, and gently used items in a trusted space.</p>
      <div class="auth-features mt-4">
        <div class="auth-feature-item"><i class="bi bi-check-circle-fill me-2"></i>Free to join</div>
        <div class="auth-feature-item"><i class="bi bi-check-circle-fill me-2"></i>Secure transactions</div>
        <div class="auth-feature-item"><i class="bi bi-check-circle-fill me-2"></i>Unique finds daily</div>
      </div>
    </div>
    <p class="auth-panel-footer">Already have an account? <a href="login.php">Log in</a></p>
  </div>
  <div class="auth-panel-right d-flex flex-column justify-content-center align-items-center">
    <div class="auth-form-box">
      <a href="login.php" class="fp-back-link mb-4 d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> Back to Login
      </a>
      <div class="auth-form-header mb-3">
        <h2 class="auth-form-title">Registration</h2>
        <p class="auth-form-sub">Create your VNTG HQ account</p>
      </div>

      <!-- Social Buttons -->
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
      <div class="auth-divider mb-3"><span>or register with email</span></div>

      <?php if ($error): ?>
      <div class="alert alert-danger py-2 mb-3"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
      <div class="alert alert-success py-2 mb-3"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="row g-2 mb-2">
          <div class="col-6">
            <label class="form-label auth-label">First Name</label>
            <div class="input-with-icon">
              <i class="bi bi-person input-icon"></i>
              <input type="text" name="firstname" class="form-control auth-input"
                     placeholder="First name" value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>" required>
            </div>
          </div>
          <div class="col-6">
            <label class="form-label auth-label">Last Name</label>
            <div class="input-with-icon">
              <i class="bi bi-person input-icon"></i>
              <input type="text" name="lastname" class="form-control auth-input"
                     placeholder="Last name" value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>" required>
            </div>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label auth-label">Username</label>
          <div class="input-with-icon">
            <i class="bi bi-at input-icon"></i>
            <input type="text" name="username" class="form-control auth-input"
                   placeholder="Choose a username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label auth-label">Email Address</label>
          <div class="input-with-icon">
            <i class="bi bi-envelope input-icon"></i>
            <input type="email" name="email" class="form-control auth-input"
                   placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label auth-label">Password</label>
          <div class="input-with-icon">
            <i class="bi bi-lock input-icon"></i>
            <input type="password" name="password" class="form-control auth-input" placeholder="At least 6 characters" required>
            <button type="button" class="btn-pw-toggle" onclick="togglePassword(this)" tabindex="-1"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label auth-label">Confirm Password</label>
          <div class="input-with-icon">
            <i class="bi bi-shield-lock input-icon"></i>
            <input type="password" name="confirm_password" class="form-control auth-input" placeholder="Repeat your password" required>
            <button type="button" class="btn-pw-toggle" onclick="togglePassword(this)" tabindex="-1"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        <button type="submit" class="btn auth-btn w-100 mb-2">Sign Up</button>
        <p class="auth-switch-link text-center mb-0">Already have an account? <a href="login.php">Log in</a></p>
      </form>
    </div>
  </div>
</div>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
<script src="firebase-config.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
</body>
</html>
