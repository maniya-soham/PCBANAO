<?php require_once __DIR__ . '/helpers.php';
$pdo = db();
$id = intval($_GET['id'] ?? 0);
$o = $pdo->query("SELECT * FROM orders WHERE id=$id")->fetch();
if (!$o) {
  die('Order not found');
}
$items = $pdo->query("SELECT oi.*, p.title FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=$id");
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <base href="/pcbanaop2/">

  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Order #<?= $id ?></title>
  <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
  <?php include __DIR__ . '/partials/header.php'; ?>
  <h1>Order #<?= $id ?></h1>
  <div class="grid">
    <div class="card">
      <div class="p">
        <div>Status: <span class="badge"><?= htmlspecialchars($o['status']) ?></span></div>
        <div>Date: <?= $o['created_at'] ?></div>
        <div>Total: <?= money($o['total']) ?></div>
        <div>Ship to: <?= htmlspecialchars($o['shipping_address']) ?></div>
      </div>
    </div>
    <div class="card">
      <div class="p">
        <h3>Items</h3>
        <table class="table">
          <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Price</th>
          </tr>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= htmlspecialchars($it['title']) ?></td>
              <td><?= $it['quantity'] ?></td>
              <td><?= money($it['price']) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
  </div>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>

</html>