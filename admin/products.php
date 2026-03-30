<?php
require_once __DIR__ . '/../auth.php';
requireLogin();

$db  = getDB();
$msg = '';
$err = '';

// ── Handle form submissions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name  = trim($_POST['name'] ?? '');
        $cat   = $_POST['category'] ?? '';
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $emoji = trim($_POST['emoji'] ?? '🛍️');
        $badge = trim($_POST['badge'] ?? '');
        $desc  = trim($_POST['description'] ?? '');

        if (!$name || !$cat || $price <= 0) {
            $err = 'Name, category and price are required.';
        } elseif (!in_array($cat, ['clothing','accessories','home'])) {
            $err = 'Invalid category.';
        } else {
            if ($action === 'create') {
                $stmt = $db->prepare('INSERT INTO products (name,category,price,stock,emoji,badge,description) VALUES (?,?,?,?,?,?,?)');
                $stmt->execute([$name,$cat,$price,$stock,$emoji,$badge,$desc]);
                $msg = 'Product added successfully.';
            } else {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $db->prepare('UPDATE products SET name=?,category=?,price=?,stock=?,emoji=?,badge=?,description=? WHERE id=?');
                $stmt->execute([$name,$cat,$price,$stock,$emoji,$badge,$desc,$id]);
                $msg = 'Product updated successfully.';
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        $msg = 'Product deleted.';
    }
}

// ── Filter & fetch ──
$catFilter = $_GET['category'] ?? 'all';
$search    = trim($_GET['search'] ?? '');

$sql    = 'SELECT * FROM products WHERE 1=1';
$params = [];
if ($catFilter !== 'all' && in_array($catFilter, ['clothing','accessories','home'])) {
    $sql .= ' AND category = ?'; $params[] = $catFilter;
}
if ($search) {
    $sql .= ' AND name LIKE ?'; $params[] = "%$search%";
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Product to edit (if ?edit=id)
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editProduct = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LUXE Admin — Products</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/admin/admin.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>

<div class="admin-body">
  <div class="page-header">
    <h2 class="page-title">Products</h2>
    <button class="btn-gold" onclick="toggleForm()">+ Add Product</button>
  </div>

  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <!-- Add / Edit Form -->
  <div class="form-card" id="product-form" style="display:<?= ($editProduct || $err) ? 'block' : 'none' ?>">
    <h3 class="form-card-title"><?= $editProduct ? 'Edit Product' : 'Add New Product' ?></h3>
    <form method="POST">
      <input type="hidden" name="action" value="<?= $editProduct ? 'update' : 'create' ?>">
      <?php if ($editProduct): ?>
        <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
      <?php endif; ?>

      <div class="form-row">
        <div class="form-group">
          <label>Product Name *</label>
          <input type="text" name="name" value="<?= htmlspecialchars($editProduct['name'] ?? $_POST['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Category *</label>
          <select name="category">
            <?php foreach(['clothing','accessories','home'] as $cat): ?>
              <option value="<?= $cat ?>" <?= ($editProduct['category'] ?? $_POST['category'] ?? '') === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Price ($) *</label>
          <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($editProduct['price'] ?? $_POST['price'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Stock</label>
          <input type="number" name="stock" min="0" value="<?= htmlspecialchars($editProduct['stock'] ?? $_POST['stock'] ?? '0') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Emoji</label>
          <input type="text" name="emoji" maxlength="4" value="<?= htmlspecialchars($editProduct['emoji'] ?? $_POST['emoji'] ?? '🛍️') ?>">
        </div>
        <div class="form-group">
          <label>Badge (optional)</label>
          <input type="text" name="badge" placeholder="New, Sale…" value="<?= htmlspecialchars($editProduct['badge'] ?? $_POST['badge'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="2"><?= htmlspecialchars($editProduct['description'] ?? $_POST['description'] ?? '') ?></textarea>
      </div>
      <div class="form-actions">
        <a href="/admin/products.php" class="btn-outline">Cancel</a>
        <button type="submit" class="btn-gold"><?= $editProduct ? 'Update Product' : 'Save Product' ?></button>
      </div>
    </form>
  </div>

  <!-- Filters -->
  <div class="filter-bar">
    <form method="GET" class="filter-form">
      <input type="text" name="search" placeholder="Search products…" value="<?= htmlspecialchars($search) ?>">
      <select name="category">
        <option value="all" <?= $catFilter==='all'?'selected':'' ?>>All Categories</option>
        <option value="clothing" <?= $catFilter==='clothing'?'selected':'' ?>>Clothing</option>
        <option value="accessories" <?= $catFilter==='accessories'?'selected':'' ?>>Accessories</option>
        <option value="home" <?= $catFilter==='home'?'selected':'' ?>>Home</option>
      </select>
      <button type="submit" class="btn-outline">Filter</button>
      <?php if ($search || $catFilter!=='all'): ?><a href="/admin/products.php" class="btn-outline">Clear</a><?php endif; ?>
    </form>
    <span class="result-count"><?= count($products) ?> product<?= count($products)!==1?'s':'' ?></span>
  </div>

  <!-- Table -->
  <div class="table-wrap">
    <table class="admin-table">
      <thead>
        <tr><th>Icon</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Badge</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td style="font-size:1.6rem"><?= $p['emoji'] ?></td>
          <td>
            <strong><?= htmlspecialchars($p['name']) ?></strong>
            <?php if ($p['description']): ?>
              <br><small style="color:var(--text-dim)"><?= htmlspecialchars(substr($p['description'],0,55)) ?>…</small>
            <?php endif; ?>
          </td>
          <td><?= ucfirst($p['category']) ?></td>
          <td>$<?= number_format($p['price'],2) ?></td>
          <td><span class="badge badge-<?= $p['stock']>0?'delivered':'cancelled' ?>"><?= $p['stock'] ?></span></td>
          <td><?php if ($p['badge']): ?><span class="badge badge-pending"><?= htmlspecialchars($p['badge']) ?></span><?php endif; ?></td>
          <td>
            <div class="action-btns">
              <a href="/admin/products.php?edit=<?= $p['id'] ?>" class="btn-edit">Edit</a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this product?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn-delete">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-dim);font-style:italic">No products found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
function toggleForm() {
  const f = document.getElementById('product-form');
  f.style.display = f.style.display === 'none' ? 'block' : 'none';
}
</script>
</body>
</html>
