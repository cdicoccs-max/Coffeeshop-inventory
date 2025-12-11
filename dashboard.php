<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit();
}

// Get summary stats
$totalProducts = $conn->query("SELECT COUNT(*) as cnt FROM Product")->fetch_assoc()['cnt'];
$totalCategories = $conn->query("SELECT COUNT(*) as cnt FROM Category")->fetch_assoc()['cnt'];
$totalSuppliers = $conn->query("SELECT COUNT(*) as cnt FROM Supplier")->fetch_assoc()['cnt'];
$totalInventory = $conn->query("SELECT SUM(quantity) as total FROM InventoryItem")->fetch_assoc()['total'];
if ($totalInventory === null) $totalInventory = 0;

// Get recent low stock items
$lowStock = $conn->query("SELECT p.name, c.name AS category, i.quantity 
                         FROM Product p 
                         JOIN InventoryItem i ON p.productID = i.productID 
                         JOIN Category c ON p.categoryID = c.categoryID 
                         WHERE i.quantity <= 0 
                         AND p.is_active = 1 
                         AND i.is_active = 1 
                         AND c.is_active = 1 
                         ORDER BY i.quantity ASC 
                         LIMIT 5");

// Get top selling products
$topProducts = $conn->query("SELECT p.name, COUNT(o.orderID) as sales FROM Product p
                           JOIN OrderItem oi ON p.productID = oi.productID
                           JOIN `Order` o ON oi.orderID = o.orderID
                           GROUP BY p.productID ORDER BY sales DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | BrewFlow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            border: 1px solid rgba(139,69,19,0.1);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .icon-products { background: linear-gradient(45deg, #667eea, #764ba2); }
        .icon-categories { background: linear-gradient(45deg, #f093fb, #f5576c); }
        .icon-suppliers { background: linear-gradient(45deg, #4facfe, #00f2fe); }
        .icon-inventory { background: linear-gradient(45deg, #43e97b, #38f9d7); }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: rgba(44,24,16,0.6);
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        .alert-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: rgba(245,241,234,0.5);
            border-radius: 10px;
            margin-bottom: 10px;
        }
        
        .alert-item.warning {
            border-left: 4px solid var(--warning);
        }
        
        .item-quantity {
            font-size: 0.9rem;
            padding: 4px 12px;
            background: rgba(230,180,0,0.1);
            color: var(--warning);
            border-radius: 20px;
            font-weight: 600;
        }
        
        .top-product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid rgba(139,69,19,0.1);
        }
        
        .top-product-item:last-child {
            border-bottom: none;
        }
        
        .product-sales {
            font-size: 0.9rem;
            padding: 4px 12px;
            background: rgba(93,139,102,0.1);
            color: var(--success);
            border-radius: 20px;
            font-weight: 600;
        }
        
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .action-btn {
            flex: 1;
            padding: 18px 25px;
            background: white;
            border: 2px solid rgba(212,165,116,0.3);
            border-radius: 12px;
            color: var(--primary);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .action-btn:hover {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
            transform: translateY(-2px);
        }
        
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="greeting">
                    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user']); ?>!</h1>
                    <p>Here's what's happening with your coffee shop today.</p>
                </div>
                <div style="color: rgba(44,24,16,0.6);">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo date('F j, Y'); ?>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $totalProducts; ?></div>
                            <div class="stat-label">Total Products</div>
                        </div>
                        <div class="stat-icon icon-products">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                    <div style="font-size: 0.9rem; color: rgba(44,24,16,0.6);">
                        <i class="fas fa-arrow-up" style="color: var(--success);"></i>
                        Updated today
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $totalCategories; ?></div>
                            <div class="stat-label">Categories</div>
                        </div>
                        <div class="stat-icon icon-categories">
                            <i class="fas fa-tags"></i>
                        </div>
                    </div>
                    <div style="font-size: 0.9rem; color: rgba(44,24,16,0.6);">
                        Coffee, Tea, Equipment, etc.
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $totalSuppliers; ?></div>
                            <div class="stat-label">Suppliers</div>
                        </div>
                        <div class="stat-icon icon-suppliers">
                            <i class="fas fa-truck"></i>
                        </div>
                    </div>
                    <div style="font-size: 0.9rem; color: rgba(44,24,16,0.6);">
                        Active partnerships
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-value"><?php echo $totalInventory; ?></div>
                            <div class="stat-label">Total Inventory</div>
                        </div>
                        <div class="stat-icon icon-inventory">
                            <i class="fas fa-cubes"></i>
                        </div>
                    </div>
                    <div style="font-size: 0.9rem; color: rgba(44,24,16,0.6);">
                        Units in stock
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Top Products -->
                <div class="card">
                    <div class="alert-header">
                        <i class="fas fa-chart-line" style="color: var(--success);"></i>
                        <h3 class="section-title">Top Selling Products</h3>
                    </div>
                    <div>
                        <?php if ($topProducts->num_rows > 0): ?>
                            <?php while($product = $topProducts->fetch_assoc()): ?>
                                <div class="top-product-item">
                                    <div class="item-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div class="product-sales"><?php echo $product['sales']; ?> sales</div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 20px; color: rgba(44,24,16,0.5);">
                                <i class="fas fa-coffee" style="font-size: 2rem; margin-bottom: 10px; color: var(--accent);"></i>
                                <p>No sales data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="alert-header">
                        <i class="fas fa-bolt" style="color: var(--accent);"></i>
                        <h3 class="section-title">Quick Actions</h3>
                    </div>
                    <div class="quick-actions">
                        <button class="action-btn" onclick="window.location.href='products.php?action=add'">
                            <i class="fas fa-plus"></i>
                            Add Product
                        </button>
                        <button class="action-btn" onclick="window.location.href='reports.php'">
                            <i class="fas fa-file-export"></i>
                            Export Report
                        </button>
                    </div>
                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(139,69,19,0.1);">
                        <h4 style="margin-bottom: 12px; color: rgba(44,24,16,0.7);">Recent Activity</h4>
                        <div style="font-size: 0.9rem; color: rgba(44,24,16,0.6);">
                            <p><i class="fas fa-circle" style="font-size: 0.5rem; color: var(--success);"></i> Inventory updated 2 hours ago</p>
                            <p><i class="fas fa-circle" style="font-size: 0.5rem; color: var(--accent);"></i> New supplier added yesterday</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>