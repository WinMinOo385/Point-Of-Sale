<?php
require_once '../includes/db_connection.php';

// Check if sale ID is provided
if (!isset($_GET['sale_id']) || !is_numeric($_GET['sale_id'])) {
    die('Invalid sale ID');
}

$saleId = (int)$_GET['sale_id'];

try {
    // Get sale information
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as customer_name, c.email, c.phone 
        FROM sales s 
        JOIN customers c ON s.cid = c.cid 
        WHERE s.sid = ?
    ");
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        die('Sale not found');
    }
    
    // Get sale items
    $stmt = $pdo->prepare("
        SELECT si.*, p.name as product_name, p.price as unit_price
        FROM sale_items si
        JOIN products p ON si.pid = p.pid
        WHERE si.sid = ?
    ");
    $stmt->execute([$saleId]);
    $saleItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Company information
    $companyInfo = [
        'name' => 'Point of Sale System',
        'address' => "123 Business Street\nCity, State 12345",
        'phone' => '(555) 123-4567',
        'email' => 'info@possystem.com'
    ];
    
    // Calculate totals
    $subtotal = array_sum(array_map(function($item) {
        return $item['total_price'];
    }, $saleItems));
    $tax = $subtotal * 0.1;
    $total = $sale['total_amount'];
    
    // Generate HTML invoice
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice #INV-' . str_pad($sale['sid'], 6, '0', STR_PAD_LEFT) . '</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #A3C4F3;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .company-info {
            color: #6c757d;
            font-size: 11px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #A3C4F3;
            margin: 20px 0;
        }
        .invoice-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .invoice-details, .customer-details {
            flex: 1;
        }
        .invoice-details {
            margin-right: 20px;
        }
        .section-title {
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 1px solid #E6EBE0;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background-color: #A3C4F3;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #E6EBE0;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-section {
            margin-top: 20px;
            border-top: 2px solid #A3C4F3;
            padding-top: 15px;
        }
        .totals-table {
            width: 300px;
            margin-left: auto;
        }
        .totals-table td {
            padding: 5px 10px;
        }
        .total-final {
            font-size: 16px;
            font-weight: bold;
            background-color: #A3C4F3;
            color: white;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            border-top: 1px solid #E6EBE0;
            padding-top: 20px;
        }
        .thank-you {
            font-size: 18px;
            font-weight: bold;
            color: #A3C4F3;
            margin-bottom: 15px;
        }
        .footer-text {
            color: #6c757d;
            font-size: 11px;
            line-height: 1.6;
        }
        .print-button {
            background-color: #A3C4F3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 20px 0;
            font-size: 14px;
        }
        .print-button:hover {
            background-color: #85a0c7;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Print Invoice</button>
        <button class="print-button" onclick="window.close()" style="background-color: #6c757d;">✖️ Close</button>
    </div>
    
    <div class="header">
        <div class="company-name">' . htmlspecialchars($companyInfo['name']) . '</div>
        <div class="company-info">
            ' . nl2br(htmlspecialchars($companyInfo['address'])) . '<br>
            Email: ' . htmlspecialchars($companyInfo['email']) . ' | Phone: ' . htmlspecialchars($companyInfo['phone']) . '
        </div>
    </div>
    
    <div class="invoice-title text-center">INVOICE</div>
    
    <div class="invoice-meta">
        <div class="invoice-details">
            <div class="section-title">Invoice Details</div>
            <strong>Invoice #:</strong> INV-' . str_pad($sale['sid'], 6, '0', STR_PAD_LEFT) . '<br>
            <strong>Date:</strong> ' . date('F j, Y', strtotime($sale['sale_date'])) . '<br>
            <strong>Time:</strong> ' . date('g:i A', strtotime($sale['sale_date'])) . '<br>
            <strong>Payment Status:</strong> <span style="color: #28a745; font-weight: bold;">PAID</span>
        </div>
        
        <div class="customer-details">
            <div class="section-title">Bill To</div>
            <strong>' . htmlspecialchars($sale['customer_name']) . '</strong><br>';
    
    if ($sale['email']) {
        $html .= 'Email: ' . htmlspecialchars($sale['email']) . '<br>';
    }
    if ($sale['phone']) {
        $html .= 'Phone: ' . htmlspecialchars($sale['phone']) . '<br>';
    }
    
    $html .= '        </div>
    </div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th>Item Description</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($saleItems as $item) {
        $html .= '<tr>
                <td>' . htmlspecialchars($item['product_name']) . '</td>
                <td class="text-center">' . $item['quantity'] . '</td>
                <td class="text-right">$' . number_format($item['unit_price'], 2) . '</td>
                <td class="text-right">$' . number_format($item['total_price'], 2) . '</td>
            </tr>';
    }
    
    $html .= '        </tbody>
    </table>
    
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td class="text-right">$' . number_format($subtotal, 2) . '</td>
            </tr>
            <tr>
                <td><strong>Tax (10%):</strong></td>
                <td class="text-right">$' . number_format($tax, 2) . '</td>
            </tr>
            <tr class="total-final">
                <td><strong>TOTAL:</strong></td>
                <td class="text-right"><strong>$' . number_format($total, 2) . '</strong></td>
            </tr>
        </table>
    </div>
    
    <div class="footer">
        <div class="thank-you">THANK YOU FOR YOUR BUSINESS!</div>
        <div class="footer-text">
            We appreciate your purchase and look forward to serving you again.<br>
            For questions about this invoice, please contact us at:<br>
            ' . htmlspecialchars($companyInfo['email']) . ' | ' . htmlspecialchars($companyInfo['phone']) . '<br><br>
            <strong>Have a wonderful day!</strong>
        </div>
    </div>
    
    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>';
    
    echo $html;
    
} catch (Exception $e) {
    die('Error generating invoice: ' . $e->getMessage());
}
?>