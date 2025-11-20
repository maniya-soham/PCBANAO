<?php
require_once __DIR__ . '/../helpers.php';
require_login();
require_role('seller');

$pdo = db();
$seller_id = (int) current_user()['id'];

$order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($order_id <= 0) { header('Location: dashboard.php'); exit; }

// Check ownership
$chk = $pdo->prepare("SELECT COUNT(*) c FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=? AND p.seller_id=?");
$chk->execute([$order_id,$seller_id]);
if ((int)$chk->fetch()['c']===0){ header('Location: dashboard.php'); exit; }

$order_stmt=$pdo->prepare("SELECT id, created_at, status, shipping_address FROM orders WHERE id=?");
$order_stmt->execute([$order_id]);
$order=$order_stmt->fetch(PDO::FETCH_ASSOC);
if(!$order){ header('Location: dashboard.php'); exit; }

$items_stmt=$pdo->prepare("SELECT oi.quantity,oi.price,p.title FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=? AND p.seller_id=?");
$items_stmt->execute([$order_id,$seller_id]);
$items=$items_stmt->fetchAll(PDO::FETCH_ASSOC);

$seller_subtotal=0;
foreach($items as $it){ $seller_subtotal+=((float)$it['price'])*((int)$it['quantity']); }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <base href="/pcbanaop2/">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Order #<?= (int)$order['id'] ?></title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include __DIR__ . '/../partials/header.php'; ?>
<h1>Order #<?= (int)$order['id'] ?></h1>

<div>
  <strong>Date:</strong> <?= htmlspecialchars($order['created_at']) ?><br>
  <strong>Status:</strong>
  <form method="post" action="seller/update_order_status.php" style="display:inline;">
    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
    <select name="status" onchange="this.form.submit()">
      <option value="placed" <?= $order['status']=='placed'?'selected':'' ?>>Placed</option>
      <option value="packed" <?= $order['status']=='packed'?'selected':'' ?>>Packed</option>
      <option value="shipped" <?= $order['status']=='shipped'?'selected':'' ?>>Shipped</option>
      <option value="cancelled" <?= $order['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
    </select>
  </form><br>
  <strong>Address:</strong><br>
  <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
</div>

<h2>Your Items</h2>
<table class="table">
  <tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th><th>Address</th></tr>
  <?php foreach($items as $it): $line=$it['price']*$it['quantity']; ?>
    <tr>
      <td><?= htmlspecialchars($it['title']) ?></td>
      <td><?= (int)$it['quantity'] ?></td>
      <td><?= money($it['price']) ?></td>
      <td><?= money($line) ?></td>
      <td><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></td>
    </tr>
  <?php endforeach; ?>
  <tr><td colspan="3" align="right"><strong>Subtotal</strong></td><td colspan="2"><strong><?= money($seller_subtotal) ?></strong></td></tr>
</table>

<p><a class="btn" href="seller/dashboard.php">Back</a> <a class="btn" href="seller/order_print.php?id=<?= (int)$order['id'] ?>">Print</a></p>
<?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
