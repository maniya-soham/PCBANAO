<?php require_once __DIR__ . '/../helpers.php';
require_login();
require_role('seller');
verify_csrf();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $cid = intval($_POST['category_id'] ?? 0);
  $price = intval($_POST['price'] ?? 0);
  $stock = intval($_POST['stock'] ?? 0);
  $desc = trim($_POST['description'] ?? '');
  $attrs = json_encode($_POST['attr'] ?? []);
  $pdo->prepare("INSERT INTO products (seller_id,category_id,title,description,price,stock,attributes) VALUES (?,?,?,?,?,?,?)")
    ->execute([current_user()['id'], $cid, $title, $desc, $price, $stock, $attrs]);
  $pid = $pdo->lastInsertId();
if (!$pid) {
    die("Product insert failed, no ID returned");
}

  if ($path = handle_upload('image')) {
    $stmt = $pdo->prepare("INSERT INTO product_images (product_id,path) VALUES (?,?)");
    if (!$stmt->execute([$pid, $path])) {
        var_dump($stmt->errorInfo());
    }
}

  flash('msg', 'Product added');
  header('Location: /pcbanaop2/seller/dashboard.php');
  exit;
}
$cats = $pdo->query("SELECT * FROM categories ORDER BY name");
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <base href="/pcbanaop2/">

  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Add Product</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
  <?php include __DIR__ . '/../partials/header.php'; ?>
  <h1>New Product</h1>
  <form method="post" enctype="multipart/form-data">
    <?php csrf_field(); ?>
    <label>Title</label><input class="input" name="title" required>
    <label>Category</label><select name="category_id" class="input" required>
      <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
    </select>
    <div class="row">
      <div style="flex:1"><label>Price (₹)</label><input class="input" type="number" name="price" required></div>
      <div style="flex:1"><label>Stock</label><input class="input" type="number" name="stock" required></div>
    </div>
    <label>Description</label><textarea class="input" name="description"></textarea>
    <h3>Attributes (used for sorting/filtering)</h3>
    <div class="row">
      <div style="flex:1"><label>Socket</label><input class="input" name="attr[socket]"></div>
      <div style="flex:1"><label>RAM Type</label><input class="input" name="attr[ram_type]" placeholder="DDR4 / DDR5"></div>
    </div>
    <div class="row">
      <div style="flex:1"><label>Watt/TDP</label><input class="input" type="number" name="attr[watt]"></div>
      <div style="flex:1"><label>GPU Length (mm)</label><input class="input" type="number" name="attr[length_mm]"></div>
      <div style="flex:1"><label>Case Max GPU Length (mm)</label><input class="input" type="number" name="attr[gpu_max_len_mm]"></div>
    </div>
    <label>Product Image</label><input type="file" name="image" accept="image/*">
    <button class="btn">Save</button>
  </form>
  <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>

</html>