<?php
include 'db.php';

if (isset($_GET['id'])) {
    $orderID = intval($_GET['id']);
    
    // Get order details
    $order = $conn->query("
        SELECT o.*, 
               SUM(p.price * oi.quantity) as total,
               COUNT(oi.orderItemID) as item_count
        FROM `Order` o
        LEFT JOIN OrderItem oi ON o.orderID = oi.orderID
        LEFT JOIN Product p ON oi.productID = p.productID
        WHERE o.orderID = $orderID
        GROUP BY o.orderID
    ")->fetch_assoc();
    
    // Get order items
    $items = $conn->query("
        SELECT oi.*, p.name, p.price, p.description, c.name as category_name
        FROM OrderItem oi
        JOIN Product p ON oi.productID = p.productID
        LEFT JOIN Category c ON p.categoryID = c.categoryID
        WHERE oi.orderID = $orderID
        ORDER BY oi.orderItemID
    ");
    
    if ($order) {
    ?>
        <div class="order-details-modal">
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="color: var(--primary);">Order #<?php echo $order['orderID']; ?></h2>
                <div style="color: rgba(44,24,16,0.6);">
                    <i class="far fa-calendar"></i> 
                    <?php echo date('F j, Y, h:i A', strtotime($order['orderDate'])); ?>
                </div>
            </div>
            
            <div style="background: rgba(245,241,234,0.5); border-radius: 12px; padding: 20px; margin-bottom: 25px;">
                <h4 style="color: var(--primary); margin-bottom: 15px;">
                    <i class="fas fa-box"></i> Order Items (<?php echo $order['item_count']; ?>)
                </h4>
                
                <?php if ($items->num_rows > 0): ?>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: rgba(139,69,19,0.1);">
                                    <th style="padding: 10px; text-align: left;">Product</th>
                                    <th style="padding: 10px; text-align: center;">Quantity</th>
                                    <th style="padding: 10px; text-align: right;">Price</th>
                                    <th style="padding: 10px; text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $subtotal = 0;
                                while($item = $items->fetch_assoc()): 
                                    $itemTotal = $item['price'] * $item['quantity'];
                                    $subtotal += $itemTotal;
                                ?>
                                    <tr style="border-bottom: 1px solid rgba(139,69,19,0.1);">
                                        <td style="padding: 10px;">
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div style="font-size: 0.85rem; color: rgba(44,24,16,0.6);">
                                                <?php echo htmlspecialchars($item['category_name'] ?? 'General'); ?>
                                            </div>
                                        </td>
                                        <td style="padding: 10px; text-align: center;">
                                            <?php echo $item['quantity']; ?>
                                        </td>
                                        <td style="padding: 10px; text-align: right;">
                                            ₱<?php echo number_format($item['price'], 2); ?>
                                        </td>
                                        <td style="padding: 10px; text-align: right; font-weight: 600;">
                                            ₱<?php echo number_format($itemTotal, 2); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; color: rgba(44,24,16,0.5);">
                        <i class="fas fa-shopping-cart"></i>
                        <p>No items in this order</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="background: white; border-radius: 12px; padding: 20px; border: 1px solid rgba(139,69,19,0.1);">
                <h4 style="color: var(--primary); margin-bottom: 15px;">
                    <i class="fas fa-calculator"></i> Order Summary
                </h4>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0;">
                    <span>Subtotal:</span>
                    <span>₱<?php echo number_format($subtotal, 2); ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0;">
                    <span>Tax (10%):</span>
                    <span>₱<?php echo number_format($subtotal * 0.10, 2); ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; font-weight: 700; font-size: 1.2rem; margin-top: 15px; padding-top: 15px; border-top: 2px solid rgba(139,69,19,0.2);">
                    <span>Total:</span>
                    <span>₱<?php echo number_format($subtotal * 1.10, 2); ?></span>
                </div>
            </div>
            
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(139,69,19,0.1);">
                <div style="display: flex; gap: 15px;">
                    <button onclick="window.print()" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                    <button onclick="closeModal()" class="btn" style="flex: 1; background: rgba(44,24,16,0.1); color: var(--primary);">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    <?php
    } else {
        echo '<div style="text-align: center; padding: 40px; color: var(--error);">Order not found</div>';
    }
} else {
    echo '<div style="text-align: center; padding: 40px; color: var(--error);">No order ID specified</div>';
}
?>