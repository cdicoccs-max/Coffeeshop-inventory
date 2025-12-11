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
    if (isset($_POST['add_supplier'])) {
        $name = $_POST['name'];
        $contactinfo = $_POST['contactinfo'];
        
        $stmt = $conn->prepare("INSERT INTO Supplier (name, contactinfo) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $contactinfo);
        
        if ($stmt->execute()) {
            $success = "Supplier added successfully!";
        } else {
            $error = "Error adding supplier: " . $conn->error;
        }
    } elseif (isset($_POST['update_supplier'])) {
        $supplierID = $_POST['supplierID'];
        $name = $_POST['name'];
        $contactinfo = $_POST['contactinfo'];
        
        $stmt = $conn->prepare("UPDATE Supplier SET name=?, contactinfo=? WHERE supplierID=?");
        $stmt->bind_param("ssi", $name, $contactinfo, $supplierID);
        
        if ($stmt->execute()) {
            $success = "Supplier updated successfully!";
        } else {
            $error = "Error updating supplier: " . $conn->error;
        }
    } elseif (isset($_POST['delete_supplier'])) {
        $supplierID = $_POST['supplierID'];
        // Check if supplier has products
        $check = $conn->query("SELECT COUNT(*) as count FROM Product WHERE supplierID = $supplierID");
        $row = $check->fetch_assoc();
        if ($row['count'] > 0) {
            // Soft delete: set is_active=0
            if ($conn->query("UPDATE Supplier SET is_active=0 WHERE supplierID = $supplierID")) {
                $success = "Supplier deactivated successfully!";
            } else {
                $error = "Error deactivating supplier: " . $conn->error;
            }
        } else {
            if ($conn->query("DELETE FROM Supplier WHERE supplierID = $supplierID")) {
                $success = "Supplier deleted successfully!";
            } else {
                $error = "Error deleting supplier: " . $conn->error;
            }
        }
    }
}

// Fetch all suppliers with product count
$suppliers = $conn->query("SELECT s.*, COUNT(p.productID) as product_count FROM Supplier s LEFT JOIN Product p ON s.supplierID = p.supplierID WHERE s.is_active = 1 GROUP BY s.supplierID ORDER BY s.name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers | BrewFlow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .supplier-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
            border: 1px solid rgba(139,69,19,0.1);
            transition: transform 0.3s ease;
        }
        
        .supplier-card:hover {
            transform: translateY(-3px);
        }
        
        .supplier-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .supplier-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .supplier-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #4facfe, #00f2fe);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .supplier-details h3 {
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .supplier-contact {
            color: rgba(44,24,16,0.6);
            font-size: 0.95rem;
        }
        
        .supplier-stats {
            display: flex;
            gap: 30px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(139,69,19,0.1);
        }
        
        .stat {
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
        
        .action-buttons {
            display: flex;
            gap: 10px;
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
                    <h1>Suppliers Management</h1>
                    <p>Manage your coffee shop suppliers and partners</p>
                </div>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i> Add New Supplier
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
            
            <div class="card">
                <h3 class="section-title">
                    <i class="fas fa-truck"></i>
                    All Suppliers
                </h3>
                
                <div>
                    <?php while($supplier = $suppliers->fetch_assoc()): ?>
                        <div class="supplier-card">
                            <div class="supplier-header">
                                <div class="supplier-info">
                                    <div class="supplier-icon">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="supplier-details">
                                        <h3><?php echo htmlspecialchars($supplier['name']); ?></h3>
                                        <div class="supplier-contact">
                                            <i class="fas fa-phone"></i> 
                                            <?php echo htmlspecialchars($supplier['contactinfo'] ?: 'No contact info'); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="editSupplier(<?php echo $supplier['supplierID']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-error" onclick="deleteSupplier(<?php echo $supplier['supplierID']; ?>, '<?php echo addslashes($supplier['name']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                            
                            <div class="supplier-stats">
                                <div class="stat">
                                    <div class="stat-value">#<?php echo $supplier['supplierID']; ?></div>
                                    <div class="stat-label">Supplier ID</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value"><?php echo $supplier['product_count']; ?></div>
                                    <div class="stat-label">Products</div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Supplier Modal -->
    <div id="supplierModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Supplier</h3>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: rgba(44,24,16,0.6);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="supplierForm" method="POST">
                    <input type="hidden" id="supplierID" name="supplierID">
                    
                    <div class="form-group">
                        <label for="name">Supplier Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contactinfo">Contact Information</label>
                        <textarea id="contactinfo" name="contactinfo" rows="3" placeholder="Phone, email, address..."></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="button" class="btn" onclick="closeModal()" style="flex: 1; background: rgba(44,24,16,0.1); color: var(--primary);">
                            Cancel
                        </button>
                        <button type="submit" name="add_supplier" id="submitBtn" class="btn btn-primary" style="flex: 1;">
                            Add Supplier
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
                <p id="deleteMessage">Are you sure you want to delete this supplier?</p>
                <form id="deleteForm" method="POST">
                    <input type="hidden" id="deleteSupplierID" name="supplierID">
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="button" class="btn" onclick="closeDeleteModal()" style="flex: 1; background: rgba(44,24,16,0.1); color: var(--primary);">
                            Cancel
                        </button>
                        <button type="submit" name="delete_supplier" class="btn btn-error" style="flex: 1;">
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('supplierModal').style.display = 'flex';
            document.getElementById('modalTitle').textContent = 'Add New Supplier';
            document.getElementById('submitBtn').name = 'add_supplier';
            document.getElementById('submitBtn').textContent = 'Add Supplier';
            document.getElementById('supplierForm').reset();
            document.getElementById('supplierID').value = '';
        }
        
        function closeModal() {
            document.getElementById('supplierModal').style.display = 'none';
        }
        
        function editSupplier(id) {
            fetch('get_supplier.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('supplierModal').style.display = 'flex';
                    document.getElementById('modalTitle').textContent = 'Edit Supplier';
                    document.getElementById('submitBtn').name = 'update_supplier';
                    document.getElementById('submitBtn').textContent = 'Update Supplier';
                    
                    document.getElementById('supplierID').value = data.supplierID;
                    document.getElementById('name').value = data.name;
                    document.getElementById('contactinfo').value = data.contactinfo;
                });
        }
        
        function deleteSupplier(id, name) {
            document.getElementById('deleteModal').style.display = 'flex';
            document.getElementById('deleteMessage').textContent = `Are you sure you want to delete "${name}"?`;
            document.getElementById('deleteSupplierID').value = id;
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