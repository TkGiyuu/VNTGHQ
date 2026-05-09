<?php
session_start();
require 'db.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

// Already logged in → dashboard
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

/*
 * FLOW:
 *  step=1  → user enters their username
 *  step=2  → user enters the 6-digit reset code
 *  step=3  → user sets a new password
 *  step=done → success screen
 *
 * Because there is no real database / mail server in this demo,
 * the reset code is stored in $_SESSION and displayed on the page
 * in a styled "email preview" box so the flow is fully exercisable.
 */

$step    = $_SESSION['fp_step']    ?? 1;
$error   = '';
$success = '';

// ── STEP 1 – submit username ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fp_step1'])) {
    $username = trim($_POST['username'] ?? '');
    if (empty($username)) {
        $error = 'Please enter your username.';
    } else {
        // Check if username exists in MySQL
        try {
            $chk = db()->prepare("SELECT id FROM users WHERE username=? AND provider='local' LIMIT 1");
            $chk->execute([$username]);
            $found = $chk->fetch();
        } catch(Exception $e){ $found = false; }

        if (!$found) {
            // Don't reveal whether account exists — generic message
            $error = 'If that username exists, a reset code has been generated.';
        } else {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['fp_username'] = $username;
            $_SESSION['fp_uid']      = $found['id'];
            $_SESSION['fp_code']     = $code;
            $_SESSION['fp_expires']  = time() + 600;
            $_SESSION['fp_step']     = 2;
            $step = 2;
        }
    }
}

// ── STEP 2 – submit reset code ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fp_step2'])) {
    $entered = trim($_POST['reset_code'] ?? '');
    if (empty($entered)) {
        $error = 'Please enter the reset code.';
        $step  = 2;
    } elseif (time() > ($_SESSION['fp_expires'] ?? 0)) {
        $error = 'This code has expired. Please start again.';
        unset($_SESSION['fp_step'], $_SESSION['fp_code'], $_SESSION['fp_username'], $_SESSION['fp_expires']);
        $step = 1;
    } elseif ($entered !== ($_SESSION['fp_code'] ?? '')) {
        $error = 'Incorrect code. Please try again.';
        $step  = 2;
    } else {
        $_SESSION['fp_step'] = 3;
        $step = 3;
    }
}

// ── STEP 3 – set new password ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fp_step3'])) {
    $newpass  = $_POST['new_password']     ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    if (empty($newpass) || empty($confirm)) {
        $error = 'Both fields are required.';
        $step  = 3;
    } elseif (strlen($newpass) < 6) {
        $error = 'Password must be at least 6 characters.';
        $step  = 3;
    } elseif ($newpass !== $confirm) {
        $error = 'Passwords do not match.';
        $step  = 3;
    } else {
        $hash = password_hash($newpass, PASSWORD_DEFAULT);
        try {
            $uid_to_reset = $_SESSION['fp_uid'] ?? 0;
            if ($uid_to_reset) {
                db()->prepare("UPDATE users SET password_hash=?,updated_at=NOW() WHERE id=?")
                   ->execute([$hash, $uid_to_reset]);
            }
        } catch(Exception $e) {}
        // Clean up reset tokens
        unset($_SESSION['fp_step'], $_SESSION['fp_code'], $_SESSION['fp_expires'], $_SESSION['fp_uid']);
        $step = 'done';
    }
}

// Restart link
if (isset($_GET['restart'])) {
    unset($_SESSION['fp_step'], $_SESSION['fp_code'], $_SESSION['fp_username'], $_SESSION['fp_expires']);
    header('Location: forgot_password.php');
    exit;
}

// Read username for display
$fpUser = $_SESSION['fp_username'] ?? '';
$fpCode = $_SESSION['fp_code']     ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
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
    <div class="auth-panel-content">

      <!-- Step progress visual -->
      <div class="fp-steps-visual mb-5">
        <div class="fp-step-dot <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'done' : '' ?>">
          <?= $step > 1 ? '<i class="bi bi-check-lg"></i>' : '1' ?>
        </div>
        <div class="fp-step-line <?= $step >= 2 ? 'active' : '' ?>"></div>
        <div class="fp-step-dot <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'done' : '' ?>">
          <?= $step > 2 ? '<i class="bi bi-check-lg"></i>' : '2' ?>
        </div>
        <div class="fp-step-line <?= $step >= 3 ? 'active' : '' ?>"></div>
        <div class="fp-step-dot <?= ($step === 3 || $step === 'done') ? 'active' : '' ?> <?= $step === 'done' ? 'done' : '' ?>">
          <?= $step === 'done' ? '<i class="bi bi-check-lg"></i>' : '3' ?>
        </div>
      </div>

      <h3 class="auth-panel-headline">
        <?php if ($step == 1): ?>Reset<br>Your<br>Password.
        <?php elseif ($step == 2): ?>Check<br>Your<br>Code.
        <?php elseif ($step == 3): ?>Set New<br>Password.
        <?php else: ?>All<br>Done!
        <?php endif; ?>
      </h3>
      <p class="auth-panel-sub mt-3">
        <?php if ($step == 1): ?>Enter your username and we'll send you a reset code.
        <?php elseif ($step == 2): ?>A 6-digit code was sent to your registered contact. It expires in 10 minutes.
        <?php elseif ($step == 3): ?>Choose a strong new password for your account.
        <?php else: ?>Your password has been successfully reset. You can now log in.
        <?php endif; ?>
      </p>
    </div>
    <p class="auth-panel-footer">Remembered it? <a href="login.php">Back to Login</a></p>
  </div>

  <!-- ── RIGHT PANEL ── -->
  <div class="auth-panel-right d-flex flex-column justify-content-center align-items-center">
    <div class="auth-form-box">

      <!-- Back link -->
      <?php if ($step !== 'done'): ?>
      <a href="<?= $step == 1 ? 'login.php' : 'forgot_password.php?restart=1' ?>" class="fp-back-link mb-4 d-inline-flex align-items-center gap-2">
        <i class="bi bi-arrow-left"></i> <?= $step == 1 ? 'Back to Login' : 'Start Over' ?>
      </a>
      <?php endif; ?>

      <?php if ($error): ?>
      <div class="alert alert-danger py-2 mb-3">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <!-- ════════════════════════════════════
           STEP 1 – Enter Username
           ════════════════════════════════════ -->
      <?php if ($step == 1): ?>
      <div class="auth-form-header mb-4">
        <div class="fp-step-badge mb-3">Step 1 of 3</div>
        <h2 class="auth-form-title">Forgot Password</h2>
        <p class="auth-form-sub">Enter your username to receive a reset code.</p>
      </div>
      <form method="POST" novalidate>
        <input type="hidden" name="fp_step1" value="1">
        <div class="mb-4 auth-input-group">
          <label class="form-label auth-label">Username</label>
          <div class="input-with-icon">
            <i class="bi bi-person input-icon"></i>
            <input type="text" name="username" class="form-control auth-input"
                   placeholder="Your username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
          </div>
        </div>
        <button type="submit" class="btn auth-btn w-100 mb-3">
          <i class="bi bi-send me-2"></i>Send Reset Code
        </button>
        <p class="auth-switch-link text-center">
          Remembered it? <a href="login.php">Log in</a>
        </p>
      </form>

      <!-- ════════════════════════════════════
           STEP 2 – Enter Reset Code
           ════════════════════════════════════ -->
      <?php elseif ($step == 2): ?>
      <div class="auth-form-header mb-4">
        <div class="fp-step-badge mb-3">Step 2 of 3</div>
        <h2 class="auth-form-title">Enter Code</h2>
        <p class="auth-form-sub">We generated a reset code for <strong><?= htmlspecialchars($fpUser) ?></strong>.</p>
      </div>

      <!-- ── Demo Email Preview Box ── -->
      <div class="fp-email-preview mb-4">
        <div class="fp-email-header">
          <i class="bi bi-envelope-fill me-2"></i>
          <span>Password Reset Email Preview</span>
          <span class="fp-email-demo-badge">DEMO</span>
        </div>
        <div class="fp-email-body">
          <p class="fp-email-to">To: <strong><?= htmlspecialchars($fpUser) ?>@vntghq.demo</strong></p>
          <p class="fp-email-subject">Subject: <strong>Your VNTG HQ Password Reset Code</strong></p>
          <hr class="my-2">
          <p class="mb-1" style="font-size:13px;">Hi <strong><?= htmlspecialchars($fpUser) ?></strong>,</p>
          <p style="font-size:13px; color:#475569;">Use the code below to reset your password. It expires in <strong>10 minutes</strong>.</p>
          <div class="fp-code-display">
            <?php foreach (str_split($fpCode) as $digit): ?>
            <span><?= $digit ?></span>
            <?php endforeach; ?>
          </div>
          <p style="font-size:11px; color:#94a3b8; margin:0;">If you didn't request this, ignore this email.</p>
        </div>
      </div>

      <!-- Timer countdown -->
      <div class="fp-timer mb-3" id="fp-timer">
        <i class="bi bi-clock me-1"></i> Code expires in <strong id="fp-countdown">10:00</strong>
      </div>

      <form method="POST" novalidate>
        <input type="hidden" name="fp_step2" value="1">
        <div class="mb-4">
          <label class="form-label auth-label">6-Digit Reset Code</label>
          <div class="fp-otp-inputs d-flex gap-2 justify-content-between">
            <?php for ($i = 0; $i < 6; $i++): ?>
            <input type="text" class="fp-otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]"
                   data-index="<?= $i ?>" autocomplete="off">
            <?php endfor; ?>
          </div>
          <!-- hidden field that collects the full code -->
          <input type="hidden" name="reset_code" id="fp-code-hidden">
        </div>
        <button type="submit" class="btn auth-btn w-100 mb-3" id="fp-verify-btn" disabled>
          <i class="bi bi-shield-check me-2"></i>Verify Code
        </button>
        <p class="auth-switch-link text-center">
          Wrong account? <a href="forgot_password.php?restart=1">Start over</a>
        </p>
      </form>

      <!-- ════════════════════════════════════
           STEP 3 – Set New Password
           ════════════════════════════════════ -->
      <?php elseif ($step == 3): ?>
      <div class="auth-form-header mb-4">
        <div class="fp-step-badge mb-3">Step 3 of 3</div>
        <h2 class="auth-form-title">New Password</h2>
        <p class="auth-form-sub">Choose a strong new password for <strong><?= htmlspecialchars($fpUser) ?></strong>.</p>
      </div>
      <form method="POST" novalidate>
        <input type="hidden" name="fp_step3" value="1">
        <div class="mb-3 auth-input-group">
          <label class="form-label auth-label">New Password</label>
          <div class="input-with-icon">
            <i class="bi bi-lock input-icon"></i>
            <input type="password" name="new_password" id="new_password"
                   class="form-control auth-input" placeholder="At least 6 characters" required autofocus>
            <button type="button" class="btn-pw-toggle" onclick="togglePassword(this)" tabindex="-1">
              <i class="bi bi-eye"></i>
            </button>
          </div>
          <!-- Strength bar -->
          <div class="pw-strength-wrap mt-2">
            <div class="pw-strength-bar" id="pw-strength-bar"></div>
          </div>
          <small class="pw-strength-label" id="pw-strength-label"></small>
        </div>
        <div class="mb-4 auth-input-group">
          <label class="form-label auth-label">Confirm New Password</label>
          <div class="input-with-icon">
            <i class="bi bi-shield-lock input-icon"></i>
            <input type="password" name="confirm_password" id="confirm_password"
                   class="form-control auth-input" placeholder="Repeat your password" required>
            <button type="button" class="btn-pw-toggle" onclick="togglePassword(this)" tabindex="-1">
              <i class="bi bi-eye"></i>
            </button>
          </div>
          <small class="fp-match-msg mt-1 d-block" id="fp-match-msg"></small>
        </div>
        <button type="submit" class="btn auth-btn w-100 mb-3">
          <i class="bi bi-check-circle me-2"></i>Reset Password
        </button>
      </form>

      <!-- ════════════════════════════════════
           DONE
           ════════════════════════════════════ -->
      <?php else: ?>
      <div class="fp-success-box text-center">
        <div class="fp-success-icon mb-4">
          <i class="bi bi-check-circle-fill"></i>
        </div>
        <h2 class="auth-form-title mb-2">Password Reset!</h2>
        <p class="auth-form-sub mb-4">
          Your password for <strong><?= htmlspecialchars($fpUser) ?></strong> has been successfully updated.
          You can now log in with your new password.
        </p>
        <a href="login.php" class="btn auth-btn w-100 mb-3">
          <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
        </a>
      </div>
      <?php endif; ?>

    </div>
  </div><!-- /auth-panel-right -->
</div><!-- /auth-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
<script>
/* ── OTP box navigation ── */
const otpBoxes = document.querySelectorAll('.fp-otp-box');
const hiddenCode = document.getElementById('fp-code-hidden');
const verifyBtn  = document.getElementById('fp-verify-btn');

otpBoxes.forEach((box, idx) => {
  box.addEventListener('input', e => {
    // Allow only digits
    box.value = box.value.replace(/\D/g,'').slice(-1);
    if (box.value && idx < otpBoxes.length - 1) otpBoxes[idx + 1].focus();
    syncCode();
  });
  box.addEventListener('keydown', e => {
    if (e.key === 'Backspace' && !box.value && idx > 0) {
      otpBoxes[idx - 1].focus();
    }
    // Allow paste
    if (e.key === 'v' && (e.ctrlKey || e.metaKey)) return;
  });
  box.addEventListener('paste', e => {
    e.preventDefault();
    const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'');
    pasted.split('').slice(0,6).forEach((ch, i) => {
      if (otpBoxes[i]) otpBoxes[i].value = ch;
    });
    syncCode();
    otpBoxes[Math.min(pasted.length, 5)].focus();
  });
});

function syncCode() {
  const code = Array.from(otpBoxes).map(b => b.value).join('');
  if (hiddenCode) hiddenCode.value = code;
  if (verifyBtn)  verifyBtn.disabled = code.length < 6;
}

/* ── Countdown timer ── */
const timerEl = document.getElementById('fp-countdown');
if (timerEl) {
  let seconds = 600;
  const tick = setInterval(() => {
    seconds--;
    if (seconds <= 0) {
      clearInterval(tick);
      timerEl.closest('.fp-timer').innerHTML =
        '<i class="bi bi-x-circle me-1 text-danger"></i> Code expired. <a href="forgot_password.php?restart=1">Request a new one</a>.';
      if (verifyBtn) verifyBtn.disabled = true;
      return;
    }
    const m = String(Math.floor(seconds / 60)).padStart(2,'0');
    const s = String(seconds % 60).padStart(2,'0');
    timerEl.textContent = `${m}:${s}`;
    // Turn red in last 60s
    if (seconds <= 60) timerEl.closest('.fp-timer').classList.add('expiring');
  }, 1000);
}

/* ── Password strength meter ── */
const pwInput      = document.getElementById('new_password');
const confirmInput = document.getElementById('confirm_password');
const strengthBar  = document.getElementById('pw-strength-bar');
const strengthLbl  = document.getElementById('pw-strength-label');
const matchMsg     = document.getElementById('fp-match-msg');

function calcStrength(pw) {
  let score = 0;
  if (pw.length >= 6)  score++;
  if (pw.length >= 10) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  return score;
}

if (pwInput) {
  pwInput.addEventListener('input', () => {
    const pw = pwInput.value;
    const score = calcStrength(pw);
    const levels = ['', 'Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];
    const colors = ['', '#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a'];
    const pct    = ['0%', '20%', '40%', '60%', '80%', '100%'];
    if (strengthBar) {
      strengthBar.style.width     = pw ? pct[score] : '0%';
      strengthBar.style.background = colors[score] || '#e2e8f0';
    }
    if (strengthLbl) {
      strengthLbl.textContent  = pw ? levels[score] : '';
      strengthLbl.style.color  = colors[score] || '';
    }
  });
}

if (confirmInput) {
  confirmInput.addEventListener('input', () => {
    if (!matchMsg) return;
    if (!confirmInput.value) { matchMsg.textContent = ''; return; }
    if (confirmInput.value === (pwInput?.value || '')) {
      matchMsg.textContent = '✓ Passwords match';
      matchMsg.style.color = '#22c55e';
    } else {
      matchMsg.textContent = '✗ Passwords do not match';
      matchMsg.style.color = '#ef4444';
    }
  });
}
</script>
</body>
</html>
