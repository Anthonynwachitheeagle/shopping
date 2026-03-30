<?php
// ============================================
//  API: Orders
//  GET    /api/orders.php          — list all
//  GET    /api/orders.php?id=1     — single order + items
//  POST   /api/orders.php          — create order (checkout)
//  PUT    /api/orders.php?id=1     — update status (admin)
//  DELETE /api/orders.php?id=1     — cancel order (admin)
// ============================================

require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$db     = getDB();

// ── GET ──
if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) jsonResponse(['error' => 'Order not found'], 404);

        // Attach items
        $stmt = $db->prepare('
            SELECT oi.*, p.name, p.emoji
            FROM order_items oi
            JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?
        ');
        $stmt->execute([$id]);
        $order['items'] = $stmt->fetchAll();
        jsonResponse($order);
    }

    // List with item count
    $stmt = $db->query('
        SELECT o.*,
               COALESCE(SUM(oi.quantity), 0) AS item_count
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ');
    jsonResponse($stmt->fetchAll());
}

// ── POST (Checkout) ──
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) jsonResponse(['error' => 'Invalid JSON'], 400);
    if (empty($data['items']) || !is_array($data['items'])) {
        jsonResponse(['error' => 'Cart is empty'], 400);
    }

    $customerName  = $data['customer_name']  ?? 'Guest Customer';
    $customerEmail = $data['customer_email'] ?? '';

    // Calculate total from DB prices (never trust client)
    $total = 0;
    $resolvedItems = [];
    foreach ($data['items'] as $item) {
        $stmt = $db->prepare('SELECT id, price, stock FROM products WHERE id = ?');
        $stmt->execute([(int)$item['product_id']]);
        $product = $stmt->fetch();
        if (!$product) jsonResponse(['error' => "Product #{$item['product_id']} not found"], 404);
        $qty = max(1, (int)$item['quantity']);
        if ($product['stock'] < $qty) {
            jsonResponse(['error' => "Insufficient stock for product #{$product['id']}"], 400);
        }
        $total += $product['price'] * $qty;
        $resolvedItems[] = ['id' => $product['id'], 'price' => $product['price'], 'qty' => $qty];
    }

    // Insert order
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('INSERT INTO orders (customer_name, customer_email, total) VALUES (?, ?, ?)');
        $stmt->execute([$customerName, $customerEmail, $total]);
        $orderId = $db->lastInsertId();

        foreach ($resolvedItems as $item) {
            $stmt = $db->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
            $stmt->execute([$orderId, $item['id'], $item['qty'], $item['price']]);

            // Decrement stock
            $db->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')
               ->execute([$item['qty'], $item['id']]);
        }
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => 'Order failed: ' . $e->getMessage()], 500);
    }

    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    jsonResponse($stmt->fetch(), 201);
}

// ── PUT (Update Status) ──
if ($method === 'PUT') {
    if (!$id) jsonResponse(['error' => 'ID required'], 400);
    $data = json_decode(file_get_contents('php://input'), true);
    $validStatuses = ['pending', 'shipped', 'delivered', 'cancelled'];
    if (!isset($data['status']) || !in_array($data['status'], $validStatuses)) {
        jsonResponse(['error' => 'Invalid status'], 400);
    }

    $stmt = $db->prepare('SELECT id FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) jsonResponse(['error' => 'Order not found'], 404);

    $db->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$data['status'], $id]);
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse($stmt->fetch());
}

// ── DELETE ──
if ($method === 'DELETE') {
    if (!$id) jsonResponse(['error' => 'ID required'], 400);
    $stmt = $db->prepare('SELECT id FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) jsonResponse(['error' => 'Order not found'], 404);

    $db->prepare('DELETE FROM orders WHERE id = ?')->execute([$id]);
    jsonResponse(['message' => 'Order deleted']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
