<?php
// includes/sidebar.php
// Usage: include 'includes/sidebar.php';
// Set $activePage before including, e.g. $activePage = 'dashboard';
if (!isset($activePage)) $activePage = '';

function navItem($page, $label, $icon, $activePage) {
    $active = $activePage === $page ? 'active' : '';
    echo "<li><a href='{$page}.php' class='nav-link {$active}'>
            <i class='bi bi-{$icon}'></i> {$label}
          </a></li>";
}
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-tools"></i>
        <div>
            <div class="brand-name">Panja Trading</div>
            <div class="brand-sub">Inventory System</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Main</div>
        <ul>
            <?php navItem('dashboard',  'Dashboard',   'speedometer2',      $activePage); ?>
            <?php navItem('inventory',  'Inventory',   'box-seam',          $activePage); ?>
            <?php navItem('items',      'Items',       'plus-square',       $activePage); ?>
            <?php navItem('parts',      'Parts',       'wrench',            $activePage); ?>
            <?php navItem('category',   'Categories',  'tags',              $activePage); ?>
        </ul>

        <div class="nav-section">Operations</div>
        <ul>
            <?php navItem('sales',             'Sales',          'receipt',          $activePage); ?>
            <?php navItem('customer',          'Customers',      'people',           $activePage); ?>
            <?php navItem('bikes',             'Bikes',          'bicycle',          $activePage); ?>
            <?php navItem('warehouse_items',   'Warehouse',      'building',         $activePage); ?>
            <?php navItem('workshop_usage',    'Workshop Usage', 'hammer',           $activePage); ?>
        </ul>

        <div class="nav-section">Reports</div>
        <ul>
            <?php navItem('report',      'Reports',    'bar-chart-line',   $activePage); ?>
            <?php navItem('print_bill',  'Print Bill', 'printer',          $activePage); ?>
            <?php navItem('search',      'Search',     'search',           $activePage); ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-avatar">AD</div>
        <div>
            <div class="user-name">Admin</div>
            <div class="user-role">Manager</div>
        </div>
        <a href="logout.php" class="logout-btn ms-auto" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</div>