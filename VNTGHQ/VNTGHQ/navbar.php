<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$firstname   = $_SESSION['firstname'] ?? '';
$lastname    = $_SESSION['lastname']  ?? '';
$fullname    = trim("$firstname $lastname");
if (!$fullname) $fullname = $_SESSION['user'] ?? 'Account';
$photoUrl    = $_SESSION['photo_url']       ?? '';
$provider    = $_SESSION['social_provider'] ?? '';
$initials    = strtoupper(
  substr($firstname, 0, 1) . substr($lastname, 0, 1)
) ?: strtoupper(substr($_SESSION['user'] ?? 'U', 0, 1));
?>
<nav class="vntg-navbar navbar navbar-expand-lg sticky-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center gap-2" href="homepage.php">
      <div class="brand-icon"><i class="bi bi-bag-heart-fill"></i></div>
      <div class="brand-text">
        <span class="brand-welcome">Welcome to</span>
        <span class="brand-name">VNTG HQ</span>
        <span class="brand-tagline">Home of Pre-loved Items</span>
      </div>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <!-- ── Search: dropdown is OUTSIDE input-group to avoid overflow clipping ── -->
      <div class="mx-auto nav-search-wrap">
        <div class="input-group nav-search">
          <input type="text" id="navSearchInput" class="form-control"
                 placeholder="Search pre-loved items…" autocomplete="off"
                 oninput="handleNavSearch(this.value)"
                 onkeydown="if(event.key==='Enter')doNavSearch()"
                 onfocus="if(this.value.trim())handleNavSearch(this.value)">
          <button class="btn btn-search" onclick="doNavSearch()"><i class="bi bi-search"></i></button>
        </div>
        <!-- dropdown is a sibling of input-group, not inside it -->
        <div id="searchDropdown" class="search-dropdown"></div>
      </div>

      <ul class="navbar-nav align-items-center gap-1 ms-3">
        <li class="nav-item">
          <a class="nav-link nav-icon-btn <?= $currentPage==='dashboard'?'active':'' ?>" href="dashboard.php">
            <i class="bi bi-speedometer2"></i><span class="nav-label">Dashboard</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link nav-icon-btn <?= $currentPage==='homepage'?'active':'' ?>" href="homepage.php">
            <i class="bi bi-house-fill"></i><span class="nav-label">Home</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link nav-icon-btn <?= $currentPage==='cart'?'active':'' ?>" href="cart.php">
            <i class="bi bi-cart3"></i><span class="nav-label">Cart</span>
            <span class="cart-badge">0</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link nav-icon-btn <?= $currentPage==='favourites'?'active':'' ?>" href="favourites.php">
            <i class="bi bi-heart-fill"></i><span class="nav-label">Favs</span>
            <span class="fav-badge">0</span>
          </a>
        </li>
        <li class="nav-item">
          <button class="nav-link nav-icon-btn notif-trigger" onclick="buildNotifPanel()" type="button">
            <i class="bi bi-bell-fill"></i><span class="nav-label">Alerts</span>
            <span class="notif-badge">0</span>
          </button>
        </li>

        <?php if (isset($_SESSION['user_id'])): ?>
        <li class="nav-item dropdown">
          <a class="nav-link nav-icon-btn dropdown-toggle d-flex flex-column align-items-center gap-0"
             href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <!-- Avatar: photo > initials -->
            <?php if ($photoUrl): ?>
              <img src="<?= htmlspecialchars($photoUrl) ?>" alt="" class="nav-avatar-img"
                   onerror="this.style.display='none';document.getElementById('navFallback').style.display='flex'">
              <span class="nav-avatar-initials" id="navFallback" style="display:none"><?= $initials ?></span>
            <?php else: ?>
              <span class="nav-avatar-initials" id="navInitials"><?= $initials ?></span>
            <?php endif; ?>
            <span class="nav-label"><?= htmlspecialchars(explode(' ', $fullname)[0]) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end vntg-dropdown">
            <li class="dropdown-user-info px-3 py-2 d-flex align-items-center gap-2">
              <?php if ($photoUrl): ?>
              <img src="<?= htmlspecialchars($photoUrl) ?>" alt="" class="dropdown-avatar-img"
                   onerror="this.style.display='none'">
              <?php endif; ?>
              <div>
                <strong class="d-block"><?= htmlspecialchars($fullname) ?></strong>
                <small class="d-block">@<?= htmlspecialchars($_SESSION['user']) ?></small>
                <?php if ($provider): ?>
                <small class="d-block" style="color:var(--brand-accent)"><?= htmlspecialchars($provider) ?></small>
                <?php endif; ?>
              </div>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
            <li><a class="dropdown-item" href="favourites.php"><i class="bi bi-heart me-2"></i>Favourites</a></li>
            <li><a class="dropdown-item" href="about.php"><i class="bi bi-info-circle me-2"></i>About</a></li>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <a class="dropdown-item text-danger" href="#" onclick="event.preventDefault();confirmLogout()">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
              </a>
            </li>
          </ul>
        </li>
        <?php else: ?>
        <li class="nav-item"><a class="btn btn-nav-login ms-2" href="login.php">Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- ── Logout Confirmation Modal ── -->
<div class="pmodal-overlay" id="logoutOverlay" onclick="cancelLogout()"></div>
<div class="pmodal" id="logoutModal" style="max-width:360px">
  <div class="pmodal-body text-center py-3">
    <div style="width:64px;height:64px;background:rgba(239,68,68,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:30px;color:#ef4444">
      <i class="bi bi-box-arrow-right"></i>
    </div>
    <h5 class="fw-700 mb-1" style="font-weight:700">Log Out?</h5>
    <p class="text-muted mb-3" style="font-size:14px">Are you sure you want to log out of VNTG HQ?</p>
    <div class="d-flex gap-2">
      <button class="btn pmodal-btn-cancel flex-fill" onclick="cancelLogout()">Cancel</button>
      <a href="logout.php" class="btn logout-btn flex-fill">Yes, Log Out</a>
    </div>
  </div>
</div>

<script>
/* Logout modal */
function confirmLogout(){
  document.getElementById('logoutOverlay').classList.add('active');
  document.getElementById('logoutModal').classList.add('active');
}
function cancelLogout(){
  document.getElementById('logoutOverlay').classList.remove('active');
  document.getElementById('logoutModal').classList.remove('active');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') cancelLogout(); });

/* Restore locally-uploaded avatar in navbar (from localStorage) */
document.addEventListener('DOMContentLoaded', () => {
  const saved = localStorage.getItem('vntghq_avatar');
  if (!saved) return;
  // Replace nav avatar with saved image
  const initEl = document.getElementById('navInitials') || document.getElementById('navFallback');
  const existImg = document.querySelector('.nav-avatar-img');
  if (existImg) {
    existImg.src = saved;
    existImg.style.display = '';
    const fb = document.getElementById('navFallback');
    if (fb) fb.style.display = 'none';
  } else if (initEl) {
    const img = document.createElement('img');
    img.src = saved;
    img.className = 'nav-avatar-img';
    img.onerror = () => img.style.display = 'none';
    initEl.replaceWith(img);
  }
  // Also replace dropdown avatar
  const ddImg = document.querySelector('.dropdown-avatar-img');
  if (ddImg) ddImg.src = saved;
});
</script>
