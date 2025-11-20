<?php require_once __DIR__ . '/helpers.php';
$pdo = db();
$id = intval($_GET['id'] ?? 0);
$build = $pdo->query("SELECT * FROM builds WHERE id=$id")->fetch();
if (!$build) {
  die('Not found');
}
$items = $pdo->query("SELECT p.* FROM build_items bi JOIN products p ON p.id=bi.product_id WHERE bi.build_id=$id");
$total = 0;
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Build Summary</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
  <?php include __DIR__ . '/partials/header.php'; ?>
  <h1>Build: <?= htmlspecialchars($build['name']) ?></h1>
  <div class="card">
    <div class="p">
      <ul>
        <?php foreach ($items as $p): $total += $p['price'];
          $img = product_image($pdo, $p['id']); ?>
          <li><img src="<?= $img ?>" style="width:40px;height:40px;vertical-align:middle;border-radius:6px;"> <?= htmlspecialchars($p['title']) ?> — <?= money($p['price']) ?></li>
        <?php endforeach; ?>
      </ul>
      <h3>Total: <?= money($total) ?></h3>
    </div>
  </div>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>

</html>