<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Handle stock update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stock'])) {
    $productID = $_POST['productID'];
    $quantity = $_POST['quantity'];
    $action = $_POST['action'];
    
    // Get current quantity
    $current = $conn->query("SELECT quantity FROM InventoryItem WHERE productID = $productID")->fetch_assoc();
    $currentQty = $current ? $current['quantity'] : 0;
    
    if ($action == 'add') {
        $newQty = $currentQty + $quantity;
    } elseif ($action == 'subtract') {
        $newQty = $currentQty - $quantity;
        if ($newQty < 0) $newQty = 0;
    } else {
        $newQty = $quantity;
    }
    
    // Update inventory
    $stmt = $conn->prepare("UPDATE InventoryItem SET quantity = ? WHERE productID = ?");
    $stmt->bind_param("ii", $newQty, $productID);
    
    if ($stmt->execute()) {
        // Also update Stock table
        $conn->query("INSERT INTO Stock (productID, quantity) VALUES ($productID, $newQty)");
        $success = "Stock updated successfully!";
    } else {
        $error = "Error updating stock: " . $conn->error;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_inventory'])) {
    $productID = $_POST['productID'];
    // Soft delete: set is_active=0
    if ($conn->query("UPDATE InventoryItem SET is_active=0 WHERE productID = $productID")) {
        $success = "Inventory item deactivated successfully!";
    } else {
        $error = "Error deactivating inventory item: " . $conn->error;
    }
}

// Fetch inventory with product details
$inventory = $conn->query("
    SELECT p.productID, p.name, p.price, c.name as category, i.quantity,
           CASE 
               WHEN i.quantity = 0 THEN 'Out of Stock'
               WHEN i.quantity < 10 THEN 'Low Stock'
               ELSE 'In Stock'
           END as status
    FROM Product p
    JOIN InventoryItem i ON p.productID = i.productID
    LEFT JOIN Category c ON p.categoryID = c.categoryID
    WHERE i.is_active = 1
    ORDER BY i.quantity ASC, p.name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | BrewFlow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .inventory-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(139,69,19,0.1);
        }
        
        .summary-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .summary-label {
            color: rgba(44,24,16,0.6);
            font-size: 0.9rem;
        }
        
        .in-stock { border-left: 4px solid var(--success); }
        .low-stock { border-left: 4px solid var(--warning); }
        .out-stock { border-left: 4px solid var(--error); }
        .total-stock { border-left: 4px solid var(--accent); }
        
        .stock-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-in-stock {
            background: rgba(93,139,102,0.1);
            color: var(--success);
        }
        
        .badge-low-stock {
            background: rgba(230,180,0,0.1);
            color: var(--warning);
        }
        
        .badge-out-stock {
            background: rgba(196,69,54,0.1);
            color: var(--error);
        }
        
        .quantity-input {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-input input {
            width: 80px;
            text-align: center;
        }
        
        .quick-actions-cell {
            display: flex;
            gap: 5px;
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
                    <h1>Inventory Management</h1>
                    <p>Track and manage your coffee shop inventory</p>
                </div>
                <button class="btn btn-primary" onclick="exportInventory()">
                    <i class="fas fa-file-export"></i> Export Report
                </button>
            </div>
            
            <?php if (isset($success)): ?>
                <div style="background: rgba(93,139,102,0.1); color: var(--success); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--success);">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div style="background: rgba(196,69,54,0.1); color: var(--error); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--error);">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Inventory Summary -->
            <div class="inventory-summary">
                <?php
                // Get summary counts
                $stats = $conn->query("
                    SELECT 
                        SUM(quantity) as total,
                        SUM(CASE WHEN quantity >= 10 THEN 1 ELSE 0 END) as in_stock,
                        SUM(CASE WHEN quantity < 10 AND quantity > 0 THEN 1 ELSE 0 END) as low_stock,
                        SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_stock
                    FROM InventoryItem
                ")->fetch_assoc();
                ?>
                <div class="summary-card total-stock">
                    <div class="summary-value"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="summary-label">Total Items in Stock</div>
                </div>
                <div class="summary-card in-stock">
                    <div class="summary-value"><?php echo $stats['in_stock'] ?? 0; ?></div>
                    <div class="summary-label">In Stock Products</div>
                </div>
                <div class="summary-card low-stock">
                    <div class="summary-value"><?php echo $stats['low_stock'] ?? 0; ?></div>
                    <div class="summary-label">Low Stock Products</div>
                </div>
                <div class="summary-card out-stock">
                    <div class="summary-value"><?php echo $stats['out_stock'] ?? 0; ?></div>
                    <div class="summary-label">Out of Stock Products</div>
                </div>
            </div>
            
            <div class="card">
                <h3 class="section-title">
                    <i class="fas fa-cubes"></i>
                    Current Inventory
                </h3>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = $inventory->fetch_assoc()): 
                                $status_class = strtolower(str_replace(' ', '-', $item['status']));
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                        <small>ID: #<?php echo $item['productID']; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <strong style="font-size: 1.2rem;"><?php echo $item['quantity']; ?></strong> units
                                    </td>
                                    <td>
                                        <span class="stock-badge badge-<?php echo $status_class; ?>">
                                            <?php echo $item['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="quick-actions-cell">
                                            <button class="btn btn-sm btn-success" onclick="updateStock(<?php echo $item['productID']; ?>, 'add')">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="updateStock(<?php echo $item['productID']; ?>, 'subtract')">
                                                <i class="fas fa-minus"></i> Remove
                                            </button>
                                            <button class="btn btn-sm btn-primary" onclick="updateStock(<?php echo $item['productID']; ?>, 'set')">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stock Update Modal -->
    <div id="stockModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3 id="modalTitle">Update Stock</h3>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: rgba(44,24,16,0.6);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="stockForm" method="POST">
                    <input type="hidden" id="productID" name="productID">
                    <input type="hidden" id="action" name="action">
                    
                    <div class="form-group">
                        <label id="quantityLabel">Quantity to Add</label>
                        <input type="number" id="quantity" name="quantity" min="1" required>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="button" class="btn" onclick="closeModal()" style="flex: 1; background: rgba(44,24,16,0.1); color: var(--primary);">
                            Cancel
                        </button>
                        <button type="submit" name="update_stock" class="btn btn-primary" style="flex: 1;">
                            Update Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function updateStock(productID, action) {
            document.getElementById('stockModal').style.display = 'flex';
            document.getElementById('productID').value = productID;
            document.getElementById('action').value = action;
            
            var title = document.getElementById('modalTitle');
            var label = document.getElementById('quantityLabel');
            
            switch(action) {
                case 'add':
                    title.textContent = 'Add Stock';
                    label.textContent = 'Quantity to Add';
                    break;
                case 'subtract':
                    title.textContent = 'Remove Stock';
                    label.textContent = 'Quantity to Remove';
                    break;
                case 'set':
                    title.textContent = 'Set Stock Level';
                    label.textContent = 'New Quantity';
                    break;
            }
        }
        
        function closeModal() {
            document.getElementById('stockModal').style.display = 'none';
        }
        
        function exportInventory() {
            // Simple export function - you can enhance this with CSV export
            alert('Inventory export functionality would be implemented here.');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('stockModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>