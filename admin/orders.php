<?php
require_once __DIR__ . '/../auth.php';
requireLogin();

$db  = getDB();
$msg = '';

// ── Update order status ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $valid  = ['pending','shipped','delivered','cancelled'];
    if ($id && in_array($status, $valid)) {
        $db->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
        $msg = "Order #$id status updated to " . ucfirst($status) . ".";
    }
}

// ── Filters ──
$statusFilter = $_GET['status'] ?? 'all';
$search       = trim($_GET['search'] ?? '');

$sql    = 'SELECT o.*, COALESCE(SUM(oi.quantity),0) AS item_count FROM orders o LEFT JOIN order_items oi ON oi.order_id=o.id WHERE 1=1';
$params = [];
if ($statusFilter !== 'all' && in_array($statusFilter, ['pending','shipped','delivered','cancelled'])) {
    $sql .= ' AND o.status = ?'; $params[] = $statusFilter;
}
if ($search) {
    $sql .= ' AND (o.customer_name LIKE ? OR o.customer_email LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%";
}
$sql .= ' GROUP BY o.id ORDER BY o.created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LUXE Admin — Orders</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/admin/admin.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>

<div class="admin-body">
  <h2 class="page-title">Orders</h2>

  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <!-- Filters -->
  <div class="filter-bar">
    <form method="GET" class="filter-form">
      <input type="text" name="search" placeholder="Search customer…" value="<?= htmlspecialchars($search) ?>">
      <select name="status">
        <option value="all" <?= $statusFilter==='all'?'selected':'' ?>>All Statuses</option>
        <option value="pending"   <?= $statusFilter==='pending'?'selected':'' ?>>Pending</option>
        <option value="shipped"   <?= $statusFilter==='shipped'?'selected':'' ?>>Shipped</option>
        <option value="delivered" <?= $statusFilter==='delivered'?'selected':'' ?>>Delivered</option>
        <option value="cancelled" <?= $statusFilter==='cancelled'?'selected':'' ?>>Cancelled</option>
      </select>
      <button type="submit" class="btn-outline">Filter</button>
      <?php if ($search || $statusFilter!=='all'): ?><a href="/admin/orders.php" class="btn-outline">Clear</a><?php endif; ?>
    </form>
    <span class="result-count"><?= count($orders) ?> order<?= count($orders)!==1?'s':'' ?></span>
  </div>

  <!-- Table -->
  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr><th>#</th><th>Customer</th><th>Email</th><th>Items</th><th>Total</th><th>Date</th><th>Status</th><th>Update</th></tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td>#<?= $o['id'] ?></td>
          <td><strong><?= htmlspecialchars($o['customer_name']) ?></strong></td>
          <td style="color:var(--text-dim)"><?= htmlspecialchars($o['customer_email']) ?></td>
          <td><?= $o['item_count'] ?></td>
          <td>$<?= number_format($o['total'],2) ?></td>
          <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
          <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
          <td>
            <form method="POST" class="inline-form">
              <input type="hidden" name="id" value="<?= $o['id'] ?>">
              <select name="status" class="status-select">
                <option value="pending"   <?= $o['status']==='pending'?'selected':'' ?>>Pending</option>
                <option value="shipped"   <?= $o['status']==='shipped'?'selected':'' ?>>Shipped</option>
                <option value="delivered" <?= $o['status']==='delivered'?'selected':'' ?>>Delivered</option>
                <option value="cancelled" <?= $o['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
              </select>
              <button type="submit" class="btn-outline" style="padding:4px 10px;font-size:11px">Save</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
          <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-dim);font-style:italic">No orders found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
