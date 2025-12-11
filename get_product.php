<?php
include 'db.php';
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $conn->query("SELECT p.*, c.name as category_name, s.name as supplier_name, i.quantity FROM Product p LEFT JOIN Category c ON p.categoryID = c.categoryID LEFT JOIN Supplier s ON p.supplierID = s.supplierID LEFT JOIN InventoryItem i ON p.productID = i.productID WHERE p.productID = $id");
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Product not found']);
    }
}
?>