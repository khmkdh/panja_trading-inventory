<?php
// includes/sidebar.php
// Set $activePage before including, e.g. $activePage = 'dashboard';
if (!isset($activePage)) $activePage = '';

function navItem($page, $label, $icon, $activePage, $href = null) {
    $active = $activePage === $page ? 'active' : '';
    $link   = $href ?? "{$page}.php";
    echo "<li><a href='{$link}' class='nav-link {$active}'>
            <i class='bi bi-{$icon}'></i> {$label}
          </a></li>";
}
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-gear-wide-connected"></i>
        <div>
            <div class="brand-name">GearVault</div>
            <div class="brand-sub">Bike Workshop Inventory</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Main</div>
        <ul>
            <?php navItem('dashboard', 'Dashboard', 'speedometer2', $activePage); ?>
            <?php navItem('inventory', 'Inventory', 'box-seam',     $activePage); ?>
        </ul>

        <div class="nav-section">Operations</div>
        <ul>
            <?php navItem('sales',          'Sales',          'receipt',   $activePage); ?>
            <?php navItem('bikes',          'Bikes',          'bicycle',   $activePage); ?>
            <?php navItem('workshop_usage', 'Workshop Usage', 'hammer',    $activePage); ?>
        </ul>

        <div class="nav-section">Reports</div>
        <ul>
            <?php navItem('report',  'Reports', 'bar-chart-line', $activePage); ?>
            <?php navItem('search',  'Search',  'search',         $activePage); ?>
            <?php navItem('profile', 'Profile', 'person-circle',  $activePage); ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-avatar">
            <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
        </div>
        <div>
            <div class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
            <div class="user-role"><?= htmlspecialchars($_SESSION['role'] ?? 'Staff') ?></div>
        </div>
        <a href="logout.php" class="logout-btn ms-auto" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</div>