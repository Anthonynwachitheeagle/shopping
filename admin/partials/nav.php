<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="admin-nav">
  <a href="/admin/index.php" class="admin-logo">LUXE Admin</a>
  <div class="nav-links">
    <a href="/admin/index.php"    class="nav-tab <?= $currentPage==='index.php'?'active':'' ?>">Dashboard</a>
    <a href="/admin/products.php" class="nav-tab <?= $currentPage==='products.php'?'active':'' ?>">Products</a>
    <a href="/admin/orders.php"   class="nav-tab <?= $currentPage==='orders.php'?'active':'' ?>">Orders</a>
  </div>
  <div class="nav-right">
    <span class="nav-user">👤 <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?></span>
    <a href="/admin/logout.php" class="btn-outline" style="font-size:12px;padding:5px 12px">Logout</a>
    <a href="/" class="btn-outline" style="font-size:12px;padding:5px 12px">← Shop</a>
  </div>
</nav>
