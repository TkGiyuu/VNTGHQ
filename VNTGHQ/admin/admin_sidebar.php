<?php $cur = basename($_SERVER['PHP_SELF'],'.php'); ?>
<aside class="admin-sidebar" id="adminSidebar">
  <nav class="admin-nav">
    <p class="admin-nav-section">Main</p>
    <a href="index.php" class="admin-nav-link <?= $cur==='index'?'active':'' ?>">
      <i class="bi bi-speedometer2"></i><span>Dashboard</span>
    </a>
    <p class="admin-nav-section mt-3">Management</p>
    <a href="users.php" class="admin-nav-link <?= $cur==='users'||$cur==='user_view'?'active':'' ?>">
      <i class="bi bi-people-fill"></i><span>Users</span>
    </a>
    <a href="orders.php" class="admin-nav-link <?= $cur==='orders'?'active':'' ?>">
      <i class="bi bi-receipt"></i><span>Orders</span>
    </a>
    <p class="admin-nav-section mt-3">Account</p>
    <a href="../homepage.php" target="_blank" class="admin-nav-link">
      <i class="bi bi-box-arrow-up-right"></i><span>View Site</span>
    </a>
    <a href="#" class="admin-nav-link admin-nav-logout" onclick="confirmAdminLogout(event)">
      <i class="bi bi-box-arrow-right"></i><span>Logout</span>
    </a>
  </nav>
</aside>

<!-- Admin Logout Confirmation Modal -->
<div class="pmodal-overlay" id="adminLogoutOverlay" onclick="cancelAdminLogout()"
     style="position:fixed;inset:0;background:rgba(0,0,0,0.55);backdrop-filter:blur(4px);z-index:1200;opacity:0;pointer-events:none;transition:opacity 0.25s"></div>
<div id="adminLogoutModal"
     style="position:fixed;top:50%;left:50%;transform:translate(-50%,-48%) scale(0.97);
            width:90%;max-width:360px;background:white;border-radius:20px;
            box-shadow:0 24px 64px rgba(0,0,0,0.22);z-index:1300;opacity:0;pointer-events:none;
            transition:opacity 0.25s,transform 0.25s;padding:32px 28px;text-align:center">
  <div style="width:64px;height:64px;background:rgba(239,68,68,0.1);border-radius:50%;
              display:flex;align-items:center;justify-content:center;margin:0 auto 16px;
              font-size:28px;color:#ef4444">
    <i class="bi bi-box-arrow-right"></i>
  </div>
  <h5 style="font-weight:700;color:#1e293b;margin-bottom:6px">Log Out?</h5>
  <p style="color:#94a3b8;font-size:14px;margin-bottom:24px">
    Are you sure you want to log out of the Admin Panel?
  </p>
  <div style="display:flex;gap:10px">
    <button onclick="cancelAdminLogout()"
            style="flex:1;background:#f1f5f9;color:#475569;border:none;border-radius:8px;
                   padding:11px;font-weight:600;font-size:14px;cursor:pointer">
      Cancel
    </button>
    <a href="logout.php"
       style="flex:1;background:#ef4444;color:white;border:none;border-radius:8px;
              padding:11px;font-weight:700;font-size:14px;text-decoration:none;
              display:flex;align-items:center;justify-content:center">
      Yes, Log Out
    </a>
  </div>
</div>

<script>
function toggleSidebar(){
  document.getElementById('adminSidebar').classList.toggle('open');
  document.getElementById('sidebarBackdrop').classList.toggle('show');
}
function confirmAdminLogout(e) {
  e.preventDefault();
  const overlay = document.getElementById('adminLogoutOverlay');
  const modal   = document.getElementById('adminLogoutModal');
  overlay.style.opacity = '1'; overlay.style.pointerEvents = 'auto';
  modal.style.opacity   = '1'; modal.style.pointerEvents   = 'auto';
  modal.style.transform = 'translate(-50%,-50%) scale(1)';
}
function cancelAdminLogout() {
  const overlay = document.getElementById('adminLogoutOverlay');
  const modal   = document.getElementById('adminLogoutModal');
  overlay.style.opacity = '0'; overlay.style.pointerEvents = 'none';
  modal.style.opacity   = '0'; modal.style.pointerEvents   = 'none';
  modal.style.transform = 'translate(-50%,-48%) scale(0.97)';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') cancelAdminLogout(); });
</script>
