<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $categoryID = $_POST['categoryID'];
        $supplierID = $_POST['supplierID'];
        
        $stmt = $conn->prepare("INSERT INTO Product (name, description, price, categoryID, supplierID) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdii", $name, $description, $price, $categoryID, $supplierID);
        
        if ($stmt->execute()) {
            $success = "Product added successfully!";
            // Add to inventory
            $productID = $conn->insert_id;
            $conn->query("INSERT INTO InventoryItem (productID, quantity) VALUES ($productID, 0)");
        } else {
            $error = "Error adding product: " . $conn->error;
        }
    } elseif (isset($_POST['update_product'])) {
        $productID = $_POST['productID'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $categoryID = $_POST['categoryID'];
        $supplierID = $_POST['supplierID'];
        
        $stmt = $conn->prepare("UPDATE Product SET name=?, description=?, price=?, categoryID=?, supplierID=? WHERE productID=?");
        $stmt->bind_param("ssdiii", $name, $description, $price, $categoryID, $supplierID, $productID);
        
        if ($stmt->execute()) {
            $success = "Product updated successfully!";
        } else {
            $error = "Error updating product: " . $conn->error;
        }
    } elseif (isset($_POST['delete_product'])) {
        $productID = $_POST['productID'];

        // Check if product is referenced in any order
        $checkOrder = $conn->query("SELECT COUNT(*) as cnt FROM OrderItem WHERE productID = $productID");
        $rowOrder = $checkOrder->fetch_assoc();
        if ($rowOrder['cnt'] > 0) {
            // Soft delete: set is_active=0
            if ($conn->query("UPDATE Product SET is_active=0 WHERE productID = $productID")) {
                $success = "Product deactivated successfully!";
            } else {
                $error = "Error deactivating product: " . $conn->error;
            }
        } else {
            // First delete from inventory
            $conn->query("DELETE FROM InventoryItem WHERE productID = $productID");
            if ($conn->query("DELETE FROM Product WHERE productID = $productID")) {
                $success = "Product deleted successfully!";
            } else {
                $error = "Error deleting product: " . $conn->error;
            }
        }
    }
}

// Fetch all products with category and supplier names
$products = $conn->query("
    SELECT p.*, c.name as category_name, s.name as supplier_name 
    FROM Product p
    LEFT JOIN Category c ON p.categoryID = c.categoryID
    LEFT JOIN Supplier s ON p.supplierID = s.supplierID
    WHERE p.is_active = 1
    ORDER BY p.productID DESC
");

// Fetch categories and suppliers for dropdowns
$categories = $conn->query("SELECT * FROM Category ORDER BY name");
$suppliers = $conn->query("SELECT * FROM Supplier ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | BrewFlow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .price {
            font-weight: 600;
            color: var(--success);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-in-stock {
            background: rgba(93,139,102,0.1);
            color: var(--success);
        }
        
        .status-low-stock {
            background: rgba(230,180,0,0.1);
            color: var(--warning);
        }
        
        .status-out-of-stock {
            background: rgba(196,69,54,0.1);
            color: var(--error);
        }
        
        .search-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .search-box {
            flex: 1;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 40px;
        }
        
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(44,24,16,0.4);
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
                    <h1>Products Management</h1>
                    <p>Manage your coffee shop products and inventory</p>
                </div>
                <button class="btn btn-primary" onclick="openModal('addProductModal')">
                    <i class="fas fa-plus"></i> Add New Product
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
            
            <div class="search-filter">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search products...">
                </div>
                <select style="padding: 12px 16px; border-radius: 8px; border: 1px solid rgba(139,69,19,0.2);">
                    <option value="">All Categories</option>
                    <?php while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['categoryID']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="card">
                <h3 class="section-title">
                    <i class="fas fa-box"></i>
                    All Products
                </h3>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($product = $products->fetch_assoc()): 
                                // Get inventory status
                                $inventory = $conn->query("SELECT quantity FROM InventoryItem WHERE productID = " . $product['productID'])->fetch_assoc();
                                $quantity = $inventory ? $inventory['quantity'] : 0;
                                
                                if ($quantity == 0) {
                                    $status_class = 'status-out-of-stock';
                                    $status_text = 'Out of Stock';
                                } elseif ($quantity < 10) {
                                    $status_class = 'status-low-stock';
                                    $status_text = 'Low Stock';
                                } else {
                                    $status_class = 'status-in-stock';
                                    $status_text = 'In Stock';
                                }
                            ?>
                                <tr>
                                    <td>#<?php echo $product['productID']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                        <small style="color: rgba(44,24,16,0.6);"><?php echo substr($product['description'], 0, 50) . '...'; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($product['supplier_name'] ?? 'N/A'); ?></td>
                                    <td class="price">₱<?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?> (<?php echo $quantity; ?>)
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm btn-primary" onclick="editProduct(<?php echo $product['productID']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-error" onclick="deleteProduct(<?php echo $product['productID']; ?>, '<?php echo addslashes($product['name']); ?>')">
                                                <i class="fas fa-trash"></i> Delete
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
    
    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Product</h3>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: rgba(44,24,16,0.6);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="productForm" method="POST">
                    <input type="hidden" id="productID" name="productID">
                    
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="categoryID">Category *</label>
                        <select id="categoryID" name="categoryID" required>
                            <option value="">Select Category</option>
                            <?php 
                            $categories->data_seek(0); // Reset pointer
                            while($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['categoryID']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="supplierID">Supplier *</label>
                        <select id="supplierID" name="supplierID" required>
                            <option value="">Select Supplier</option>
                            <?php 
                            $suppliers->data_seek(0); // Reset pointer
                            while($sup = $suppliers->fetch_assoc()): ?>
                                <option value="<?php echo $sup['supplierID']; ?>"><?php echo htmlspecialchars($sup['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="button" class="btn" onclick="closeModal()" style="flex: 1; background: rgba(44,24,16,0.1); color: var(--primary);">
                            Cancel
                        </button>
                        <button type="submit" name="add_product" id="submitBtn" class="btn btn-primary" style="flex: 1;">
                            Add Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <button onclick="closeDeleteModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: rgba(44,24,16,0.6);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage">Are you sure you want to delete this product?</p>
                <form id="deleteForm" method="POST">
                    <input type="hidden" id="deleteProductID" name="productID">
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="button" class="btn" onclick="closeDeleteModal()" style="flex: 1; background: rgba(44,24,16,0.1); color: var(--primary);">
                            Cancel
                        </button>
                        <button type="submit" name="delete_product" class="btn btn-error" style="flex: 1;">
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openModal(modalId) {
            document.getElementById('productModal').style.display = 'flex';
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('submitBtn').name = 'add_product';
            document.getElementById('submitBtn').textContent = 'Add Product';
            document.getElementById('productForm').reset();
            document.getElementById('productID').value = '';
        }
        
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }
        
        function editProduct(id) {
            fetch('get_product.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('productModal').style.display = 'flex';
                    document.getElementById('modalTitle').textContent = 'Edit Product';
                    document.getElementById('submitBtn').name = 'update_product';
                    document.getElementById('submitBtn').textContent = 'Update Product';
                    document.getElementById('productID').value = data.productID;
                    document.getElementById('name').value = data.name;
                    document.getElementById('description').value = data.description;
                    document.getElementById('price').value = data.price;
                    document.getElementById('categoryID').value = data.categoryID;
                    document.getElementById('supplierID').value = data.supplierID;
                });
        }
        
        function deleteProduct(id, name) {
            document.getElementById('deleteModal').style.display = 'flex';
            document.getElementById('deleteMessage').textContent = `Are you sure you want to delete "${name}"?`;
            document.getElementById('deleteProductID').value = id;
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modals = document.getElementsByClassName('modal');
            for (var i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>