<?php
// seller/seed_gpus.php
// Seeder: insert GPU products into products table using the same insert structure
// as your New Product page (seller_id, category_id, title, description, price, stock, attributes).
// IMPORTANT: Backup your app.db before running.

require_once __DIR__ . '/../helpers.php';
require_login();
require_role('seller');

$pdo = db();
$seller_id = (int) current_user()['id'];

// GPU rows to insert
$rows = [
    [
        'title' => 'NVIDIA GeForce RTX 3060 12GB',
        'category' => 'GPU',
        'price' => 34999,
        'stock' => 7,
        'description' => 'Mainstream 1080p/1440p GPU, 12GB VRAM',
        'socket' => 'N/A',
        'ram_type' => 'N/A',
        'watt' => 170,            // estimated board power / TDP
        'length_mm' => 242,
        'gpu_max_len_mm' => 350,
        'product_image' => 'rtx_3060.jpg'
    ],
    [
        'title' => 'NVIDIA GeForce RTX 3070 8GB',
        'category' => 'GPU',
        'price' => 54999,
        'stock' => 5,
        'description' => 'High-end 1440p GPU, excellent performance',
        'socket' => 'N/A',
        'ram_type' => 'N/A',
        'watt' => 220,
        'length_mm' => 267,
        'gpu_max_len_mm' => 330,
        'product_image' => 'rtx_3070.jpg'
    ],
    [
        'title' => 'AMD Radeon RX 6600 XT 8GB',
        'category' => 'GPU',
        'price' => 29999,
        'stock' => 9,
        'description' => 'Good value 1080p GPU from AMD',
        'socket' => 'N/A',
        'ram_type' => 'N/A',
        'watt' => 160,
        'length_mm' => 245,
        'gpu_max_len_mm' => 310,
        'product_image' => 'rx_6600xt.jpg'
    ],
    [
        'title' => 'AMD Radeon RX 6700 XT 12GB',
        'category' => 'GPU',
        'price' => 42999,
        'stock' => 4,
        'description' => 'Strong 1440p AMD GPU with 12GB memory',
        'socket' => 'N/A',
        'ram_type' => 'N/A',
        'watt' => 230,
        'length_mm' => 267,
        'gpu_max_len_mm' => 330,
        'product_image' => 'rx_6700xt.jpg'
    ],
    [
        'title' => 'NVIDIA GeForce RTX 3080 10GB',
        'category' => 'GPU',
        'price' => 99999,
        'stock' => 2,
        'description' => 'High-end 4K GPU for serious gamers',
        'socket' => 'N/A',
        'ram_type' => 'N/A',
        'watt' => 320,
        'length_mm' => 285,
        'gpu_max_len_mm' => 340,
        'product_image' => 'rtx_3080.jpg'
    ],
    [
        'title' => 'ASUS TUF Gaming GeForce GTX 1660 Super',
        'category' => 'GPU',
        'price' => 22999,
        'stock' => 11,
        'description' => 'Solid 1080p GPU targeted at budget gamers',
        'socket' => 'N/A',
        'ram_type' => 'N/A',
        'watt' => 125,
        'length_mm' => 235,
        'gpu_max_len_mm' => 300,
        'product_image' => 'asus_gtx_1660s.jpg'
    ],
    [
        'title' => 'ZOTAC Gaming GeForce GTX 1650',
        'category' => 'GPU',
        'price' => 14999,
        'stock' => 25,
        'description' => 'Entry-level GPU for light gaming and HTPCs',
        'socket' => 'N/A',
        'ram_type' => 'N/A',
        'watt' => 75,
        'length_mm' => 200,
        'gpu_max_len_mm' => 280,
        'product_image' => 'zotac_gtx1650.jpg'
    ],
    [
        'title' => 'Palit Gaming RTX 3050',
        'category' => 'GPU',
        'price' => 24999,
        'stock' => 16,
        'description' => 'Affordable RTX card with ray-tracing on budget',
        'socket' => 'N/A',
        'ram_type' => 'N/A',
        'watt' => 130,
        'length_mm' => 230,
        'gpu_max_len_mm' => 310,
        'product_image' => 'palit_rtx3050.jpg'
    ],
    [
        'title' => 'MSI Ventus RTX 3060 Ti',
        'category' => 'GPU',
        'price' => 45999,
        'stock' => 6,
        'description' => 'Powerful 1440p GPU with good thermals',
        'socket' => 'N/A',
        'ram_type' => 'N/A',
        'watt' => 200,
        'length_mm' => 250,
        'gpu_max_len_mm' => 330,
        'product_image' => 'msi_3060ti.jpg'
    ],
];

// Helpers
function getOrCreateCategory(PDO $pdo, $name) {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) return (int)$row['id'];

    $ins = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
    $ins->execute([$name]);
    return (int)$pdo->lastInsertId();
}

function productExists(PDO $pdo, $title) {
    $stmt = $pdo->prepare("SELECT id FROM products WHERE title = ? LIMIT 1");
    $stmt->execute([$title]);
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

// Insert
$inserted = 0;
$skipped = 0;
$errors = [];

try {
    $pdo->beginTransaction();

    $ins = $pdo->prepare("INSERT INTO products (seller_id,category_id,title,description,price,stock,attributes) VALUES (?,?,?,?,?,?,?)");

    foreach ($rows as $r) {
        if (productExists($pdo, $r['title'])) {
            $skipped++;
            continue;
        }

        $catId = getOrCreateCategory($pdo, $r['category']);

        $attributes = [
            'socket' => $r['socket'],
            'ram_type' => $r['ram_type'],
            'watt' => $r['watt'],
            'length_mm' => $r['length_mm'],
            'gpu_max_len_mm' => $r['gpu_max_len_mm'],
            'product_image' => $r['product_image']
        ];
        $attrs_json = json_encode($attributes, JSON_UNESCAPED_UNICODE);

        $ins->execute([
            $seller_id,
            $catId,
            $r['title'],
            $r['description'],
            $r['price'],
            $r['stock'],
            $attrs_json
        ]);

        $inserted++;
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $errors[] = $e->getMessage();
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Seed GPUs — Result</title>
  <style>body{font-family:Arial,Helvetica,sans-serif;padding:18px} .ok{color:green} .err{color:red}</style>
</head>
<body>
  <h1>Seeder — Insert GPUs</h1>
  <p class="ok">Inserted: <?= htmlspecialchars($inserted) ?></p>
  <p class="ok">Skipped (already existed): <?= htmlspecialchars($skipped) ?></p>

  <?php if ($errors): ?>
    <h3 class="err">Errors</h3>
    <ul>
      <?php foreach ($errors as $er): ?>
        <li><?= htmlspecialchars($er) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p>No errors reported.</p>
  <?php endif; ?>

  <p><a href="dashboard.php">← Back to Dashboard</a></p>
  <hr>
  <p><small>Note: watt values are estimated TDP/power draw for each GPU. Adjust if you prefer exact manufacturer specs.</small></p>
</body>
</html>
