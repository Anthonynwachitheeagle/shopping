<?php
require_once __DIR__ . '/../auth.php';
requireLogin();

$db = getDB();

// Stats
$totalRevenue  = $db->query('SELECT COALESCE(SUM(total),0) FROM orders')->fetchColumn();
$totalOrders   = $db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalProducts = $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$deliveredOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetchColumn();

// Recent orders
$recentOrders = $db->query('
    SELECT o.*, COALESCE(SUM(oi.quantity),0) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    GROUP BY o.id ORDER BY o.created_at DESC LIMIT 5
')->fetchAll();

// Low stock products
$lowStock = $db->query('SELECT * FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 5')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LUXE Admin — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/admin/admin.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>

<div class="admin-body">
  <h2 class="page-title">Dashboard</h2>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">Total Revenue</div>
      <div class="stat-value">$<?= number_format($totalRevenue, 0) ?></div>
      <div class="stat-sub">All time</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Orders</div>
      <div class="stat-value"><?= $totalOrders ?></div>
      <div class="stat-sub"><?= $deliveredOrders ?> delivered</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Products</div>
      <div class="stat-value"><?= $totalProducts ?></div>
      <div class="stat-sub">In catalogue</div>
    </div>
    <div class="stat-card highlight">
      <div class="stat-label">Pending</div>
      <div class="stat-value"><?= $pendingOrders ?></div>
      <div class="stat-sub">Awaiting dispatch</div>
    </div>
  </div>

  <div class="two-col">

    <!-- Recent Orders -->
    <div>
      <div class="section-header">
        <h3 class="section-title">Recent Orders</h3>
        <a href="/admin/orders.php" class="view-all">View all →</a>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($recentOrders as $o): ?>
            <tr>
              <td>#<?= $o['id'] ?></td>
              <td><?= htmlspecialchars($o['customer_name']) ?></td>
              <td><?= $o['item_count'] ?></td>
              <td>$<?= number_format($o['total'], 2) ?></td>
              <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Low Stock -->
    <div>
      <div class="section-header">
        <h3 class="section-title">Low Stock Alert</h3>
        <a href="/admin/products.php" class="view-all">View all →</a>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Product</th><th>Category</th><th>Stock</th></tr></thead>
          <tbody>
            <?php foreach ($lowStock as $p): ?>
            <tr>
              <td><?= $p['emoji'] ?> <?= htmlspecialchars($p['name']) ?></td>
              <td><?= $p['category'] ?></td>
              <td><span class="badge badge-<?= $p['stock'] == 0 ? 'cancelled' : 'pending' ?>"><?= $p['stock'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($lowStock)): ?>
            <tr><td colspan="3" style="text-align:center;color:var(--text-dim);font-style:italic">All products well stocked</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
</body>
</html>
