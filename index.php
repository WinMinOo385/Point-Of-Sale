<?php
include 'includes/db_connection.php';

// Fetch products - in stock first, then out of stock
$stmt = $pdo->query("SELECT * FROM products ORDER BY CASE WHEN stock > 0 THEN 0 ELSE 1 END, name");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$pageTitle = "POS - Product List";
include 'includes/header.php';
?>
    <?php include 'includes/navbar.php'; ?>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Product Grid -->
        <main class="product-section">
            <div class="section-header">
                <h1>Product Catalog</h1>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search products...">
                </div>
            </div>
            
            <div class="product-grid" id="productGrid">
                <?php foreach ($products as $product): ?>
                <div class="product-card" data-name="<?= htmlspecialchars($product['name']) ?>">
                    <div class="product-image">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="product-price">$<?= number_format($product['price'], 2) ?></p>
                        <p class="product-stock">Stock: <?= $product['stock'] ?></p>
                    </div>
                    <div class="product-actions">
                        <div class="quantity-controls">
                            <button class="qty-btn" onclick="decreaseQuantity(<?= $product['pid'] ?>)">-</button>
                            <input type="number" id="qty-<?= $product['pid'] ?>" value="1" min="1" max="<?= $product['stock'] ?>">
                            <button class="qty-btn" onclick="increaseQuantity(<?= $product['pid'] ?>)">+</button>
                        </div>
                        <?php if ($product['stock'] > 0): ?>
                            <button class="add-to-cart-btn" onclick="addToCart(<?= $product['pid'] ?>, '<?= htmlspecialchars($product['name']) ?>', <?= $product['price'] ?>, <?= $product['stock'] ?>)">
                                <i class="fas fa-cart-plus"></i>
                                Add to Cart
                            </button>
                        <?php else: ?>
                            <button class="add-to-cart-btn out-of-stock" disabled>
                                <i class="fas fa-times-circle"></i>
                                Out of Stock
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>

        <!-- Cart Sidebar -->
        <aside class="cart-sidebar">
            <div class="cart-header">
                <h2><i class="fas fa-shopping-cart"></i> Shopping Cart</h2>
                <button class="clear-cart-btn" onclick="clearCart()">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            
            <div class="cart-items" id="cartItems">
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty</p>
                </div>
            </div>
            
            <div class="cart-footer">
                <div class="cart-total">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span id="subtotal">$0.00</span>
                    </div>
                    <div class="total-row">
                        <span>Tax (10%):</span>
                        <span id="tax">$0.00</span>
                    </div>
                    <div class="total-row total-final">
                        <span>Total:</span>
                        <span id="total">$0.00</span>
                    </div>
                </div>
                <button class="checkout-btn" onclick="checkout()">
                    <i class="fas fa-credit-card"></i>
                    Checkout
                </button>
            </div>
        </aside>
    </div>

<?php include 'includes/footer.php'; ?>