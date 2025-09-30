<?php
// Get current page for active navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

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
        <div class="nav-brand">
            <i class="fas fa-cash-register"></i>
            <span>Point of Sale</span>
        </div>
        <div class="nav-menu">
            <a href="index.php" class="nav-link <?= ($currentPage == 'index') ? 'active' : '' ?>">
                <i class="fas fa-box"></i>
                Products
            </a>
            <a href="sales.php" class="nav-link <?= ($currentPage == 'sales') ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart"></i>
                Sales
            </a>
            <a href="customer/customer.php" class="nav-link <?= ($currentPage == 'customers') ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                Customers
            </a>
            <a href="reports.php" class="nav-link <?= ($currentPage == 'reports') ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                Reports
            </a>
        </div>
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
    </div>
</nav>
