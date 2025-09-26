<?php
// Database server connection (without DB name yet)
$host = "localhost";
$user = "redhoddie";
$pass = "redhoddie_mysql";

// Create PDO connection to server
try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ Connected to MySQL server\n";
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}

// Load SQL from file and execute
$sqlFile = __DIR__ . "/database/pos.sql";
if (!file_exists($sqlFile)) {
    die("❌ SQL file not found: $sqlFile\n");
}

$sqlScript = file_get_contents($sqlFile);
if ($sqlScript === false) {
    die("❌ Failed to read SQL file: $sqlFile\n");
}

try {
    $pdo->exec($sqlScript);
    echo "✅ Database and tables set up from SQL file\n";
} catch (PDOException $e) {
    die("❌ SQL execution failed: " . $e->getMessage());
}

echo "🎉 Setup complete!";
