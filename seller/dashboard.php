<?php
require_once __DIR__ . '/../helpers.php';
require_login();
require_role('seller');

$pdo = db();
$uid = (int) current_user()['id'];

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM products WHERE seller_id = ?");
$stmt->execute([$uid]);
$products_count = (int) $stmt->fetch()['c'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE p.seller_id = ?");
$stmt->execute([$uid]);
$orders_count = (int) $stmt->fetch()['c'];

$stmt = $pdo->prepare("SELECT IFNULL(SUM(oi.price * oi.quantity), 0) AS s FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE p.seller_id = ?");
$stmt->execute([$uid]);
$revenue_sum = $stmt->fetch()['s'];

$stats = [
  'products' => $products_count,
  'orders' => $orders_count,
  'revenue' => $revenue_sum,
];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <base href="/pcbanaop2/">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Seller Dashboard</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <?php include __DIR__ . '/../partials/header.php'; ?>
  <h1>Seller Dashboard</h1>

  <div class="kpis">
    <div class="kpi"><div>Products</div><h2><?= (int)$stats['products'] ?></h2></div>
    <div class="kpi"><div>Items Sold</div><h2><?= (int)$stats['orders'] ?></h2></div>
    <div class="kpi"><div>Revenue</div><h2><?= money($stats['revenue']) ?></h2></div>
    <div class="kpi"><a class="btn" href="seller/product_new.php">Add Product</a></div>
  </div>

  <h2>Your Products</h2>
  <table class="table">
    <tr><th>Title</th><th>Category</th><th>Price</th><th>Stock</th><th></th></tr>
    <?php
    $stmt = $pdo->prepare("SELECT p.*, c.name AS cat FROM products p JOIN categories c ON c.id = p.category_id WHERE p.seller_id = ? ORDER BY p.id DESC");
    $stmt->execute([$uid]);
    while ($p = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
      <tr>
        <td><?= htmlspecialchars($p['title']) ?></td>
        <td><?= htmlspecialchars($p['cat']) ?></td>
        <td><?= money($p['price']) ?></td>
        <td><?= (int)$p['stock'] ?></td>
        <td>
          <a class="btn secondary" href="seller/product_edit.php?id=<?= (int)$p['id'] ?>">Edit</a>
          <a class="btn" href="seller/product_delete.php?id=<?= (int)$p['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>

  <h2>Orders containing your items</h2>
  <table class="table">
    <tr>
      <th>Order #</th><th>Date</th><th>Status</th><th>Item</th><th>Qty</th><th>Price</th><th>Address</th><th>Action</th>
    </tr>
    <?php
    $sql = "SELECT o.id oid, o.created_at, o.status, o.shipping_address, oi.quantity, oi.price, p.title
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN products p ON p.id = oi.product_id
            WHERE p.seller_id = ?
            ORDER BY o.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$uid]);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
      <tr>
        <td>#<?= (int)$r['oid'] ?></td>
        <td><?= htmlspecialchars($r['created_at']) ?></td>
        <td>
          <form method="post" action="seller/update_order_status.php" style="display:inline;">
            <input type="hidden" name="order_id" value="<?= (int)$r['oid'] ?>">
            <select name="status" onchange="this.form.submit()">
              <option value="placed" <?= $r['status']=='placed'?'selected':'' ?>>Placed</option>
              <option value="packed" <?= $r['status']=='packed'?'selected':'' ?>>Packed</option>
              <option value="shipped" <?= $r['status']=='shipped'?'selected':'' ?>>Shipped</option>
              <option value="cancelled" <?= $r['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
            </select>
          </form>
        </td>
        <td><?= htmlspecialchars($r['title']) ?></td>
        <td><?= (int)$r['quantity'] ?></td>
        <td><?= money($r['price']) ?></td>
        <td><?= nl2br(htmlspecialchars($r['shipping_address'])) ?></td>
        <td><a class="btn" href="seller/order_view.php?id=<?= (int)$r['oid'] ?>">View</a></td>
      </tr>
    <?php endwhile; ?>
  </table>

  <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
