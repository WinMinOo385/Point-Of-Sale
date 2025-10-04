<?php
// Database connection
include '../includes/db_connection.php';

// Handle form submissions
$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                try {
                    $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)");
                    $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone']]);
                    $message = "Customer created successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error creating customer: " . $e->getMessage();
                    $messageType = "error";
                }
                break;
                
            case 'update':
                try {
                    $stmt = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ? WHERE cid = ?");
                    $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone'], $_POST['cid']]);
                    $message = "Customer updated successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error updating customer: " . $e->getMessage();
                    $messageType = "error";
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM customers WHERE cid = ?");
                    $stmt->execute([$_POST['cid']]);
                    $message = "Customer deleted successfully!";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $message = "Error deleting customer: " . $e->getMessage();
                    $messageType = "error";
                }
                break;
        }
    }
}

// Handle edit request
$editCustomer = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE cid = ?");
    $stmt->execute([$_GET['edit']]);
    $editCustomer = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Pagination (client-side search, so fetch all)
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 7;
$offset = ($page - 1) * $perPage;

// Get total count
$countStmt = $pdo->query("SELECT COUNT(*) FROM customers");
$totalCustomers = $countStmt->fetchColumn();
$totalPages = ceil($totalCustomers / $perPage);

// Fetch customers with pagination
$stmt = $pdo->prepare("SELECT * FROM customers ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
$pageTitle = "Customer Management - POS System";
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

        .form-container {
            background-color: #A3C4F3;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            color: #E6EBE0;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #E6EBE0;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            background-color: white;
            box-shadow: 0 0 10px rgba(163, 196, 243, 0.5);
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

        .btn-secondary {
            background-color: #757575;
        }

        .btn-secondary:hover {
            background-color: #616161;
        }

        .search-section {
            background-color: #A3C4F3;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-input {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            background-color: white;
            box-shadow: 0 0 10px rgba(163, 196, 243, 0.5);
        }

        .table-container {
            background-color: #A3C4F3;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table-container h2 {
            color: #E6EBE0;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #85a0c7;
            color: #E6EBE0;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
        }

        td {
            color: #333;
            background-color: rgba(255, 255, 255, 0.9);
        }

        tr:hover td {
            background-color: rgba(163, 196, 243, 0.1);
        }

        .actions {
            display: flex;
            gap: 5px;
        }

        .actions .btn {
            padding: 8px 12px;
            font-size: 14px;
            margin-right: 0;
        }

        .no-customers {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination-container {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }

        .pagination-info {
            color: #E6EBE0;
            font-size: 14px;
            font-weight: 500;
        }

        .pagination-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination-btn {
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 40px;
            text-decoration: none;
            display: inline-block;
        }

        .pagination-btn:hover:not(.disabled) {
            background-color: #85a0c7;
            color: #E6EBE0;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .pagination-btn.active {
            background-color: #85a0c7;
            color: #E6EBE0;
            font-weight: 600;
        }

        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination-ellipsis {
            color: #E6EBE0;
            padding: 0 5px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .form-container, .table-container {
                padding: 20px;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 10px;
            }
            
        .actions {
            flex-direction: column;
        }
    }
</style>

<div class="container">
    <div class="header">
        <h1>Customer Management</h1>
        <p>Manage Our customer list</p>
    </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Customer Form -->
        <div class="form-container">
            <h2><?php echo $editCustomer ? 'Edit Customer' : 'Add New Customer'; ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editCustomer ? 'update' : 'create'; ?>">
                <?php if ($editCustomer): ?>
                    <input type="hidden" name="cid" value="<?php echo $editCustomer['cid']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name">Customer Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo $editCustomer ? htmlspecialchars($editCustomer['name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo $editCustomer ? htmlspecialchars($editCustomer['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?php echo $editCustomer ? htmlspecialchars($editCustomer['phone']) : ''; ?>">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn">
                        <?php echo $editCustomer ? 'Update Customer' : 'Add Customer'; ?>
                    </button>
                    <?php if ($editCustomer): ?>
                        <a href="customer.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <div class="search-form">
                <input type="text" id="customerSearch" class="search-input" placeholder="🔍 Search customers by name, email, or phone..." onkeyup="filterCustomers()">
            </div>
        </div>

        <!-- Customer List -->
        <div class="table-container">
            <h2>Customer List<?php echo $search ? ' (Search Results)' : ''; ?></h2>
            <?php if (count($customers) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="customerTableBody">
                        <?php foreach ($customers as $customer): ?>
                            <tr class="customer-row" data-name="<?php echo htmlspecialchars(strtolower($customer['name'])); ?>" data-email="<?php echo htmlspecialchars(strtolower($customer['email'] ?: '')); ?>" data-phone="<?php echo htmlspecialchars($customer['phone'] ?: ''); ?>">
                                <td><?php echo $customer['cid']; ?></td>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone'] ?: 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="customer.php?edit=<?php echo $customer['cid']; ?>" class="btn">Edit</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="cid" value="<?php echo $customer['cid']; ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-customers">
                    <p>No customers found. Add your first customer using the form above.</p>
                </div>
            <?php endif; ?>
            <div id="noResults" class="no-customers" style="display: none;">
                <p>No customers match your search.</p>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?> (<?php echo $totalCustomers; ?> customers)
                    </div>
                    <div class="pagination-buttons">
                        <?php
                        // Previous button
                        if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?>" class="pagination-btn">← Prev</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">← Prev</span>
                        <?php endif; ?>

                        <?php
                        // Page numbers
                        for ($i = 1; $i <= $totalPages; $i++):
                            if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)):
                                $activeClass = ($i == $page) ? 'active' : '';
                                ?>
                                <a href="?page=<?php echo $i; ?>" class="pagination-btn <?php echo $activeClass; ?>"><?php echo $i; ?></a>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <span class="pagination-ellipsis">...</span>
                            <?php endif;
                        endfor;
                        ?>

                        <?php
                        // Next button
                        if ($page < $totalPages): ?>
                            <a href="?page=<?php echo ($page + 1); ?>" class="pagination-btn">Next →</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">Next →</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Client-side search filter
        function filterCustomers() {
            const searchInput = document.getElementById('customerSearch');
            const filter = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('.customer-row');
            const noResults = document.getElementById('noResults');
            const table = document.querySelector('table');
            let visibleCount = 0;

            rows.forEach(function(row) {
                const name = row.getAttribute('data-name') || '';
                const email = row.getAttribute('data-email') || '';
                const phone = row.getAttribute('data-phone') || '';
                
                if (name.includes(filter) || email.includes(filter) || phone.includes(filter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show/hide no results message
            if (visibleCount === 0 && filter !== '') {
                if (table) table.style.display = 'none';
                noResults.style.display = 'block';
            } else {
                if (table) table.style.display = '';
                noResults.style.display = 'none';
            }
        }

        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
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

            // Form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const name = document.getElementById('name');
                    if (name && name.value.trim() === '') {
                        e.preventDefault();
                        alert('Please enter a customer name.');
                        name.focus();
                    }
                });
            }
        });
    </script>
</div>

<?php include '../includes/footer.php'; ?>
