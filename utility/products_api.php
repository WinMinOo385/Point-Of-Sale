<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db_connection.php';

// Ensure products table has sku and notes columns (idempotent)
try {
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS sku VARCHAR(64) NULL AFTER name");
} catch (Throwable $e) {}
try {
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER stock");
} catch (Throwable $e) {}

function json_input() {
    $raw = file_get_contents('php://input');
    return $raw ? json_decode($raw, true) : [];
}

function respond($data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function validate_product($data, $isUpdate = false) {
    $errors = [];
    $name = trim($data['name'] ?? '');
    $sku = trim($data['sku'] ?? '');
    $stock = $data['stock'] ?? null;
    $price = $data['price'] ?? null;

    if (!$isUpdate || array_key_exists('name', $data)) {
        if ($name === '') $errors['name'] = 'Name is required';
        if (strlen($name) > 100) $errors['name'] = 'Max 100 characters';
    }
    if ($sku !== '' && strlen($sku) > 64) $errors['sku'] = 'Max 64 characters';
    if (!$isUpdate || array_key_exists('stock', $data)) {
        if (!is_numeric($stock) || $stock < 0) $errors['stock'] = 'Quantity must be a non-negative number';
    }
    if (!$isUpdate || array_key_exists('price', $data)) {
        if (!is_numeric($price) || $price < 0) $errors['price'] = 'Price must be a non-negative number';
    }

    return $errors;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['action'] ?? '';

try {
    if ($method === 'GET' && $path === 'list') {
        $q = trim($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? 'name';
        $allowed = ['name', 'qty', 'price'];
        if (!in_array($sort, $allowed, true)) $sort = 'name';
        $orderBy = $sort === 'qty' ? 'stock' : ($sort === 'price' ? 'price' : 'name');

        if ($q !== '') {
            $stmt = $pdo->prepare("SELECT pid, name, IFNULL(sku,'') as sku, price, stock, IFNULL(notes,'') as notes FROM products WHERE name LIKE ? OR IFNULL(sku,'') LIKE ? ORDER BY $orderBy ASC");
            $like = "%$q%";
            $stmt->execute([$like, $like]);
        } else {
            $stmt = $pdo->query("SELECT pid, name, IFNULL(sku,'') as sku, price, stock, IFNULL(notes,'') as notes FROM products ORDER BY $orderBy ASC");
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['success' => true, 'items' => $rows]);
    }

    if ($method === 'POST' && $path === 'create') {
        $data = json_input();
        $errors = validate_product($data);
        if ($errors) respond(['success' => false, 'errors' => $errors], 422);

        $stmt = $pdo->prepare("INSERT INTO products (name, sku, price, stock, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            trim($data['name']),
            trim($data['sku'] ?? '' ) ?: null,
            (float)$data['price'],
            (int)$data['stock'],
            trim($data['notes'] ?? '' ) ?: null,
        ]);
        $pid = (int)$pdo->lastInsertId();
        respond(['success' => true, 'id' => $pid]);
    }

    if ($method === 'PATCH' && $path === 'update') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) respond(['success' => false, 'message' => 'Invalid id'], 400);
        $data = json_input();
        $errors = validate_product($data, true);
        if ($errors) respond(['success' => false, 'errors' => $errors], 422);

        $fields = [];
        $params = [];
        foreach (['name','sku','price','stock','notes'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                if ($f === 'sku' || $f === 'notes') {
                    $val = trim($data[$f] ?? '');
                    $params[] = $val === '' ? null : $val;
                } elseif ($f === 'price') {
                    $params[] = (float)$data[$f];
                } elseif ($f === 'stock') {
                    $params[] = (int)$data[$f];
                } else {
                    $params[] = trim($data[$f]);
                }
            }
        }
        if (!$fields) respond(['success' => false, 'message' => 'No fields to update'], 400);
        $params[] = $id;

        $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE pid = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respond(['success' => true]);
    }

    if ($method === 'POST' && $path === 'adjust') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) respond(['success' => false, 'message' => 'Invalid id'], 400);
        $data = json_input();
        $delta = (int)($data['delta'] ?? 0);

        $stmt = $pdo->prepare('UPDATE products SET stock = GREATEST(0, stock + ?) WHERE pid = ?');
        $stmt->execute([$delta, $id]);
        respond(['success' => true]);
    }

    if ($method === 'DELETE' && $path === 'delete') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) respond(['success' => false, 'message' => 'Invalid id'], 400);
        $stmt = $pdo->prepare('DELETE FROM products WHERE pid = ?');
        $stmt->execute([$id]);
        respond(['success' => true]);
    }

    respond(['success' => false, 'message' => 'Not found'], 404);
} catch (Throwable $e) {
    respond(['success' => false, 'message' => $e->getMessage()], 500);
}


