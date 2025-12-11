<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Include TCPDF library
require_once('tcpdf/tcpdf.php');

$start_date = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end'] ?? date('Y-m-d');

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('BrewFlow Coffee Shop');
$pdf->SetAuthor('Inventory System');
$pdf->SetTitle('Sales Report');
$pdf->SetSubject('Sales Report PDF');
$pdf->SetKeywords('Sales, Report, PDF, Coffee, Inventory');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(15, 15, 15);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 15);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 16);

// Title
$pdf->Cell(0, 10, 'BrewFlow Coffee Shop - Sales Report', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Period: ' . date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date)), 0, 1, 'C');
$pdf->Cell(0, 5, 'Generated on: ' . date('F j, Y, g:i a'), 0, 1, 'C');

$pdf->Ln(10);

// Get summary data
$summary = $conn->query("
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

// Key Metrics
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Key Metrics', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

$metrics = [
    ['Total Revenue', '$' . number_format($summary['total_revenue'] ?? 0, 2)],
    ['Total Orders', $summary['total_orders'] ?? 0],
    ['Items Sold', $summary['total_items_sold'] ?? 0],
    ['Average Order Value', '$' . number_format($summary['avg_order_value'] ?? 0, 2)]
];

foreach ($metrics as $metric) {
    $pdf->Cell(90, 7, $metric[0] . ':', 0, 0, 'L');
    $pdf->Cell(0, 7, $metric[1], 0, 1, 'R');
}

$pdf->Ln(10);

// Get sales data
$sales_data = $conn->query("
    SELECT 
        DATE(o.orderDate) as sale_date,
        COUNT(DISTINCT o.orderID) as total_orders,
        SUM(oi.quantity) as total_items,
        SUM(oi.quantity * p.price) as total_revenue
    FROM `Order` o
    JOIN OrderItem oi ON o.orderID = oi.orderID
    JOIN Product p ON oi.productID = p.productID
    WHERE o.orderDate BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(o.orderDate)
    ORDER BY sale_date DESC
    LIMIT 30
");

if ($sales_data->num_rows > 0) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Daily Sales Report', 0, 1, 'L');
    
    // Table header
    $pdf->SetFillColor(139, 69, 19);
    $pdf->SetTextColor(255);
    $pdf->SetFont('helvetica', 'B', 10);
    
    $header = array('Date', 'Orders', 'Items Sold', 'Revenue');
    $w = array(40, 35, 40, 45);
    
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    // Table data
    $pdf->SetFillColor(255, 245, 238);
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', '', 9);
    
    $fill = false;
    $total_items = 0;
    $total_revenue = 0;
    
    while($row = $sales_data->fetch_assoc()) {
        $pdf->Cell($w[0], 6, date('M d, Y', strtotime($row['sale_date'])), 'LR', 0, 'L', $fill);
        $pdf->Cell($w[1], 6, $row['total_orders'], 'LR', 0, 'C', $fill);
        $pdf->Cell($w[2], 6, $row['total_items'], 'LR', 0, 'C', $fill);
        $pdf->Cell($w[3], 6, '$' . number_format($row['total_revenue'], 2), 'LR', 0, 'R', $fill);
        $pdf->Ln();
        
        $fill = !$fill;
        $total_items += $row['total_items'];
        $total_revenue += $row['total_revenue'];
    }
    
    // Closing line
    $pdf->Cell(array_sum($w), 0, '', 'T');
    
    $pdf->Ln(8);
    
    // Summary
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(115, 6, 'TOTAL (' . $sales_data->num_rows . ' days):', 0, 0, 'R');
    $pdf->Cell(20, 6, $total_items . ' items', 0, 0, 'C');
    $pdf->Cell(25, 6, '$' . number_format($total_revenue, 2), 0, 1, 'R');
}

$pdf->Ln(10);

// Top Products
$top_products = $conn->query("
    SELECT 
        p.name,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * p.price) as revenue
    FROM OrderItem oi
    JOIN Product p ON oi.productID = p.productID
    JOIN `Order` o ON oi.orderID = o.orderID
    WHERE o.orderDate BETWEEN '$start_date' AND '$end_date'
    AND p.is_deleted = 0
    GROUP BY p.productID
    ORDER BY total_sold DESC
    LIMIT 10
");

if ($top_products->num_rows > 0) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Top 10 Products', 0, 1, 'L');
    
    $pdf->SetFillColor(93, 139, 102);
    $pdf->SetTextColor(255);
    $pdf->SetFont('helvetica', 'B', 10);
    
    $header = array('Product', 'Units Sold', 'Revenue');
    $w = array(100, 40, 40);
    
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    $pdf->SetFillColor(232, 245, 233);
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', '', 9);
    
    $fill = false;
    
    while($row = $top_products->fetch_assoc()) {
        // Truncate long product names
        $product_name = strlen($row['name']) > 40 ? substr($row['name'], 0, 37) . '...' : $row['name'];
        
        $pdf->Cell($w[0], 6, $product_name, 'LR', 0, 'L', $fill);
        $pdf->Cell($w[1], 6, $row['total_sold'], 'LR', 0, 'C', $fill);
        $pdf->Cell($w[2], 6, '$' . number_format($row['revenue'], 2), 'LR', 0, 'R', $fill);
        $pdf->Ln();
        
        $fill = !$fill;
    }
    
    $pdf->Cell(array_sum($w), 0, '', 'T');
}

$pdf->Ln(10);

// Footer note
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(128);
$pdf->MultiCell(0, 10, 'This is an automatically generated report. For questions, contact the administrator.', 0, 'C');

// Close and output PDF
$pdf->Output('sales_report_' . date('Y-m-d') . '.pdf', 'D');
?>