<?php
// sidebar.php
?>
<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <i class="fas fa-mug-hot"></i>
        BrewFlow
    </div>
    
    <nav class="nav-links">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        ?>
        <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i>
            Dashboard
        </a>
        <a href="products.php" class="nav-item <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            Products
        </a>
        <a href="categories.php" class="nav-item <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i>
            Categories
        </a>
         <a href="orders.php" class="nav-item <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i>
            Orders
        </a>
        <a href="suppliers.php" class="nav-item <?php echo $current_page == 'suppliers.php' ? 'active' : ''; ?>">
            <i class="fas fa-truck"></i>
            Suppliers
        </a>
        <a href="inventory.php" class="nav-item <?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>">
            <i class="fas fa-cube"></i>
            Inventory
        </a>
        <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            Reports
        </a>
        <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            Settings
        </a>
    </nav>
    
    <div class="user-info">
        <div class="user-avatar">
            <?php echo strtoupper(substr($_SESSION['user'] ?? 'A', 0, 1)); ?>
        </div>
        <div style="color: var(--light); font-weight: 600;">
            <?php echo htmlspecialchars($_SESSION['user'] ?? 'Administrator'); ?>
        </div>
        <div style="color: rgba(245,241,234,0.5); font-size: 0.9rem; margin-top: 4px;">
            <?php echo ucfirst(htmlspecialchars($_SESSION['role'] ?? 'Admin')); ?>
        </div>
        <a href="logout.php" style="display: block; margin-top: 15px; color: var(--accent); font-size: 0.9rem; text-decoration: none;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>