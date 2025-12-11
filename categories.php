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
    if (isset($_POST['add_category'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("INSERT INTO Category (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        
        if ($stmt->execute()) {
            $success = "Category added successfully!";
        } else {
            $error = "Error adding category: " . $conn->error;
        }
    } elseif (isset($_POST['update_category'])) {
        $categoryID = $_POST['categoryID'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("UPDATE Category SET name=?, description=? WHERE categoryID=?");
        $stmt->bind_param("ssi", $name, $description, $categoryID);
        
        if ($stmt->execute()) {
            $success = "Category updated successfully!";
        } else {
            $error = "Error updating category: " . $conn->error;
        }
    } elseif (isset($_POST['delete_category'])) {
        $categoryID = $_POST['categoryID'];
        
        // Check if category has products
        $check = $conn->query("SELECT COUNT(*) as count FROM Product WHERE categoryID = $categoryID");
        $row = $check->fetch_assoc();
        
        if ($row['count'] > 0) {
            // Soft delete: set is_active=0
            if ($conn->query("UPDATE Category SET is_active=0 WHERE categoryID = $categoryID")) {
                $success = "Category deactivated successfully!";
            } else {
                $error = "Error deactivating category: " . $conn->error;
            }
        } else {
            if ($conn->query("DELETE FROM Category WHERE categoryID = $categoryID")) {
                $success = "Category deleted successfully!";
            } else {
                $error = "Error deleting category: " . $conn->error;
            }
        }
    }
}

// Fetch all categories
$categories = $conn->query("SELECT c.*, COUNT(p.productID) as product_count FROM Category c LEFT JOIN Product p ON c.categoryID = p.categoryID WHERE c.is_active = 1 GROUP BY c.categoryID ORDER BY c.name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories | BrewFlow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .category-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
            border: 1px solid rgba(139,69,19,0.1);
            transition: transform 0.3s ease;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
        }
        
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .category-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, var(--accent), #E6B894);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .category-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .category-description {
            color: rgba(44,24,16,0.6);
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .category-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(139,69,19,0.1);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: rgba(44,24,16,0.6);
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
                    <h1>Categories Management</h1>
                    <p>Organize your products into categories</p>
                </div>
                <button class="btn btn-primary" onclick="openModal('addCategoryModal')">
                    <i class="fas fa-plus"></i> Add New Category
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
            
            <div class="categories-grid">
                <?php while($category = $categories->fetch_assoc()): ?>
                    <div class="category-card">
                        <div class="category-header">
                            <div class="category-icon">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button class="btn btn-sm btn-primary" onclick="editCategory(<?php echo $category['categoryID']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-error" onclick="deleteCategory(<?php echo $category['categoryID']; ?>, '<?php echo addslashes($category['name']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                        <div class="category-description">
                            <?php echo htmlspecialchars($category['description'] ?: 'No description provided'); ?>
                        </div>
                        
                        <div class="category-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $category['product_count']; ?></div>
                                <div class="stat-label">Products</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">#<?php echo $category['categoryID']; ?></div>
                                <div class="stat-label">Category ID</div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <!-- Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Category</h3>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: rgba(44,24,16,0.6);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="categoryForm" method="POST">
                    <input type="hidden" id="categoryID" name="categoryID">
                    
                    <div class="form-group">
                        <label for="name">Category Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="button" class="btn" onclick="closeModal()" style="flex: 1; background: rgba(44,24,16,0.1); color: var(--primary);">
                            Cancel
                        </button>
                        <button type="submit" name="add_category" id="submitBtn" class="btn btn-primary" style="flex: 1;">
                            Add Category
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
                <p id="deleteMessage">Are you sure you want to delete this category?</p>
                <form id="deleteForm" method="POST">
                    <input type="hidden" id="deleteCategoryID" name="categoryID">
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="button" class="btn" onclick="closeDeleteModal()" style="flex: 1; background: rgba(44,24,16,0.1); color: var(--primary);">
                            Cancel
                        </button>
                        <button type="submit" name="delete_category" class="btn btn-error" style="flex: 1;">
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('categoryModal').style.display = 'flex';
            document.getElementById('modalTitle').textContent = 'Add New Category';
            document.getElementById('submitBtn').name = 'add_category';
            document.getElementById('submitBtn').textContent = 'Add Category';
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryID').value = '';
        }
        
        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }
        
        function editCategory(id) {
            fetch('get_category.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('categoryModal').style.display = 'flex';
                    document.getElementById('modalTitle').textContent = 'Edit Category';
                    document.getElementById('submitBtn').name = 'update_category';
                    document.getElementById('submitBtn').textContent = 'Update Category';
                    
                    document.getElementById('categoryID').value = data.categoryID;
                    document.getElementById('name').value = data.name;
                    document.getElementById('description').value = data.description;
                });
        }
        
        function deleteCategory(id, name) {
            document.getElementById('deleteModal').style.display = 'flex';
            document.getElementById('deleteMessage').textContent = `Are you sure you want to delete "${name}"?`;
            document.getElementById('deleteCategoryID').value = id;
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