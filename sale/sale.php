<?php
// Database connection (reusable)
include '../includes/db_connection.php';

// Handle form submissions
$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'clear_history') {
        try {
            $pdo->beginTransaction();
            
            // Delete all sale items first (due to foreign key constraints)
            $pdo->exec("DELETE FROM sale_items");
            
            // Delete all sales
            $pdo->exec("DELETE FROM sales");
            
            // Reset auto increment counters
            $pdo->exec("ALTER TABLE sales AUTO_INCREMENT = 1");
            $pdo->exec("ALTER TABLE sale_items AUTO_INCREMENT = 1");
            
            $pdo->commit();
            $message = "Sales history cleared successfully!";
            $messageType = "success";
            
        } catch (Exception $e) {
            // Only rollback if transaction is still active
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "Error clearing sales history: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Get filter parameters
$startDate = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] : null;
$filterCustomer = isset($_GET['customer_id']) && $_GET['customer_id'] !== '' ? (int)$_GET['customer_id'] : null;

// Build query with filters
$whereConditions = [];
$params = [];

if ($startDate) {
    $whereConditions[] = "s.sale_date >= :start_date";
    $params[':start_date'] = $startDate . ' 00:00:00';
}
if ($endDate) {
    $whereConditions[] = "s.sale_date <= :end_date";
    $params[':end_date'] = $endDate . ' 23:59:59';
}
if ($filterCustomer) {
    $whereConditions[] = "s.cid = :customer_id";
    $params[':customer_id'] = $filterCustomer;
}

$whereClause = count($whereConditions) > 0 ? (' WHERE ' . implode(' AND ', $whereConditions)) : '';

// Fetch sales history with filters
$sql = "
    SELECT 
        s.sid,
        s.sale_date,
        s.total_amount,
        c.name as customer_name,
        c.cid,
        GROUP_CONCAT(
            CONCAT(p.name, ' (Qty: ', si.quantity, ', Price: $', si.total_price, ')')
            SEPARATOR ', '
        ) as products_purchased
    FROM sales s
    JOIN customers c ON s.cid = c.cid
    JOIN sale_items si ON s.sid = si.sid
    JOIN products p ON si.pid = p.pid
    $whereClause
    GROUP BY s.sid, s.sale_date, s.total_amount, c.name, c.cid
    ORDER BY s.sale_date DESC
";

$sales_history_stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $sales_history_stmt->bindValue($key, $value);
}
$sales_history_stmt->execute();
$sales_history = $sales_history_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all customers for filter dropdown
$customers_stmt = $pdo->query("SELECT cid, name FROM customers ORDER BY name");
$all_customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$pageTitle = "Sales History - POS System";
$basePath = '../';
include '../includes/header.php';
?>
<?php include '../includes/navbar.php'; ?>

<style>

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .header {
        background-color: #A3C4F3;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .header h1 {
        color: #E6EBE0;
        font-size: 2.5rem;
        margin-bottom: 10px;
    }

    .header p {
        color: #E6EBE0;
        font-size: 1.1rem;
        opacity: 0.9;
    }

    .filters-section {
        background-color: #A3C4F3;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .filters-section h3 {
        color: #E6EBE0;
        margin-bottom: 15px;
        font-size: 1.3rem;
    }

    .filters-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-group label {
        color: #E6EBE0;
        font-weight: 600;
        font-size: 14px;
    }

    .filter-group input,
    .filter-group select {
        padding: 10px 12px;
        border: none;
        border-radius: 5px;
        background-color: rgba(255, 255, 255, 0.9);
        color: #333;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .filter-group input:focus,
    .filter-group select:focus {
        outline: none;
        background-color: white;
        box-shadow: 0 0 10px rgba(163, 196, 243, 0.5);
    }

    .filter-buttons {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    .history-container {
        background-color: #A3C4F3;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

        .history-container h2 {
            color: #E6EBE0;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .history-item {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            color: #333;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .history-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #85a0c7;
        }

        .invoice-code {
            background-color: #85a0c7;
            color: #E6EBE0;
            padding: 6px 12px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.9rem;
            letter-spacing: 1px;
            margin-right: 10px;
            display: inline-block;
        }

        .sale-id {
            font-weight: bold;
            color: #85a0c7;
            font-size: 1.2rem;
        }

        .customer-name {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
        }

        .total-amount-history {
            color: #4CAF50;
            font-weight: bold;
            font-size: 1.3rem;
        }

        .products-list {
            margin-top: 10px;
            padding: 15px;
            background-color: rgba(163, 196, 243, 0.1);
            border-radius: 5px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .products-list strong {
            color: #85a0c7;
            display: block;
            margin-bottom: 8px;
        }

        .no-sales {
            text-align: center;
            padding: 60px 40px;
            color: #666;
            font-style: italic;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
        }

        .no-sales h3 {
            color: #85a0c7;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .no-sales p {
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            color: #333;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .message.success {
            background-color: #4CAF50;
            color: white;
            border-left: 5px solid #45a049;
        }

        .message.error {
            background-color: #f44336;
            color: white;
            border-left: 5px solid #d32f2f;
        }

        .btn {
            background-color: #85a0c7;
            color: #E6EBE0;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background-color: #6d8bb3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-danger {
            background-color: #f44336;
        }

        .btn-danger:hover {
            background-color: #d32f2f;
        }

        .clear-history-section {
            background-color: #A3C4F3;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .clear-history-section h3 {
            color: #E6EBE0;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .clear-history-section p {
            color: #E6EBE0;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .history-container {
                padding: 20px;
            }
            
            .history-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .nav-links {
                flex-direction: column;
            }

            .nav-link {
                justify-content: center;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }
    }
</style>

<div class="container">
    <div class="header">
        <h1>Sales History</h1>
        <p>View all sales transactions and customer purchases</p>
    </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Filters Section -->
        <div class="filters-section">
            <h3>🔍 Filter Sales</h3>
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label for="customer_id">Customer</label>
                    <select id="customer_id" name="customer_id">
                        <option value="">All Customers</option>
                        <?php foreach ($all_customers as $customer): ?>
                            <option value="<?php echo $customer['cid']; ?>" <?php echo ($filterCustomer == $customer['cid']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($customer['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn">Apply Filters</button>
                    <a href="sale.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <?php if (count($sales_history) > 0): ?>
            <!-- Sales Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($sales_history); ?></div>
                    <div class="stat-label">Total Sales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format(array_sum(array_column($sales_history, 'total_amount')), 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo count($sales_history) > 0 ? number_format(array_sum(array_column($sales_history, 'total_amount')) / count($sales_history), 2) : '0.00'; ?></div>
                    <div class="stat-label">Average Sale</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Sales History -->
        <div class="history-container">
            <h2>Sales Transactions</h2>
            <?php if (count($sales_history) > 0): ?>
                <?php foreach ($sales_history as $sale): ?>
                    <div class="history-item">
                        <div class="history-header">
                            <div>
                                <span class="invoice-code">INV-<?php echo str_pad($sale['sid'], 6, '0', STR_PAD_LEFT); ?></span>
                                <span class="sale-id">Sale #<?php echo $sale['sid']; ?></span>
                                <span class="customer-name"> - <?php echo htmlspecialchars($sale['customer_name']); ?></span>
                            </div>
                            <div>
                                <div class="sale-date"><?php echo date('M d, Y H:i', strtotime($sale['sale_date'])); ?></div>
                                <div class="total-amount-history">$<?php echo number_format($sale['total_amount'], 2); ?></div>
                            </div>
                        </div>
                        <div class="products-list">
                            <strong>Products Purchased:</strong>
                            <?php echo htmlspecialchars($sale['products_purchased']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-sales">
                    <h3>No Sales Recorded</h3>
                    <p>There are no sales transactions in the system yet.<br>
                    Sales will appear here once they are recorded through the system.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Clear History Section -->
        <div class="clear-history-section">
            <h3>🗑️ Clear Sales History</h3>
            <p>This action will permanently delete all sales records and cannot be undone.</p>
            <form method="POST" onsubmit="return confirmClearHistory()">
                <input type="hidden" name="action" value="clear_history">
                <button type="submit" class="btn btn-danger">
                    🗑️ Clear All Sales History
                </button>
            </form>
        </div>
    </div>

    <script>
        // Confirmation function for clearing history
        function confirmClearHistory() {
            const salesCount = <?php echo count($sales_history); ?>;
            
            if (salesCount === 0) {
                alert('There are no sales to clear.');
                return false;
            }
            
            const confirmMessage = `Are you sure you want to clear ALL sales history?\n\nThis will permanently delete ${salesCount} sales record(s) and cannot be undone.\n\nType "DELETE" to confirm:`;
            const userInput = prompt(confirmMessage);
            
            if (userInput === "DELETE") {
                return true;
            } else {
                alert('Operation cancelled. Sales history was not cleared.');
                return false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Add some interactivity to the sales history
            const historyItems = document.querySelectorAll('.history-item');
            
            historyItems.forEach(function(item) {
                item.addEventListener('click', function() {
                    // Add a subtle highlight effect when clicked
                    this.style.backgroundColor = 'rgba(163, 196, 243, 0.2)';
                    setTimeout(() => {
                        this.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
                    }, 200);
                });
            });

            // Add smooth scrolling for better UX
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Auto-hide messages after 5 seconds
            const messages = document.querySelectorAll('.message');
            messages.forEach(function(message) {
                setTimeout(function() {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.5s ease';
                    setTimeout(function() {
                        message.remove();
                    }, 500);
                }, 5000);
            });
        });
    </script>
</div>

<?php include '../includes/footer.php'; ?>