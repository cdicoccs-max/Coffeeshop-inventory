<?php
include 'db.php';
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $orderID = $_POST['orderID'];
        $status = $_POST['status'];
        
        // You might want to add a status column to your Order table
        // For now, we'll use a simple update
        // $conn->query("UPDATE `Order` SET status = '$status' WHERE orderID = $orderID");
        
        $success = "Order #$orderID status updated!";
    }
    
    if (isset($_POST['cancel_order'])) {
        $orderID = $_POST['orderID'];
        
        // Restore inventory first
        $items = $conn->query("SELECT productID, quantity FROM OrderItem WHERE orderID = $orderID");
        while ($item = $items->fetch_assoc()) {
            $conn->query("UPDATE InventoryItem SET quantity = quantity + " . $item['quantity'] . " WHERE productID = " . $item['productID']);
        }
        
        // Delete order items
        $conn->query("DELETE FROM OrderItem WHERE orderID = $orderID");
        
        // Delete order
        if ($conn->query("DELETE FROM `Order` WHERE orderID = $orderID")) {
            $success = "Order #$orderID cancelled and inventory restored!";
        } else {
            $error = "Error cancelling order: " . $conn->error;
        }
    }
}

// Get all orders with their items
$orders = $conn->query("
    SELECT 
        o.orderID,
        o.orderDate,
        COUNT(oi.orderItemID) as item_count,
        SUM(p.price * oi.quantity) as total_amount,
        GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items_list
    FROM `Order` o
    LEFT JOIN OrderItem oi ON o.orderID = oi.orderID
    LEFT JOIN Product p ON oi.productID = p.productID
    GROUP BY o.orderID
    ORDER BY o.orderDate DESC, o.orderID DESC
");

// Get today's orders for quick stats
$today = date('Y-m-d');
$todayOrders = $conn->query("
    SELECT COUNT(*) as count, SUM(p.price * oi.quantity) as total
    FROM `Order` o
    LEFT JOIN OrderItem oi ON o.orderID = oi.orderID
    LEFT JOIN Product p ON oi.productID = p.productID
    WHERE o.orderDate = '$today'
")->fetch_assoc();

// Get monthly stats
$monthStart = date('Y-m-01');
$monthOrders = $conn->query("
    SELECT COUNT(*) as count, SUM(p.price * oi.quantity) as total
    FROM `Order` o
    LEFT JOIN OrderItem oi ON o.orderID = oi.orderID
    LEFT JOIN Product p ON oi.productID = p.productID
    WHERE o.orderDate >= '$monthStart'
")->fetch_assoc();

// Get popular products
$popularProducts = $conn->query("
    SELECT 
        p.name,
        SUM(oi.quantity) as total_sold,
        COUNT(DISTINCT oi.orderID) as order_count
    FROM OrderItem oi
    JOIN Product p ON oi.productID = p.productID
    GROUP BY oi.productID
    ORDER BY total_sold DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking | BrewFlow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .orders-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(139,69,19,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: rgba(44,24,16,0.6);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
            font-size: 1.5rem;
            color: white;
        }
        
        .icon-today { background: linear-gradient(45deg, #667eea, #764ba2); }
        .icon-month { background: linear-gradient(45deg, #f093fb, #f5576c); }
        .icon-total { background: linear-gradient(45deg, #4facfe, #00f2fe); }
        .icon-items { background: linear-gradient(45deg, #43e97b, #38f9d7); }
        
        .orders-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .orders-list {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
            border: 1px solid rgba(139,69,19,0.1);
        }
        
        .order-item {
            background: rgba(245,241,234,0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--accent);
            transition: transform 0.2s ease;
        }
        
        .order-item:hover {
            transform: translateX(5px);
            background: rgba(245,241,234,0.5);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(139,69,19,0.1);
        }
        
        .order-id {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-date {
            color: rgba(44,24,16,0.6);
            font-size: 0.9rem;
        }
        
        .order-amount {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--success);
        }
        
        .order-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }
        
        .order-items {
            color: rgba(44,24,16,0.8);
            font-size: 0.95rem;
            max-width: 70%;
        }
        
        .order-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        .popular-products {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
            border: 1px solid rgba(139,69,19,0.1);
            height: fit-content;
        }
        
        .product-rank {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(139,69,19,0.1);
        }
        
        .product-rank:last-child {
            border-bottom: none;
        }
        
        .rank-number {
            width: 30px;
            height: 30px;
            background: rgba(212,165,116,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--accent);
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-name {
            font-weight: 600;
            color: var(--primary);
        }
        
        .product-stats {
            font-size: 0.85rem;
            color: rgba(44,24,16,0.6);
        }
        
        .product-sales {
            font-weight: 700;
            color: var(--success);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: rgba(44,24,16,0.5);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: rgba(212,165,116,0.3);
            margin-bottom: 15px;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 25px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 45px;
            border: 1px solid rgba(139,69,19,0.2);
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(44,24,16,0.4);
        }
        
        .date-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .date-filter button {
            padding: 8px 16px;
            background: white;
            border: 1px solid rgba(139,69,19,0.2);
            border-radius: 8px;
            color: rgba(44,24,16,0.7);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .date-filter button:hover {
            background: rgba(212,165,116,0.1);
            color: var(--accent);
        }
        
        .date-filter button.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }
        
        @media (max-width: 1024px) {
            .orders-container {
                grid-template-columns: 1fr;
            }
            
            .orders-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .orders-stats {
                grid-template-columns: 1fr;
            }
            
            .order-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .order-details {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .order-items {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <div class="greeting">
                    <h1>Order Tracking</h1>
                    <p>Monitor and manage all customer orders</p>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
                <div style="background: rgba(93,139,102,0.1); color: var(--success); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--success); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div style="background: rgba(196,69,54,0.1); color: var(--error); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--error); display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Order Statistics -->
            <div class="orders-stats">
                <div class="stat-box">
                    <div class="stat-icon icon-today">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-value"><?php echo $todayOrders['count'] ?? 0; ?></div>
                    <div class="stat-label">Orders Today</div>
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(139,69,19,0.1);">
                        <div style="font-size: 0.9rem; color: rgba(44,24,16,0.6);">
                            $<?php echo number_format($todayOrders['total'] ?? 0, 2); ?> revenue
                        </div>
                    </div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-icon icon-month">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-value"><?php echo $monthOrders['count'] ?? 0; ?></div>
                    <div class="stat-label">This Month</div>
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(139,69,19,0.1);">
                        <div style="font-size: 0.9rem; color: rgba(44,24,16,0.6);">
                            $<?php echo number_format($monthOrders['total'] ?? 0, 2); ?> revenue
                        </div>
                    </div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-icon icon-total">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-value"><?php echo $orders->num_rows; ?></div>
                    <div class="stat-label">Total Orders</div>
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(139,69,19,0.1);">
                        <div style="font-size: 0.9rem; color: rgba(44,24,16,0.6);">
                            All-time sales
                        </div>
                    </div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-icon icon-items">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-value">
                        <?php 
                        // Get total items sold
                        $totalItems = $conn->query("SELECT SUM(quantity) as total FROM OrderItem")->fetch_assoc()['total'] ?? 0;
                        echo $totalItems;
                        ?>
                    </div>
                    <div class="stat-label">Items Sold</div>
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(139,69,19,0.1);">
                        <div style="font-size: 0.9rem; color: rgba(44,24,16,0.6);">
                            Products moved
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="orders-container">
                <!-- Left: Orders List -->
                <div class="orders-list">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search orders by ID or items..." id="searchOrders">
                    </div>
                    
                    <div class="date-filter">
                        <button onclick="filterOrders('all')" class="active">All Orders</button>
                        <button onclick="filterOrders('today')">Today</button>
                        <button onclick="filterOrders('week')">This Week</button>
                        <button onclick="filterOrders('month')">This Month</button>
                    </div>
                    
                    <h3 class="section-title">
                        <i class="fas fa-list"></i>
                        Recent Orders
                    </h3>
                    
                    <?php if ($orders->num_rows > 0): ?>
                        <div id="ordersList">
                            <?php while($order = $orders->fetch_assoc()): 
                                // Get detailed items for this order
                                $orderDetails = $conn->query("
                                    SELECT p.name, oi.quantity, p.price 
                                    FROM OrderItem oi 
                                    JOIN Product p ON oi.productID = p.productID 
                                    WHERE oi.orderID = " . $order['orderID'] . "
                                    ORDER BY oi.orderItemID
                                ");
                            ?>
                                <div class="order-item" data-id="<?php echo $order['orderID']; ?>" data-date="<?php echo $order['orderDate']; ?>">
                                    <div class="order-header">
                                        <div>
                                            <div class="order-id">
                                                <span>Order #<?php echo $order['orderID']; ?></span>
                                            </div>
                                            <div class="order-date">
                                                <i class="far fa-calendar"></i> 
                                                <?php echo date('F j, Y', strtotime($order['orderDate'])); ?>
                                            </div>
                                        </div>
                                        <div class="order-amount">
                                            $<?php echo number_format($order['total_amount'] ?? 0, 2); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="order-details">
                                        <div class="order-items">
                                            <strong>Items:</strong>
                                            <?php if ($orderDetails->num_rows > 0): ?>
                                                <br>
                                                <?php while($item = $orderDetails->fetch_assoc()): ?>
                                                    <span style="display: inline-block; background: rgba(212,165,116,0.1); padding: 3px 8px; border-radius: 4px; margin: 3px; font-size: 0.85rem;">
                                                        <?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?>
                                                    </span>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <span style="color: rgba(44,24,16,0.5);">No items</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="order-actions">
                                            <button class="btn btn-sm btn-primary" onclick="viewOrderDetails(<?php echo $order['orderID']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-error" onclick="cancelOrder(<?php echo $order['orderID']; ?>)">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(139,69,19,0.1); font-size: 0.9rem; color: rgba(44,24,16,0.6);">
                                        <i class="fas fa-box"></i> <?php echo $order['item_count']; ?> items 
                                        | 
                                        <i class="far fa-clock"></i> 
                                        <?php 
                                        $date = new DateTime($order['orderDate']);
                                        $now = new DateTime();
                                        $interval = $date->diff($now);
                                        
                                        if ($interval->y > 0) {
                                            echo $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                                        } elseif ($interval->m > 0) {
                                            echo $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                                        } elseif ($interval->d > 0) {
                                            echo $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                                        } elseif ($interval->h > 0) {
                                            echo $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                                        } elseif ($interval->i > 0) {
                                            echo $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
                                        } else {
                                            echo 'Just now';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <h3>No Orders Found</h3>
                            <p>Orders will appear here when customers make purchases.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Right: Popular Products -->
                <div class="popular-products">
                    <h3 class="section-title">
                        <i class="fas fa-chart-line"></i>
                        Popular Products
                    </h3>
                    
                    <?php if ($popularProducts->num_rows > 0): ?>
                        <div id="popularProductsList">
                            <?php $rank = 1; ?>
                            <?php while($product = $popularProducts->fetch_assoc()): ?>
                                <div class="product-rank">
                                    <div class="rank-number"><?php echo $rank; ?></div>
                                    <div class="product-info">
                                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div class="product-stats">
                                            Sold: <?php echo $product['total_sold']; ?> units | 
                                            Orders: <?php echo $product['order_count']; ?>
                                        </div>
                                    </div>
                                    <div class="product-sales">
                                        <?php echo $product['total_sold']; ?> sold
                                    </div>
                                </div>
                                <?php $rank++; ?>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; color: rgba(44,24,16,0.5);">
                            <i class="fas fa-coffee" style="font-size: 2rem; margin-bottom: 10px; color: rgba(212,165,116,0.3);"></i>
                            <p>No sales data yet</p>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(139,69,19,0.1);">
                        <h4 style="color: var(--primary); margin-bottom: 10px;">
                            <i class="fas fa-info-circle"></i> Quick Stats
                        </h4>
                        <?php
                        // Get some quick stats
                        $avgOrderValue = $conn->query("
                            SELECT AVG(p.price * oi.quantity) as avg_value
                            FROM OrderItem oi
                            JOIN Product p ON oi.productID = p.productID
                        ")->fetch_assoc()['avg_value'];
                        
                        $busiestDay = $conn->query("
                            SELECT orderDate, COUNT(*) as order_count
                            FROM `Order`
                            GROUP BY orderDate
                            ORDER BY order_count DESC
                            LIMIT 1
                        ")->fetch_assoc();
                        ?>
                        <div style="font-size: 0.9rem; color: rgba(44,24,16,0.7);">
                            <div style="margin-bottom: 5px;">
                                <i class="fas fa-dollar-sign" style="color: var(--success);"></i>
                                <strong>Avg Order:</strong> $<?php echo number_format($avgOrderValue ?? 0, 2); ?>
                            </div>
                            <?php if ($busiestDay): ?>
                                <div>
                                    <i class="fas fa-calendar-star" style="color: var(--warning);"></i>
                                    <strong>Busiest Day:</strong> 
                                    <?php echo date('M j', strtotime($busiestDay['orderDate'])); ?> 
                                    (<?php echo $busiestDay['order_count']; ?> orders)
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Order Details Modal -->
    <div id="viewOrderModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Order Details</h3>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: rgba(44,24,16,0.6);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Cancel Order Confirmation Modal -->
    <div id="cancelOrderModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Cancel</h3>
                <button onclick="closeCancelModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: rgba(44,24,16,0.6);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this order?</p>
                <div style="background: rgba(196,69,54,0.1); padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid var(--error);">
                    <i class="fas fa-info-circle" style="color: var(--error);"></i>
                    <span style="font-size: 0.9rem; color: var(--error);">
                        This will restore all items to inventory.
                    </span>
                </div>
                <form method="POST" id="cancelOrderForm">
                    <input type="hidden" id="cancelOrderID" name="orderID">
                    <div style="display: flex; gap: 15px; margin-top: 20px;">
                        <button type="button" class="btn" onclick="closeCancelModal()" style="flex: 1; background: rgba(44,24,16,0.1); color: var(--primary);">
                            Keep Order
                        </button>
                        <button type="submit" name="cancel_order" class="btn btn-error" style="flex: 1;">
                            <i class="fas fa-times"></i> Cancel Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function filterOrders(filter) {
            const buttons = document.querySelectorAll('.date-filter button');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // In a real application, this would fetch filtered orders from the server
            // For now, we'll just show a message
            alert('Filtering by ' + filter + ' would be implemented with AJAX');
        }
        
        function viewOrderDetails(orderID) {
            fetch('get_order_details.php?id=' + orderID)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('viewOrderModal').style.display = 'flex';
                    document.getElementById('orderDetailsContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('orderDetailsContent').innerHTML = `
                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-exclamation-circle" style="font-size: 3rem; color: var(--error); margin-bottom: 20px;"></i>
                            <p>Error loading order details</p>
                        </div>
                    `;
                    document.getElementById('viewOrderModal').style.display = 'flex';
                });
        }
        
        function cancelOrder(orderID) {
            document.getElementById('cancelOrderModal').style.display = 'flex';
            document.getElementById('cancelOrderID').value = orderID;
        }
        
        function closeModal() {
            document.getElementById('viewOrderModal').style.display = 'none';
        }
        
        function closeCancelModal() {
            document.getElementById('cancelOrderModal').style.display = 'none';
        }
        
        // Search functionality
        document.getElementById('searchOrders').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const orders = document.querySelectorAll('.order-item');
            
            orders.forEach(order => {
                const orderId = order.getAttribute('data-id').toString();
                const orderDate = order.querySelector('.order-date').textContent.toLowerCase();
                const orderItems = order.querySelector('.order-items').textContent.toLowerCase();
                
                if (orderId.includes(searchTerm) || 
                    orderDate.includes(searchTerm) || 
                    orderItems.includes(searchTerm)) {
                    order.style.display = 'block';
                } else {
                    order.style.display = 'none';
                }
            });
        });
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                if (event.target.id === 'viewOrderModal') closeModal();
                if (event.target.id === 'cancelOrderModal') closeCancelModal();
            }
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeCancelModal();
            }
        });
    </script>
</body>
</html>