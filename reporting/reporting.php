<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "POS";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Inputs (GET)
$startDate = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] : null;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit < 1) { $limit = 10; }
if ($limit > 100) { $limit = 100; }

// Build query for top customers
$whereSalesDate = [];
$params = [];

if ($startDate) {
    $whereSalesDate[] = "s.sale_date >= :start_date";
    $params[':start_date'] = $startDate . ' 00:00:00';
}
if ($endDate) {
    $whereSalesDate[] = "s.sale_date <= :end_date";
    $params[':end_date'] = $endDate . ' 23:59:59';
}

$salesDateClause = count($whereSalesDate) > 0 ? (" AND " . implode(" AND ", $whereSalesDate)) : "";

$sql = "
    SELECT 
        c.cid,
        c.name,
        c.email,
        c.phone,
        COUNT(s.sid) AS orders_count,
        COALESCE(SUM(s.total_amount), 0) AS total_spent,
        COALESCE(AVG(s.total_amount), 0) AS avg_order_value,
        MAX(s.sale_date) AS last_purchase
    FROM customers c
    LEFT JOIN sales s ON s.cid = c.cid $salesDateClause
    GROUP BY c.cid, c.name, c.email, c.phone
    HAVING orders_count > 0
    ORDER BY total_spent DESC, orders_count DESC, c.name ASC
    LIMIT :limit
";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary stats
$summarySql = "
    SELECT 
        COUNT(DISTINCT s.sid) AS total_orders,
        COALESCE(SUM(s.total_amount), 0) AS total_revenue,
        COALESCE(AVG(s.total_amount), 0) AS avg_order
    FROM sales s
    WHERE 1=1" . (count($whereSalesDate) ? (" AND " . implode(" AND ", $whereSalesDate)) : "");

$summaryStmt = $pdo->prepare($summarySql);
foreach ($params as $k => $v) {
    $summaryStmt->bindValue($k, $v);
}
$summaryStmt->execute();
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_orders' => 0,
    'total_revenue' => 0,
    'avg_order' => 0
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Customers Report - POS System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #E6EBE0;
            color: #E6EBE0;
            line-height: 1.6;
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }

        .header {
            background-color: #A3C4F3;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header h1 { color: #E6EBE0; font-size: 2.5rem; margin-bottom: 10px; }
        .header p { color: #E6EBE0; font-size: 1.1rem; opacity: 0.9; }

        .navigation {
            background-color: #A3C4F3;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .nav-links { display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; }
        .nav-link { background-color: #85a0c7; color: #E6EBE0; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: 600; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; }
        .nav-link:hover { background-color: #6d8bb3; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .nav-link.active { background-color: #4CAF50; }
        .nav-link.active:hover { background-color: #45a049; }

        .filters, .table-container, .stats-container {
            background-color: #A3C4F3;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .filters h2, .table-container h2 { color: #E6EBE0; margin-bottom: 15px; }
        .filters form { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end; }
        label { color: #E6EBE0; font-weight: 600; margin-bottom: 6px; display: block; }
        input[type="date"], select { width: 100%; padding: 10px; border: none; border-radius: 5px; background-color: rgba(255,255,255,0.9); color: #333; }

        .btn { background-color: #85a0c7; color: #E6EBE0; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s ease; }
        .btn:hover { background-color: #6d8bb3; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .stat-card { background-color: rgba(255,255,255,0.95); padding: 18px; border-radius: 8px; text-align: center; color: #333; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 1.8rem; font-weight: bold; color: #4CAF50; }
        .stat-label { color: #666; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }

        table { width: 100%; border-collapse: collapse; background-color: rgba(255, 255, 255, 0.95); border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #85a0c7; color: #E6EBE0; font-weight: 600; text-transform: uppercase; font-size: 14px; letter-spacing: 1px; }
        td { color: #333; background-color: rgba(255, 255, 255, 0.9); }
        tr:hover td { background-color: rgba(163, 196, 243, 0.1); }

        .badge { background-color: #85a0c7; color: #E6EBE0; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }

        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header h1 { font-size: 2rem; }
            .filters form { grid-template-columns: 1fr; }
        }
    </style>
    <script>
        function resetFilters() {
            const url = new URL(window.location.href);
            ['start_date','end_date','limit'].forEach(k => url.searchParams.delete(k));
            window.location.href = url.toString();
        }
    </script>
    </head>
<body>
    <div class="container">
        <div class="header">
            <h1>Top Customers</h1>
            <p>See which customers drive the most revenue</p>
        </div>

        <div class="navigation">
            <div class="nav-links">
                <a href="../index.php" class="nav-link">🏠 Home</a>
                <a href="../customer/customer.php" class="nav-link">👥 Customers</a>
                <a href="../sale/sale.php" class="nav-link">🛒 Sales History</a>
                <a href="reporting.php" class="nav-link active">📊 Reporting</a>
            </div>
        </div>

        <div class="filters">
            <h2>Filters</h2>
            <form method="GET">
                <div>
                    <label for="start_date">Start date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate ?? ''); ?>">
                </div>
                <div>
                    <label for="end_date">End date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate ?? ''); ?>">
                </div>
                <div>
                    <label for="limit">Top N</label>
                    <select id="limit" name="limit">
                        <?php foreach ([5,10,20,50,100] as $n): ?>
                            <option value="<?php echo $n; ?>" <?php echo $limit === $n ? 'selected' : ''; ?>><?php echo $n; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn">Apply</button>
                    <button type="button" class="btn" onclick="resetFilters()">Reset</button>
                </div>
            </form>
        </div>

        <div class="stats-container">
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo (int)$summary['total_orders']; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format((float)$summary['total_revenue'], 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format((float)$summary['avg_order'], 2); ?></div>
                    <div class="stat-label">Avg. Order Value</div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <h2>Top Customers<?php echo $startDate || $endDate ? ' (Filtered)' : ''; ?></h2>
            <?php if (count($topCustomers) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Avg Order</th>
                            <th>Last Purchase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topCustomers as $idx => $row): ?>
                            <tr>
                                <td><span class="badge"><?php echo $idx + 1; ?></span></td>
                                <td>
                                    <a href="../customer/customer.php?edit=<?php echo (int)$row['cid']; ?>" class="nav-link" style="padding:6px 10px; display:inline-block;">
                                        <?php echo htmlspecialchars($row['name']); ?> ↗
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row['email'] ?: 'N/A'); ?><br>
                                    <?php echo htmlspecialchars($row['phone'] ?: ''); ?>
                                </td>
                                <td><?php echo (int)$row['orders_count']; ?></td>
                                <td>$<?php echo number_format((float)$row['total_spent'], 2); ?></td>
                                <td>$<?php echo number_format((float)$row['avg_order_value'], 2); ?></td>
                                <td><?php echo $row['last_purchase'] ? date('M d, Y H:i', strtotime($row['last_purchase'])) : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="stat-card" style="text-align:center;">
                    <div style="color:#85a0c7; font-weight:600; margin-bottom:8px;">No data</div>
                    <div style="color:#333;">No customers with sales found for the selected period.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>


