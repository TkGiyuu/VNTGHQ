<?php
$adminName = trim(($_SESSION['admin_firstname']??'').(' ').($_SESSION['admin_lastname']??'')) ?: ($_SESSION['admin_username']??'Admin');
$currentAdmin = basename($_SERVER['PHP_SELF'],'.php');
?>
<nav class="admin-topbar">
  <div class="admin-topbar-inner">
    <div class="d-flex align-items-center gap-3">
      <button class="admin-sidebar-toggle d-lg-none" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
      </button>
      <a href="index.php" class="d-flex align-items-center gap-2 text-decoration-none">
        <div class="brand-icon" style="width:36px;height:36px;font-size:18px"><i class="bi bi-bag-heart-fill"></i></div>
        <div class="brand-text">
          <span class="brand-name" style="font-size:18px">VNTG HQ</span>
          <span class="brand-tagline">Admin Panel</span>
        </div>
      </a>
    </div>
    <div class="d-flex align-items-center gap-3">
      <a href="../homepage.php" target="_blank" class="admin-topbar-link">
        <i class="bi bi-box-arrow-up-right me-1"></i>View Site
      </a>
      <div class="dropdown">
        <button class="admin-user-btn dropdown-toggle" data-bs-toggle="dropdown">
          <div class="admin-nav-avatar"><?= strtoupper(substr($_SESSION['admin_firstname']??'A',0,1)) ?></div>
          <span><?= htmlspecialchars($adminName) ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end vntg-dropdown">
          <li class="dropdown-user-info px-3 py-2">
            <strong class="d-block"><?= htmlspecialchars($adminName) ?></strong>
            <small>Administrator</small>
          </li>
          <li><hr class="dropdown-divider my-1"></li>
          <li><a class="dropdown-item text-danger" href="#" onclick="confirmAdminLogout(event)"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>
<div class="admin-sidebar-backdrop d-lg-none" id="sidebarBackdrop" onclick="toggleSidebar()"></div>
