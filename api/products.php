<?php
// ============================================
//  API: Products
//  GET    /api/products.php          — list all (filter by ?category=)
//  GET    /api/products.php?id=1     — single product
//  POST   /api/products.php          — create  (admin)
//  PUT    /api/products.php?id=1     — update  (admin)
//  DELETE /api/products.php?id=1     — delete  (admin)
// ============================================

require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$db     = getDB();

// ── GET ──
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if (!$product) jsonResponse(['error' => 'Product not found'], 404);
        jsonResponse($product);
    }

    $category = $_GET['category'] ?? null;
    if ($category && in_array($category, ['clothing', 'accessories', 'home'])) {
        $stmt = $db->prepare('SELECT * FROM products WHERE category = ? ORDER BY created_at DESC');
        $stmt->execute([$category]);
    } else {
        $stmt = $db->query('SELECT * FROM products ORDER BY created_at DESC');
    }
    jsonResponse($stmt->fetchAll());
}

// ── POST (Create) ──
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) jsonResponse(['error' => 'Invalid JSON'], 400);

    $required = ['name', 'category', 'price'];
    foreach ($required as $field) {
        if (empty($data[$field])) jsonResponse(['error' => "Field '$field' is required"], 400);
    }

    if (!in_array($data['category'], ['clothing', 'accessories', 'home'])) {
        jsonResponse(['error' => 'Invalid category'], 400);
    }

    $stmt = $db->prepare('
        INSERT INTO products (name, category, price, stock, emoji, badge, description)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $data['name'],
        $data['category'],
        (float)$data['price'],
        (int)($data['stock'] ?? 0),
        $data['emoji'] ?? '🛍️',
        $data['badge'] ?? '',
        $data['description'] ?? '',
    ]);

    $newId = $db->lastInsertId();
    $stmt  = $db->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$newId]);
    jsonResponse($stmt->fetch(), 201);
}

// ── PUT (Update) ──
if ($method === 'PUT') {
    if (!$id) jsonResponse(['error' => 'ID required'], 400);
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) jsonResponse(['error' => 'Invalid JSON'], 400);

    $stmt = $db->prepare('SELECT id FROM products WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) jsonResponse(['error' => 'Product not found'], 404);

    $stmt = $db->prepare('
        UPDATE products
        SET name=?, category=?, price=?, stock=?, emoji=?, badge=?, description=?
        WHERE id=?
    ');
    $stmt->execute([
        $data['name']        ?? '',
        $data['category']    ?? 'clothing',
        (float)($data['price'] ?? 0),
        (int)($data['stock']   ?? 0),
        $data['emoji']       ?? '🛍️',
        $data['badge']       ?? '',
        $data['description'] ?? '',
        $id,
    ]);

    $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse($stmt->fetch());
}

// ── DELETE ──
if ($method === 'DELETE') {
    if (!$id) jsonResponse(['error' => 'ID required'], 400);

    $stmt = $db->prepare('SELECT id FROM products WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) jsonResponse(['error' => 'Product not found'], 404);

    $db->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    jsonResponse(['message' => 'Product deleted']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
