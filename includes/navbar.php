<?php
// Determine base path for links if not set by the page
$basePath = isset($basePath) ? $basePath : '';

// Determine active section based on the current script path
$script = $_SERVER['SCRIPT_NAME'];
if (strpos($script, '/sale/') !== false) {
    $active = 'sale';
} elseif (strpos($script, '/customer/') !== false) {
    $active = 'customer';
} elseif (strpos($script, '/reporting/') !== false) {
    $active = 'reporting';
} elseif (strpos($script, '/stock/') !== false || strpos($script, 'stock.php') !== false) {
    $active = 'stock';
} elseif (strpos($script, 'index.php') !== false) {
    $active = 'index';
} else {
    $active = 'index';
}

// Get customers for selection dropdown
if (isset($pdo)) {
    $stmt = $pdo->query("SELECT cid, name FROM customers ORDER BY name");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $customers = [];
}
?>
<!-- Navigation Bar -->
<nav class="navbar">
    <div class="nav-container">
        <a href="<?= $basePath ?>index.php" class="nav-brand" style="text-decoration: none; color: inherit;">
            <i class="fas fa-cash-register"></i>
            <span>Point of Sale</span>
        </a>
        <div class="nav-menu">
            <a href="<?= $basePath ?>stock/stock.php" class="nav-link <?= ($active == 'stock') ? 'active' : '' ?>">
                <i class="fas fa-box"></i>
                Products
            </a>
            <a href="<?= $basePath ?>sale/sale.php" class="nav-link <?= ($active == 'sale') ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart"></i>
                Sales
            </a>
            <a href="<?= $basePath ?>customer/customer.php" class="nav-link <?= ($active == 'customer') ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                Customers
            </a>
            <a href="<?= $basePath ?>reporting/reporting.php" class="nav-link <?= ($active == 'reporting') ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                Reports
            </a>
        </div>
        <?php if ($active == 'index'): ?>
        <div class="nav-customer">
            <i class="fas fa-user"></i>
            <label for="customerSelect">Customer:</label>
            <select id="customerSelect" name="customer_id" onchange="updateSelectedCustomer(this.value)">
                <option value="">Select Customer</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= $customer['cid'] ?>"><?= htmlspecialchars($customer['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>
</nav>