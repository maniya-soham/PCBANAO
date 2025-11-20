<?php
require_once __DIR__ . '/../helpers.php';
require_login();
require_role('seller');

$pdo=db(); $seller_id=(int)current_user()['id'];
$order_id=isset($_GET['id'])?(int)$_GET['id']:0;
if($order_id<=0){header('Location: dashboard.php');exit;}

$chk=$pdo->prepare("SELECT COUNT(*) c FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=? AND p.seller_id=?");
$chk->execute([$order_id,$seller_id]);
if((int)$chk->fetch()['c']===0){header('Location: dashboard.php');exit;}

$order_stmt=$pdo->prepare("SELECT id,created_at,status,shipping_address FROM orders WHERE id=?");
$order_stmt->execute([$order_id]);
$order=$order_stmt->fetch(PDO::FETCH_ASSOC);
if(!$order){header('Location: dashboard.php');exit;}

$items_stmt=$pdo->prepare("SELECT oi.quantity,oi.price,p.title FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=? AND p.seller_id=?");
$items_stmt->execute([$order_id,$seller_id]);
$items=$items_stmt->fetchAll(PDO::FETCH_ASSOC);

$seller_subtotal=0;
foreach($items as $it){$seller_subtotal+=$it['price']*$it['quantity'];}

$shop_name="PCBANAO";
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Print Order #<?= (int)$order['id'] ?></title>
  <style>
    body{font-family:Arial,sans-serif;margin:1rem;}
    .btn{padding:.3rem .6rem;background:#111;color:#fff;text-decoration:none;border-radius:5px;}
    table{width:100%;border-collapse:collapse;margin-top:1rem;}
    th,td{border:1px solid #ccc;padding:.5rem;text-align:left;}
    th{background:#eee;}
    .right{text-align:right;}
    @media print{.noprint{display:none;}}
  </style>
</head>
<body>
<div class="noprint">
  <a class="btn" href="javascript:window.print()">Print</a>
  <a class="btn" href="order_view.php?id=<?= (int)$order['id'] ?>">Back</a>
</div>

<h1><?= $shop_name ?> Invoice</h1>
<p><strong>Order #<?= (int)$order['id'] ?></strong><br>
Date: <?= htmlspecialchars($order['created_at']) ?><br>
Status: <?= htmlspecialchars($order['status']) ?></p>

<p><strong>Shipping Address:</strong><br><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>

<h2>Items</h2>
<table>
  <tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th><th>Address</th></tr>
  <?php foreach($items as $it): $line=$it['price']*$it['quantity']; ?>
    <tr>
      <td><?= htmlspecialchars($it['title']) ?></td>
      <td><?= (int)$it['quantity'] ?></td>
      <td><?= money($it['price']) ?></td>
      <td class="right"><?= money($line) ?></td>
      <td><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></td>
    </tr>
  <?php endforeach; ?>
  <tr><td colspan="3" class="right"><strong>Subtotal</strong></td><td colspan="2"><strong><?= money($seller_subtotal) ?></strong></td></tr>
</table>

<p class="right noprint">Thank you for selling with <?= $shop_name ?>.</p>
</body>
</html>
