<?php require_once __DIR__ . '/../helpers.php';
require_login();
require_role('seller');
verify_csrf();
$pdo = db();
$id = intval($_GET['id'] ?? 0);
$p = $pdo->query("SELECT * FROM products WHERE id=$id AND seller_id=" . intval(current_user()['id']))->fetch();
if (!$p) {
  die('Not found');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $cid = intval($_POST['category_id'] ?? 0);
  $price = intval($_POST['price'] ?? 0);
  $stock = intval($_POST['stock'] ?? 0);
  $desc = trim($_POST['description'] ?? '');
  $attrs = json_encode($_POST['attr'] ?? []);
  $pdo->prepare("UPDATE products SET category_id=?, title=?, description=?, price=?, stock=?, attributes=? WHERE id=?")
    ->execute([$cid, $title, $desc, $price, $stock, $attrs, $id]);
  if ($path = handle_upload('image')) {
    $pdo->prepare("INSERT INTO product_images (product_id,path) VALUES (?,?)")->execute([$id, $path]);
  }
  flash('msg', 'Product updated');
  header('Location: /pcbanaop2/seller/dashboard.php');
  exit;
}
$cats = $pdo->query("SELECT * FROM categories ORDER BY name");
$attr = json_decode($p['attributes'], true) ?? [];
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <base href="/pcbanaop2/">

  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Edit Product</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
  <?php include __DIR__ . '/../partials/header.php'; ?>
  <h1>Edit Product</h1>
  <form method="post" enctype="multipart/form-data">
    <?php csrf_field(); ?>
    <label>Title</label><input class="input" name="title" value="<?= htmlspecialchars($p['title']) ?>" required>
    <label>Category</label><select name="category_id" class="input" required>
      <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $c['id'] == $p['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
    </select>
    <div class="row">
      <div style="flex:1"><label>Price (₹)</label><input class="input" type="number" name="price" value="<?= $p['price'] ?>" required></div>
      <div style="flex:1"><label>Stock</label><input class="input" type="number" name="stock" value="<?= $p['stock'] ?>" required></div>
    </div>
    <label>Description</label><textarea class="input" name="description"><?= htmlspecialchars($p['description']) ?></textarea>
    <h3>Attributes</h3>
    <div class="row">
      <div style="flex:1"><label>Socket</label><input class="input" name="attr[socket]" value="<?= htmlspecialchars($attr['socket'] ?? '') ?>"></div>
      <div style="flex:1"><label>RAM Type</label><input class="input" name="attr[ram_type]" value="<?= htmlspecialchars($attr['ram_type'] ?? '') ?>"></div>
    </div>
    <div class="row">
      <div style="flex:1"><label>Watt/TDP</label><input class="input" type="number" name="attr[watt]" value="<?= htmlspecialchars($attr['watt'] ?? '') ?>"></div>
      <div style="flex:1"><label>GPU Length (mm)</label><input class="input" type="number" name="attr[length_mm]" value="<?= htmlspecialchars($attr['length_mm'] ?? '') ?>"></div>
      <div style="flex:1"><label>Case Max GPU Length (mm)</label><input class="input" type="number" name="attr[gpu_max_len_mm]" value="<?= htmlspecialchars($attr['gpu_max_len_mm'] ?? '') ?>"></div>
    </div>
    <label>Add New Image</label><input type="file" name="image" accept="image/*">
    <button class="btn">Save</button>
  </form>
  <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>

</html>