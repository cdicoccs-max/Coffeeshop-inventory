<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Set default date range (last 30 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Set default report type
$report_type = $_GET['type'] ?? 'sales';

// Handle date filter
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['filter'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
}

// Common data for all reports
$total_stats = $conn->query("
    SELECT 
        COUNT(DISTINCT o.orderID) as total_orders,
        SUM(oi.quantity) as total_items_sold,
        SUM(oi.quantity * p.price) as total_revenue,
        AVG(oi.quantity * p.price) as avg_order_value
    FROM `Order` o
    JOIN OrderItem oi ON o.orderID = oi.orderID
    JOIN Product p ON oi.productID = p.productID
    WHERE o.orderDate BETWEEN '$start_date' AND '$end_date'
")->fetch_assoc();

// Get sales data (for sales report)
$sales_report = $conn->query("
    SELECT 
        DATE(o.orderDate) as sale_date,
        COUNT(DISTINCT o.orderID) as total_orders,
        SUM(oi.quantity) as total_items,
        SUM(oi.quantity * p.price) as total_revenue,
        SUM(oi.quantity * p.price * 0.10) as estimated_tax
    FROM `Order` o
    JOIN OrderItem oi ON o.orderID = oi.orderID
    JOIN Product p ON oi.productID = p.productID
    WHERE o.orderDate BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(o.orderDate)
    ORDER BY sale_date DESC
");

// Get top selling products (for products report)
$top_products = $conn->query("
    SELECT 
        p.name,
        c.name as category,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * p.price) as revenue,
        i.quantity as current_stock
    FROM OrderItem oi
    JOIN Product p ON oi.productID = p.productID
    JOIN Category c ON p.categoryID = c.categoryID
    LEFT JOIN InventoryItem i ON p.productID = i.productID
    JOIN `Order` o ON oi.orderID = o.orderID
    WHERE o.orderDate BETWEEN '$start_date' AND '$end_date'
    AND p.is_deleted = 0
    GROUP BY p.productID
    ORDER BY total_sold DESC
    LIMIT 20
");

// Get category performance (for categories report)
$category_performance = $conn->query("
    SELECT 
        c.name as category,
        COUNT(DISTINCT p.productID) as product_count,
        SUM(i.quantity) as total_stock,
        COALESCE(SUM(oi.quantity), 0) as total_sold,
        COALESCE(SUM(oi.quantity * p.price), 0) as revenue,
        ROUND(COALESCE(SUM(oi.quantity * p.price), 0) / NULLIF(COUNT(DISTINCT p.productID), 0), 2) as avg_per_product
    FROM Category c
    LEFT JOIN Product p ON c.categoryID = p.categoryID AND p.is_deleted = 0
    LEFT JOIN InventoryItem i ON p.productID = i.productID AND i.is_active = 1
    LEFT JOIN OrderItem oi ON p.productID = oi.productID
    LEFT JOIN `Order` o ON oi.orderID = o.orderID AND o.orderDate BETWEEN '$start_date' AND '$end_date'
    WHERE c.is_active = 1
    GROUP BY c.categoryID, c.name
    ORDER BY revenue DESC
");

// Get inventory status (for inventory report)
$inventory_status = $conn->query("
    SELECT 
        p.name,
        c.name as category,
        i.quantity,
        CASE 
            WHEN i.quantity = 0 THEN 'Out of Stock'
            WHEN i.quantity < 10 THEN 'Low Stock'
            ELSE 'In Stock'
        END as status,
        s.name as supplier,
        p.price
    FROM InventoryItem i
    JOIN Product p ON i.productID = p.productID
    LEFT JOIN Category c ON p.categoryID = c.categoryID
    LEFT JOIN Supplier s ON p.supplierID = s.supplierID
    WHERE i.is_active = 1 AND p.is_deleted = 0
    ORDER BY i.quantity ASC
");

// Get monthly sales trend (for chart)
$monthly_trend = $conn->query("
    SELECT 
        DATE_FORMAT(o.orderDate, '%Y-%m') as month,
        SUM(oi.quantity * p.price) as revenue,
        COUNT(DISTINCT o.orderID) as orders
    FROM `Order` o
    JOIN OrderItem oi ON o.orderID = oi.orderID
    JOIN Product p ON oi.productID = p.productID
    WHERE o.orderDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(o.orderDate, '%Y-%m')
    ORDER BY month
");

// Generate chart data
$months = [];
$revenues = [];
$orders = [];
while ($trend = $monthly_trend->fetch_assoc()) {
    $months[] = date('M Y', strtotime($trend['month'] . '-01'));
    $revenues[] = floatval($trend['revenue']);
    $orders[] = intval($trend['orders']);
}

// Get low stock count
$low_stock = $conn->query("
    SELECT COUNT(*) as count 
    FROM InventoryItem i 
    JOIN Product p ON i.productID = p.productID 
    WHERE i.quantity < 10 AND i.quantity > 0 AND i.is_active = 1 AND p.is_deleted = 0
")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics | BrewFlow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-filters {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid rgba(139,69,19,0.1);
        }
        
        /* Report Type Tabs */
        .report-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            background: white;
            border-radius: 10px;
            padding: 5px;
            border: 1px solid rgba(139,69,19,0.1);
        }
        
        .report-tab {
            flex: 1;
            padding: 12px 15px;
            text-align: center;
            border: none;
            background: transparent;
            color: rgba(44,24,16,0.6);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .report-tab:hover {
            background: rgba(139,69,19,0.05);
            color: var(--primary);
        }
        
        .report-tab.active {
            background: var(--primary);
            color: white;
        }
        
        .report-tab i {
            margin-right: 8px;
            font-size: 0.9rem;
        }
        
        /* Report Content */
        .report-content {
            display: none;
        }
        
        .report-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* 4 boxes in 1 line - COMPACT */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid rgba(139,69,19,0.1);
            transition: transform 0.2s ease;
            text-align: center;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(139,69,19,0.1);
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
            line-height: 1;
        }
        
        .stat-label {
            color: rgba(44,24,16,0.6);
            font-size: 0.85rem;
            margin-bottom: 8px;
        }
        
        .stat-change {
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 12px;
            display: inline-block;
        }
        
        .positive { 
            background: rgba(93,139,102,0.1); 
            color: var(--success); 
        }
        
        .negative { 
            background: rgba(196,69,54,0.1); 
            color: var(--error); 
        }
        
        .neutral { 
            background: rgba(139,69,19,0.1); 
            color: var(--primary); 
        }
        
        /* Smaller chart boxes */
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid rgba(139,69,19,0.1);
            height: 250px;
        }
        
        /* Single column layout */
        .report-sections {
            margin-bottom: 30px;
        }
        
        .report-section {
            margin-bottom: 20px;
        }
        
        .report-section .card {
            padding: 15px;
            border-radius: 10px;
        }
        
        .report-section .table-container {
            max-height: 400px;
            font-size: 0.9rem;
        }
        
        /* UPDATED: Removed export-buttons class, now using single button */
        .print-button-container {
            display: flex;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .section-title i {
            color: var(--accent);
            font-size: 1rem;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }
        
        .form-group {
            flex: 1;
        }
        
        /* Compact icon styling for stat cards */
        .stat-icon {
            font-size: 1.4rem;
            color: var(--accent);
            margin-bottom: 10px;
        }
        
        /* Compact tables */
        table {
            font-size: 0.85rem;
        }
        
        table th {
            padding: 8px 10px;
            font-size: 0.8rem;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        
        table td {
            padding: 6px 10px;
        }
        
        /* Status badges */
        .badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .badge-success {
            background: rgba(93,139,102,0.1);
            color: var(--success);
        }
        
        .badge-warning {
            background: rgba(230,180,0,0.1);
            color: var(--warning);
        }
        
        .badge-error {
            background: rgba(196,69,54,0.1);
            color: var(--error);
        }
        
        .badge-primary {
            background: rgba(139,69,19,0.1);
            color: var(--primary);
        }
        
        .badge-info {
            background: rgba(59,130,246,0.1);
            color: #3b82f6;
        }
        
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .print-button-container {
                width: 100%;
            }
            
            .btn-print {
                width: 100%;
            }
            
            .chart-container {
                height: 220px;
            }
            
            .report-tabs {
                flex-wrap: wrap;
            }
            
            .report-tab {
                flex: 0 0 calc(50% - 10px);
                margin-bottom: 5px;
            }
        }
        
        @media (max-width: 480px) {
            .stat-value {
                font-size: 1.5rem;
            }
            
            .chart-container {
                height: 200px;
                padding: 10px;
            }
            
            .section-title {
                font-size: 1rem;
            }
            
            .report-tab {
                flex: 0 0 100%;
                margin-bottom: 5px;
            }
        }
        
        /* Custom styling for print button only */
        .btn-print {
            background: linear-gradient(135deg, var(--primary) 0%, #8B4513 100%);
            border: none;
            color: white;
            padding: 10px 20px;
            font-size: 0.95rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-print:hover {
            background: linear-gradient(135deg, #8B4513 0%, var(--primary) 100%);
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(139,69,19,0.2);
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: rgba(44,24,16,0.5);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: rgba(44,24,16,0.2);
        }
        
        /* Scrollbar styling */
        .table-container::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: rgba(139,69,19,0.05);
            border-radius: 3px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: rgba(139,69,19,0.2);
            border-radius: 3px;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background: rgba(139,69,19,0.3);
        }
        
        /* Print styles */
        @media print {
            .sidebar, .print-button-container, .report-filters, .report-tabs, .header button {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .report-content {
                display: block !important;
            }
            
            .card, .stat-card, .chart-container {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
                break-inside: avoid;
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
                    <h1>Reports & Analytics</h1>
                    <p>Business insights and performance metrics</p>
                </div>
                <!-- UPDATED: Only Print button, no PDF export -->
                <div class="print-button-container">
                    <button class="btn-print" onclick="printReport()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>
            
            <!-- Date Filter -->
            <div class="report-filters">
                <h3 class="section-title">
                    <i class="fas fa-filter"></i>
                    Filter Reports
                </h3>
                <form method="POST" id="filterForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label style="font-size: 0.85rem;">Start Date</label>
                            <input type="date" name="start_date" value="<?php echo $start_date; ?>" required>
                        </div>
                        <div class="form-group">
                            <label style="font-size: 0.85rem;">End Date</label>
                            <input type="date" name="end_date" value="<?php echo $end_date; ?>" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="filter" class="btn btn-primary" style="height: 38px; font-size: 0.9rem;">
                                <i class="fas fa-search"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Report Type Tabs -->
            <div class="report-tabs">
                <button class="report-tab <?php echo $report_type == 'sales' ? 'active' : ''; ?>" 
                        onclick="switchReport('sales')">
                    <i class="fas fa-chart-line"></i> Sales Report
                </button>
                <button class="report-tab <?php echo $report_type == 'products' ? 'active' : ''; ?>" 
                        onclick="switchReport('products')">
                    <i class="fas fa-box"></i> Products Report
                </button>
                <button class="report-tab <?php echo $report_type == 'categories' ? 'active' : ''; ?>" 
                        onclick="switchReport('categories')">
                    <i class="fas fa-tags"></i> Categories Report
                </button>
                <button class="report-tab <?php echo $report_type == 'inventory' ? 'active' : ''; ?>" 
                        onclick="switchReport('inventory')">
                    <i class="fas fa-cubes"></i> Inventory Report
                </button>
            </div>
            
            <!-- Key Metrics (Shown for all reports) -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($total_stats['total_revenue'] ?? 0, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> Period
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_stats['total_orders'] ?? 0; ?></div>
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-change neutral">
                        <i class="fas fa-box"></i> <?php echo number_format($total_stats['total_items_sold'] ?? 0); ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($total_stats['avg_order_value'] ?? 0, 2); ?></div>
                    <div class="stat-label">Avg Order Value</div>
                    <div class="stat-change neutral">
                        Per order
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-value"><?php echo $low_stock; ?></div>
                    <div class="stat-label">Low Stock Items</div>
                    <div class="stat-change <?php echo $low_stock > 0 ? 'negative' : 'positive'; ?>">
                        <i class="fas fa-<?php echo $low_stock > 0 ? 'exclamation-triangle' : 'check-circle'; ?>"></i> 
                        <?php echo $low_stock > 0 ? 'Attention' : 'Good'; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sales Chart (Shown for all reports) -->
            <div class="chart-container">
                <h3 class="section-title">
                    <i class="fas fa-chart-line"></i>
                    Monthly Sales Trend
                </h3>
                <canvas id="salesChart"></canvas>
            </div>
            
            <!-- SALES REPORT -->
            <div id="sales-report" class="report-content <?php echo $report_type == 'sales' ? 'active' : ''; ?>">
                <div class="report-sections">
                    <!-- Daily Sales Report -->
                    <div class="card report-section">
                        <h3 class="section-title">
                            <i class="fas fa-file-invoice-dollar"></i>
                            Daily Sales Report
                        </h3>
                        
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Orders</th>
                                        <th>Items Sold</th>
                                        <th>Revenue</th>
                                        <th>Tax (10%)</th>
                                        <th>Net Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_items = 0;
                                    $total_revenue = 0;
                                    $total_orders = 0;
                                    $row_count = 0;
                                    $sales_report->data_seek(0); // Reset pointer
                                    while($sale = $sales_report->fetch_assoc()):
                                        $total_items += $sale['total_items'];
                                        $total_revenue += $sale['total_revenue'];
                                        $total_orders += $sale['total_orders'];
                                        $row_count++;
                                    ?>
                                        <tr>
                                            <td><strong><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></strong></td>
                                            <td style="text-align: center;"><?php echo $sale['total_orders']; ?></td>
                                            <td style="text-align: center;"><?php echo $sale['total_items']; ?></td>
                                            <td style="text-align: right;">₱<?php echo number_format($sale['total_revenue'], 2); ?></td>
                                            <td style="text-align: right;">₱<?php echo number_format($sale['estimated_tax'], 2); ?></td>
                                            <td style="text-align: right; font-weight: 600;">
                                                ₱<?php echo number_format($sale['total_revenue'] - $sale['estimated_tax'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($row_count == 0): ?>
                                        <tr>
                                            <td colspan="6" class="empty-state">
                                                <i class="fas fa-chart-bar"></i>
                                                <p>No sales data for the selected period</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background: rgba(139,69,19,0.05); font-weight: bold;">
                                        <td>TOTAL (<?php echo $row_count; ?> days)</td>
                                        <td style="text-align: center;"><?php echo $total_orders; ?></td>
                                        <td style="text-align: center;"><?php echo $total_items; ?></td>
                                        <td style="text-align: right;">₱<?php echo number_format($total_revenue, 2); ?></td>
                                        <td style="text-align: right;">₱<?php echo number_format($total_revenue * 0.10, 2); ?></td>
                                        <td style="text-align: right;">₱<?php echo number_format($total_revenue * 0.90, 2); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- PRODUCTS REPORT -->
            <div id="products-report" class="report-content <?php echo $report_type == 'products' ? 'active' : ''; ?>">
                <div class="report-sections">
                    <!-- Top Products -->
                    <div class="card report-section">
                        <h3 class="section-title">
                            <i class="fas fa-star"></i>
                            Top Selling Products
                        </h3>
                        
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Sold</th>
                                        <th>Current Stock</th>
                                        <th>Revenue</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $top_products->data_seek(0); // Reset pointer
                                    while($product = $top_products->fetch_assoc()): 
                                        $status = $product['current_stock'] == 0 ? 'Out of Stock' : 
                                                 ($product['current_stock'] < 10 ? 'Low Stock' : 'In Stock');
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                                            <td style="text-align: center;">
                                                <span class="badge badge-primary">
                                                    <?php echo $product['total_sold']; ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center;"><?php echo $product['current_stock'] ?? 0; ?></td>
                                            <td style="text-align: right; font-weight: 600;">
                                                ₱<?php echo number_format($product['revenue'], 2); ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $status == 'In Stock' ? 'badge-success' : 
                                                         ($status == 'Low Stock' ? 'badge-warning' : 'badge-error'); 
                                                ?>">
                                                    <?php echo $status; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($top_products->num_rows == 0): ?>
                                        <tr>
                                            <td colspan="6" class="empty-state">
                                                <i class="fas fa-box"></i>
                                                <p>No product sales data for the selected period</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CATEGORIES REPORT -->
            <div id="categories-report" class="report-content <?php echo $report_type == 'categories' ? 'active' : ''; ?>">
                <div class="report-sections">
                    <!-- Category Performance -->
                    <div class="card report-section">
                        <h3 class="section-title">
                            <i class="fas fa-tags"></i>
                            Category Performance
                        </h3>
                        
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Products</th>
                                        <th>Total Stock</th>
                                        <th>Units Sold</th>
                                        <th>Revenue</th>
                                        <th>Avg/Product</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $category_performance->data_seek(0); // Reset pointer
                                    while($cat = $category_performance->fetch_assoc()): 
                                        $performance = $cat['revenue'] > 1000 ? 'High' : 
                                                      ($cat['revenue'] > 500 ? 'Medium' : 'Low');
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($cat['category']); ?></strong></td>
                                            <td style="text-align: center;"><?php echo $cat['product_count']; ?></td>
                                            <td style="text-align: center;"><?php echo number_format($cat['total_stock']); ?></td>
                                            <td style="text-align: center;"><?php echo number_format($cat['total_sold']); ?></td>
                                            <td style="text-align: right; font-weight: 600;">
                                                ₱<?php echo number_format($cat['revenue'], 2); ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge badge-info">
                                                    ₱<?php echo number_format($cat['avg_per_product'], 2); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($category_performance->num_rows == 0): ?>
                                        <tr>
                                            <td colspan="6" class="empty-state">
                                                <i class="fas fa-tags"></i>
                                                <p>No category data available</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- INVENTORY REPORT -->
            <div id="inventory-report" class="report-content <?php echo $report_type == 'inventory' ? 'active' : ''; ?>">
                <div class="report-sections">
                    <!-- Inventory Status -->
                    <div class="card report-section">
                        <h3 class="section-title">
                            <i class="fas fa-cubes"></i>
                            Inventory Status Report
                        </h3>
                        
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Current Stock</th>
                                        <th>Status</th>
                                        <th>Supplier</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $inventory_status->data_seek(0); // Reset pointer
                                    while($item = $inventory_status->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                                            <td style="text-align: center; font-weight: 600;"><?php echo $item['quantity']; ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $item['status'] == 'In Stock' ? 'badge-success' : 
                                                         ($item['status'] == 'Low Stock' ? 'badge-warning' : 'badge-error'); 
                                                ?>">
                                                    <?php echo $item['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['supplier']); ?></td>
                                            <td style="text-align: right;">₱<?php echo number_format($item['price'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($inventory_status->num_rows == 0): ?>
                                        <tr>
                                            <td colspan="6" class="empty-state">
                                                <i class="fas fa-cubes"></i>
                                                <p>No inventory data available</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <?php 
                                    $inventory_stats = $conn->query("
                                        SELECT 
                                            COUNT(*) as total_items,
                                            SUM(quantity) as total_units
                                        FROM InventoryItem i
                                        JOIN Product p ON i.productID = p.productID
                                        WHERE i.is_active = 1 AND p.is_deleted = 0
                                    ")->fetch_assoc();
                                    ?>
                                    <tr style="background: rgba(139,69,19,0.05); font-weight: bold;">
                                        <td colspan="2">TOTAL</td>
                                        <td style="text-align: center;"><?php echo number_format($inventory_stats['total_units']); ?> units</td>
                                        <td><?php echo $inventory_stats['total_items']; ?> items</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Sales Chart (Compact version)
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: <?php echo json_encode($revenues); ?>,
                    borderColor: 'rgb(139, 69, 19)',
                    backgroundColor: 'rgba(139, 69, 19, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: 'rgb(139, 69, 19)',
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 11
                            },
                            boxWidth: 12,
                            padding: 10
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        bodyFont: {
                            size: 11
                        },
                        titleFont: {
                            size: 11
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value;
                            },
                            font: {
                                size: 10
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.03)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 10
                            },
                            maxRotation: 45
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.03)'
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 5,
                        right: 5,
                        bottom: 5,
                        left: 5
                    }
                }
            }
        });
        
        // Switch between report types
        function switchReport(type) {
            // Update active tab
            document.querySelectorAll('.report-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Show selected report content
            document.querySelectorAll('.report-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(type + '-report').classList.add('active');
            
            // Update URL without page reload
            const url = new URL(window.location);
            url.searchParams.set('type', type);
            window.history.pushState({}, '', url);
        }
        
        // Print Report function
        function printReport() {
            window.print();
        }
        
        // Set initial report type from URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const type = urlParams.get('type');
            if (type) {
                switchReport(type);
            }
        });
    </script>
</body>
</html>