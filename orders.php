<?php
require_once __DIR__ . '/helpers.php';
ensure_schema();
$pdo = db();

// Ensure cart exists
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$cart = &$_SESSION['cart'];

// ---- POST handlers ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();

  // 1) Remove item
  if (isset($_POST['remove'])) {
    $rid = (int)$_POST['remove'];
    unset($_SESSION['cart'][$rid]);
    flash('msg', 'Item removed from cart.');
    header('Location: orders.php');
    exit;
  }

  // 2) Update cart quantities
  if (isset($_POST['update']) && isset($_POST['qty']) && is_array($_POST['qty'])) {
    foreach ($_POST['qty'] as $pid => $q) {
      $pid = (int)$pid;
      $q = max(0, (int)$q);
      if ($q === 0) unset($_SESSION['cart'][$pid]);
      else $_SESSION['cart'][$pid] = $q;
    }
    flash('msg', 'Cart updated.');
    header('Location: orders.php');
    exit;
  }

  // 3) Checkout
  if (isset($_POST['checkout'])) {
    require_login();

    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
      flash('warn', 'Cart is empty');
      header('Location: orders.php');
      exit;
    }

    // read changed qtys before computing totals
    if (isset($_POST['qty']) && is_array($_POST['qty'])) {
      foreach ($_POST['qty'] as $pid => $q) {
        $pid = (int)$pid;
        $q = max(0, (int)$q);
        if ($q === 0) unset($cart[$pid]);
        else $cart[$pid] = $q;
      }
      $_SESSION['cart'] = $cart;
    }

    $ids = array_map('intval', array_keys($cart));
    if (!$ids) {
      flash('warn', 'Cart became empty');
      header('Location: orders.php');
      exit;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $items = $stmt->fetchAll();
    if (!$items) {
      flash('warn', 'No valid items in cart.');
      header('Location: orders.php');
      exit;
    }

    // compute total
    $total = 0;
    foreach ($items as $p) {
      $qty = max(1, (int)$cart[$p['id']]);
      $total += ((int)$p['price']) * $qty;
    }

    // must select saved address
    $address = trim($_POST['address_choice'] ?? '');
    if ($address === '') {
      flash('warn', 'Please select a shipping address.');
      header('Location: orders.php');
      exit;
    }

    try {
      $pdo->beginTransaction();

      $pdo->prepare("INSERT INTO orders (user_id,status,total,shipping_address) VALUES (?,?,?,?)")
        ->execute([(int)current_user()['id'], 'placed', (int)$total, $address]);
      $oid = (int)$pdo->lastInsertId();

      $oi  = $pdo->prepare("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)");
      $upd = $pdo->prepare("
        UPDATE products
        SET stock = CASE WHEN stock - ? < 0 THEN 0 ELSE stock - ? END
        WHERE id = ?
      ");

      foreach ($items as $p) {
        $pid = (int)$p['id'];
        $qty = max(1, (int)$cart[$pid]);
        $price = (int)$p['price'];
        $oi->execute([$oid, $pid, $qty, $price]);
        $upd->execute([$qty, $qty, $pid]);
      }

      $pdo->commit();
      $_SESSION['cart'] = [];
      flash('msg', 'Order placed!');
      header('Location: order.php?id=' . $oid);
      exit;
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      flash('warn', 'Checkout failed: ' . $e->getMessage());
      header('Location: orders.php');
      exit;
    }
  }
}

// ---- Build current cart view ----
$items = [];
$total = 0;
if ($cart) {
  $ids = array_map('intval', array_keys($cart));
  if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $items = $stmt->fetchAll();

    foreach ($items as $p) {
      $qty = max(1, (int)$cart[$p['id']]);
      $total += ((int)$p['price']) * $qty;
    }
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Orders & Cart</title>
  <base href="/pcbanaop2/">
  <link rel="stylesheet" href="assets/styles.css">
  <script defer src="assets/app.js"></script>
</head>
<body>
  <?php include __DIR__ . '/partials/header.php'; ?>

  <?php if ($m = flash('msg')): ?>
    <div class="toast"><?= htmlspecialchars($m) ?></div>
  <?php endif; ?>
  <?php if ($w = flash('warn')): ?>
    <div class="toast warning"><?= htmlspecialchars($w) ?></div>
  <?php endif; ?>

  <h1>Your Cart</h1>

  <?php if ($items): ?>
    <form method="post">
      <?php csrf_field(); ?>
      <table class="table">
        <tr>
          <th>Product</th>
          <th style="width:120px">Qty</th>
          <th>Price</th>
          <th>Subtotal</th>
          <th></th>
        </tr>
        <?php foreach ($items as $p):
          $img = product_image($pdo, $p['id']);
          $qty = max(1, (int)($cart[$p['id']] ?? 1));
          $sub = ((int)$p['price']) * $qty;
        ?>
          <tr>
            <td>
              <div class="row" style="align-items:center;gap:10px">
                <img src="<?= htmlspecialchars($img) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:8px">
                <div>
                  <div><strong><?= htmlspecialchars($p['title']) ?></strong></div>
                  <div class="small">Stock: <?= (int)$p['stock'] ?></div>
                </div>
              </div>
            </td>
            <td>
              <input class="input" type="number" min="1" name="qty[<?= (int)$p['id'] ?>]" value="<?= $qty ?>">
            </td>
            <td><?= money((int)$p['price']) ?></td>
            <td><?= money($sub) ?></td>
            <td>
              <button class="btn secondary" type="submit" name="remove" value="<?= (int)$p['id'] ?>" formnovalidate>
                Remove
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
        <tr>
          <td colspan="3" align="right"><strong>Total</strong></td>
          <td colspan="2"><strong><?= money($total) ?></strong></td>
        </tr>
      </table>

      <div class="row" style="margin:10px 0">
        <button class="btn secondary" type="submit" name="update" value="1" formnovalidate>
          Update Cart
        </button>
      </div>

      <?php if (current_user()): ?>
        <h3>Checkout</h3>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([(int)current_user()['id']]);
        $saved_addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if ($saved_addresses): ?>
          <label>Select a shipping address</label>
          <select class="input" name="address_choice" required>
            <option value="">-- Select --</option>
            <?php foreach ($saved_addresses as $a): ?>
              <?php 
                $full = $a['street_address'] . ', ' . $a['area'] . ', ' . $a['city'] . ', ' . $a['state'] . ' - ' . $a['postal_code'] . ', ' . $a['country']; 
                $selected = $a['is_default'] ? 'selected' : '';
              ?>
              <option value="<?= htmlspecialchars($full) ?>" <?= $selected ?>>
                <?= htmlspecialchars(($a['label'] ?: 'Address') . ': ' . $full) ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <div class="toast warning">No saved addresses. Please add one.</div>
        <?php endif; ?>

        <div style="margin-top:10px">
          <a class="btn secondary" href="address_add.php">➕ Add New Address</a>
        </div>

        <button class="btn" name="checkout" value="1" style="margin-top:8px">Place Order</button>
      <?php else: ?>
        <div class="toast warning">Please login to checkout.</div>
      <?php endif; ?>
    </form>
  <?php else: ?>
    <div class="small">Your cart is empty.</div>
  <?php endif; ?>

  <!-- ================== Your Orders Section ================== -->
  <?php
  if (current_user()) {
    $stmt = $pdo->prepare("
      SELECT o.id, o.status, o.total, o.shipping_address, o.created_at,
             COALESCE(SUM(oi.quantity),0) AS items
      FROM orders o
      LEFT JOIN order_items oi ON oi.order_id = o.id
      WHERE o.user_id = ?
      GROUP BY o.id
      ORDER BY o.created_at DESC
    ");
    $stmt->execute([ (int) current_user()['id'] ]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <h2 style="margin-top:24px">Your Orders</h2>
    <?php if ($orders): ?>
      <table class="table">
        <tr>
          <th>#</th>
          <th>Status</th>
          <th>Items</th>
          <th>Total</th>
          <th>Placed</th>
          <th></th>
        </tr>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><?= (int)$o['id'] ?></td>
            <td><?= htmlspecialchars($o['status']) ?></td>
            <td><?= (int)$o['items'] ?></td>
            <td><?= money((int)$o['total']) ?></td>
            <td class="small"><?= htmlspecialchars($o['created_at']) ?></td>
            <td><a class="btn small" href="order.php?id=<?= (int)$o['id'] ?>">View</a></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <div class="small">No orders yet.</div>
    <?php endif; ?>
  <?php } else { ?>
    <div class="toast warning" style="margin-top:24px">Login to see your orders.</div>
  <?php } ?>
  <!-- ========================================================= -->

  <div id="toast"></div>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
