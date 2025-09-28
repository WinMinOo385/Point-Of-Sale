<?php
<<<<<<< HEAD
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Point Of Sale - Main Menu</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			background-color: #E6EBE0;
			color: #E6EBE0;
			line-height: 1.6;
			min-height: 100vh;
			display: flex;
			flex-direction: column;
		}

		.app-header {
			background-color: #A3C4F3;
			padding: 30px;
			text-align: center;
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
		}

		.app-header h1 {
			color: #E6EBE0;
			font-size: 3rem;
			margin-bottom: 10px;
		}

		.app-main {
			flex: 1;
			display: flex;
			justify-content: center;
			align-items: center;
			padding: 40px 20px;
		}

		.card {
			background-color: #A3C4F3;
			padding: 40px;
			border-radius: 15px;
			box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
			text-align: center;
			max-width: 600px;
			width: 100%;
		}

		.card h2 {
			color: #E6EBE0;
			font-size: 2.5rem;
			margin-bottom: 20px;
		}

		.card p {
			color: #E6EBE0;
			font-size: 1.2rem;
			margin-bottom: 30px;
			opacity: 0.9;
		}

		.actions {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 20px;
			margin-top: 30px;
		}

		.btn {
			background-color: #85a0c7;
			color: #E6EBE0;
			padding: 20px 30px;
			text-decoration: none;
			border-radius: 10px;
			font-size: 1.2rem;
			font-weight: 600;
			transition: all 0.3s ease;
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 10px;
		}

		.btn:hover {
			background-color: #6d8bb3;
			transform: translateY(-3px);
			box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
		}

		.btn-icon {
			font-size: 2rem;
		}

		.btn-text {
			font-size: 1.1rem;
		}

		.app-footer {
			background-color: #A3C4F3;
			padding: 20px;
			text-align: center;
			box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
		}

		.app-footer small {
			color: #E6EBE0;
			font-size: 1rem;
		}

		.setup-section {
			margin-top: 30px;
			padding-top: 30px;
			border-top: 2px solid rgba(133, 160, 199, 0.3);
		}

		.setup-section h3 {
			color: #E6EBE0;
			font-size: 1.5rem;
			margin-bottom: 15px;
		}

		.setup-section p {
			color: #E6EBE0;
			font-size: 1rem;
			margin-bottom: 20px;
			opacity: 0.8;
		}

		.btn-setup {
			background-color: #4CAF50;
			font-size: 1rem;
			padding: 15px 25px;
		}

		.btn-setup:hover {
			background-color: #45a049;
		}

		@media (max-width: 768px) {
			.app-header h1 {
				font-size: 2.5rem;
			}
			
			.card {
				padding: 30px 20px;
			}
			
			.card h2 {
				font-size: 2rem;
			}
			
			.actions {
				grid-template-columns: 1fr;
			}
		}
	</style>
</head>
<body>
	<header class="app-header">
		<h1>Point Of Sale System</h1>
	</header>
	<main class="app-main">
		<section class="card">
			<h2>Welcome to POS</h2>
			<p>Choose an option below to manage your point of sale system</p>
			<nav class="actions">
				<a class="btn" href="customer/customer.php">
					<span class="btn-icon">👥</span>
					<span class="btn-text">Customer Management</span>
				</a>
				<a class="btn" href="sale/sale.php">
					<span class="btn-icon">🛒</span>
					<span class="btn-text">Sales Management</span>
				</a>
			</nav>
			
			<div class="setup-section">
				<h3>System Setup</h3>
				<p>Run this first to set up your database and initial data</p>
				<nav class="actions">
					<a class="btn btn-setup" href="utility/setup.php">
						<span class="btn-icon">⚙️</span>
						<span class="btn-text">Run Setup</span>
					</a>
				</nav>
			</div>
		</section>
	</main>
	<footer class="app-footer">
		<small>&copy; <?php echo date('Y'); ?> POS System</small>
	</footer>
</body>
</html>
=======
include 'includes/db_connection.php';
>>>>>>> 6f0589739f37f5f4e046f5035faeec29754b8431

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