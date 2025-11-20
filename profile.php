<?php
require_once __DIR__ . '/helpers.php';
require_login();
ensure_schema();
$pdo = db();

// Handle admin actions (reassign orders to current user)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reassign']) && is_admin()) {
  verify_csrf();
  $ids = array_filter(array_map('intval', $_POST['ids'] ?? []));
  if ($ids) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = $ids;
    $params[] = (int)current_user()['id'];
    $pdo->prepare("UPDATE orders SET user_id = ? WHERE id IN ($placeholders)")->execute(array_merge([(int)current_user()['id']], $ids));
    flash('msg', 'Selected orders reassigned to your account.');
  } else {
    flash('warn', 'No orders selected to reassign.');
  }
  header('Location: profile.php');
  exit;
}

// Inputs / options
$showAll = is_admin() && (($_GET['show'] ?? '') === 'all');

// Load account basics
$me = current_user();

// Orders (yours or all if admin toggled)
if ($showAll) {
  $ordersStmt = $pdo->query("SELECT o.*, u.email FROM orders o LEFT JOIN users u ON u.id=o.user_id ORDER BY o.id DESC");
  $orders = $ordersStmt->fetchAll();
} else {
  $ordersStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
  $ordersStmt->execute([(int)$me['id']]);
  $orders = $ordersStmt->fetchAll();
}

// Counts for diagnostics
$totalOrders = (int)$pdo->query("SELECT COUNT(*) c FROM orders")->fetch()['c'];
$myCountStmt = $pdo->prepare("SELECT COUNT(*) c FROM orders WHERE user_id = ?");
$myCountStmt->execute([(int)$me['id']]);
$myOrdersCount = (int)$myCountStmt->fetch()['c'];

// Saved builds
$buildStmt = $pdo->prepare("SELECT * FROM builds WHERE user_id=? ORDER BY id DESC");
$buildStmt->execute([(int)$me['id']]);
$builds = $buildStmt->fetchAll();
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <base href="/pcbanaop2/">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Profile</title>
  <link rel="stylesheet" href="assets/styles.css">
  <style>
    .profile-grid {
      display: flex;
      flex-direction: column;
      gap: 20px;
      max-width: 1000px;
      margin: 0 auto
    }

    .table th,
    .table td {
      vertical-align: middle
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/partials/header.php'; ?>

  <h1>Your Profile</h1>

  <div class="profile-grid">
    <!-- Account card -->
    <div class="card">
      <div class="p">
        <h3>Account</h3>
        <div>Name: <?= htmlspecialchars($me['name']) ?></div>
        <div>Email: <?= htmlspecialchars($me['email']) ?></div>
        <div>Role: <?= htmlspecialchars($me['role']) ?></div>
        <div>Seller status: <?= htmlspecialchars($me['seller_status']) ?></div>
      </div>
    </div>

    <!-- Orders card -->
    <div class="card">
      <div class="p">
        <div class="row" style="justify-content:space-between;align-items:flex-end">
          <h3 style="margin:0">Order History</h3>
          <div class="row" style="gap:8px">
            <?php if (is_admin()): ?>
              <?php if ($showAll): ?>
                <a class="btn secondary" href="profile.php">Show my orders (<?= $myOrdersCount ?>)</a>
              <?php else: ?>
                <a class="btn secondary" href="profile.php?show=all">Show all orders (<?= $totalOrders ?>)</a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($orders): ?>
          <form method="post">
            <?php if ($showAll && is_admin()): ?>
              <?php csrf_field(); ?>
            <?php endif; ?>
            <table class="table">
              <tr>
                <?php if ($showAll && is_admin()): ?><th></th><?php endif; ?>
                <th>#</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total</th>
                <?php if ($showAll && is_admin()): ?><th>User</th><?php endif; ?>
                <th></th>
              </tr>
              <?php foreach ($orders as $o): ?>
                <tr>
                  <?php if ($showAll && is_admin()): ?>
                    <td><input class="sel" type="checkbox" name="ids[]" value="<?= (int)$o['id'] ?>"></td>
                  <?php endif; ?>
                  <td>#<?= (int)$o['id'] ?></td>
                  <td><?= htmlspecialchars($o['created_at']) ?></td>
                  <td><span class="badge"><?= htmlspecialchars($o['status']) ?></span></td>
                  <td><?= money($o['total']) ?></td>
                  <?php if ($showAll && is_admin()): ?>
                    <td class="small"><?= htmlspecialchars($o['email'] ?? '—') ?></td>
                  <?php endif; ?>
                  <td><a class="btn secondary" href="order.php?id=<?= (int)$o['id'] ?>">View</a></td>
                </tr>
              <?php endforeach; ?>
            </table>
            <?php if ($showAll && is_admin()): ?>
              <div class="row" style="margin-top:10px">
                <button class="btn" type="submit" name="reassign" value="1">Reassign selected to me</button>
              </div>
            <?php endif; ?>
          </form>
        <?php else: ?>
          <div class="toast warning">No orders found for this account.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Saved builds card -->
    <div class="card">
      <div class="p">
        <h3>Saved Builds</h3>
        <?php if ($builds): ?>
          <table class="table">
            <tr>
              <th>Name</th>
              <th>Created</th>
              <th></th>
            </tr>
            <?php foreach ($builds as $b): ?>
              <tr>
                <td><?= htmlspecialchars($b['name']) ?></td>
                <td><?= htmlspecialchars($b['created_at']) ?></td>
                <td><a class="btn secondary" href="build_view.php?id=<?= (int)$b['id'] ?>">Summary</a></td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php else: ?>
          <div class="small">No saved builds yet.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Addresses card -->
    <div class="card">
      <div class="p">
        <h3>Saved Addresses</h3>
        <?php
        $addrStmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC, created_at DESC");
        $addrStmt->execute([(int)$me['id']]);
        $addresses = $addrStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if ($addresses): ?>
          <table class="table">
            <tr>
              <th>Label</th>
              <th>Recipient</th>
              <th>Phone</th>
              <th>Address</th>
              <th>Default</th>
              <th></th>
            </tr>
            <?php foreach ($addresses as $a): ?>
              <tr>
                <td><?= htmlspecialchars($a['label'] ?: '-') ?></td>
                <td><?= htmlspecialchars($a['recipient_name']) ?></td>
                <td><?= htmlspecialchars($a['phone']) ?></td>
                <td>
                  <?= htmlspecialchars($a['street_address']) ?>,
                  <?= htmlspecialchars($a['area']) ?>,
                  <?= htmlspecialchars($a['city']) ?>,
                  <?= htmlspecialchars($a['state']) ?> -
                  <?= htmlspecialchars($a['postal_code']) ?>,
                  <?= htmlspecialchars($a['country']) ?>
                </td>
                <td><?= $a['is_default'] ? "✅" : "" ?></td>
                <td>
                  <a class="btn small secondary" href="address_edit.php?id=<?= (int)$a['id'] ?>">Edit</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php else: ?>
          <div class="small">No saved addresses yet.</div>
        <?php endif; ?>

        <div style="margin-top:10px">
          <a class="btn" href="address_add.php">➕ Add New Address</a>
        </div>
      </div>
    </div>
  </div>

  <div id="toast"></div>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>

</html>