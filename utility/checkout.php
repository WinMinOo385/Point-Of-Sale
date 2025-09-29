<?php
header('Content-Type: application/json');
include '../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['cart']) || empty($input['cart'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid cart data']);
    exit;
}

$cart = $input['cart'];
$customerId = $input['customer_id'] ?? 1; // Default customer ID

try {
    // Start database transaction for data integrity
    $pdo->beginTransaction();
    
    // First, validate stock availability for all items
    foreach ($cart as $item) {
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE pid = ?");
        $stmt->execute([$item['pid']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception("Product with ID {$item['pid']} not found");
        }
        
        if ($product['stock'] < $item['quantity']) {
            throw new Exception("Insufficient stock for product ID {$item['pid']}. Available: {$product['stock']}, Requested: {$item['quantity']}");
        }
    }
    
    // Calculate totals
    $subtotal = 0;
    foreach ($cart as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $tax = $subtotal * 0.1; // 10% tax
    $total = $subtotal + $tax;
    
    // Create sale record in database
    $stmt = $pdo->prepare("INSERT INTO sales (cid, total_amount) VALUES (?, ?)");
    $stmt->execute([$customerId, $total]);
    $saleId = $pdo->lastInsertId();
    
    // Process each cart item - REAL DATABASE OPERATIONS
    foreach ($cart as $item) {
        // Insert sale item record
        $stmt = $pdo->prepare("INSERT INTO sale_items (sid, pid, quantity, total_price) VALUES (?, ?, ?, ?)");
        $itemTotal = $item['price'] * $item['quantity'];
        $stmt->execute([$saleId, $item['pid'], $item['quantity'], $itemTotal]);
        
        // DECREASE PRODUCT STOCK IN DATABASE - THIS IS REAL!
        $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE pid = ?");
        $stmt->execute([$item['quantity'], $item['pid']]);
    }
    
    // Commit all changes to database
    $pdo->commit();
    
    // Get updated product stocks to send back to frontend
    $updatedProducts = [];
    foreach ($cart as $item) {
        $stmt = $pdo->prepare("SELECT pid, stock FROM products WHERE pid = ?");
        $stmt->execute([$item['pid']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        $updatedProducts[] = $product;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Checkout successful - Stock quantities updated in database',
        'sale_id' => $saleId,
        'total' => $total,
        'updated_products' => $updatedProducts
    ]);
    
} catch (Exception $e) {
    // Rollback all changes if any error occurs
    $pdo->rollBack();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>