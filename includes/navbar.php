<?php
// Get current page for active navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
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
        <div class="nav-user">
            <i class="fas fa-user-circle"></i>
            <span>Admin</span>
        </div>
    </div>
</nav>
