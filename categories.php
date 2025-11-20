<?php
require_once __DIR__ . '/helpers.php';
ensure_schema();
$pdo = db();

// Inputs
$cid   = isset($_GET['c']) ? (int)$_GET['c'] : 0;
$q     = trim($_GET['q'] ?? '');
$min   = (int)($_GET['min'] ?? 0);
$max   = (int)($_GET['max'] ?? 0);
$sort  = $_GET['sort'] ?? 'new';
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Build WHERE and params
$where = [];
$params = [];

if ($cid) {
  $where[] = 'c.id = ?';
  $params[] = $cid;
}

if ($q !== '') {
  $where[] = '(p.title LIKE ? OR p.description LIKE ?)';
  $params[] = "%$q%";
  $params[] = "%$q%";
}

if ($min > 0) {
  $where[] = 'p.price >= ?';
  $params[] = $min;
}
if ($max > 0) {
  $where[] = 'p.price <= ?';
  $params[] = $max;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Sorting
$sortSql = match ($sort) {
  'price_asc'  => 'ORDER BY p.price ASC',
  'price_desc' => 'ORDER BY p.price DESC',
  'name_asc'   => 'ORDER BY p.title ASC',
  'name_desc'  => 'ORDER BY p.title DESC',
  default      => 'ORDER BY p.id DESC', // new
};

// Attribute filters (context-aware)
$attrWhere = '';
$attrParams = [];
$socket = trim($_GET['socket'] ?? '');
$ramtype = trim($_GET['ram_type'] ?? '');

if ($cid) {
  $catName = $pdo->prepare('SELECT name FROM categories WHERE id=?');
  $catName->execute([$cid]);
  $catName = $catName->fetch()['name'] ?? '';

  // CPU socket filter
  if ($catName === 'CPU' && $socket !== '') {
    $attrWhere .= ' AND json_extract(p.attributes, "$.socket") = ?';
    $attrParams[] = $socket;
  }

  // Motherboard & RAM – ram_type filter
  if (in_array($catName, ['Motherboard', 'RAM']) && $ramtype !== '') {
    $attrWhere .= ' AND json_extract(p.attributes, "$.ram_type") = ?';
    $attrParams[] = $ramtype;
  }
}

// Count total
$countSql = "SELECT COUNT(*) AS cnt
             FROM products p JOIN categories c ON c.id=p.category_id
             $whereSql $attrWhere";
$stmt = $pdo->prepare($countSql);
$stmt->execute([...$params, ...$attrParams]);
$totalRows = (int)($stmt->fetch()['cnt'] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $limit));

// Fetch page data
$sql = "SELECT p.*, c.name AS cat
        FROM products p JOIN categories c ON c.id=p.category_id
        $whereSql $attrWhere
        $sortSql
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute([...$params, ...$attrParams]);
$rows = $stmt->fetchAll();

// Distinct attribute values for filters (only when a category is selected)
$socketOptions = $ramOptions = [];
if ($cid) {
  $catName2 = $pdo->prepare('SELECT name FROM categories WHERE id=?');
  $catName2->execute([$cid]);
  $cat2 = $catName2->fetch()['name'] ?? '';
  if ($cat2 === 'CPU') {
    $socketOptions = $pdo->query('SELECT DISTINCT json_extract(attributes, "$.socket") AS v FROM products WHERE v IS NOT NULL')->fetchAll();
  }
  if (in_array($cat2, ['Motherboard', 'RAM'])) {
    $ramOptions = $pdo->query('SELECT DISTINCT json_extract(attributes, "$.ram_type") AS v FROM products WHERE v IS NOT NULL')->fetchAll();
  }
}
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <base href="/pcbanaop2/">
  <title>Categories</title>
  <link rel="stylesheet" href="assets/styles.css">
  <script defer src="assets/app.js"></script>
</head>

<body>
  <?php include __DIR__ . '/partials/header.php'; ?>

  <h1>Browse Components</h1>

  <!-- Filters row -->
  <form class="card" style="padding:14px; margin-bottom:14px;">
    <div class="row" style="flex-wrap:wrap; gap:10px">
      <!-- Category -->
      <div style="min-width:200px;flex:1">
        <label class="small">Category</label>
        <select name="c" class="input" onchange="this.form.submit()">
          <option value="0" <?= $cid === 0 ? 'selected' : ''; ?>>All</option>
          <?php foreach ($pdo->query("SELECT * FROM categories ORDER BY name") as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $cid === $c['id'] ? 'selected' : ''; ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Search -->
      <div style="min-width:220px;flex:2">
        <label class="small">Search</label>
        <input class="input" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search titles / description">
      </div>

      <!-- Price -->
      <div style="min-width:140px">
        <label class="small">Min ₹</label>
        <input class="input" type="number" name="min" value="<?= $min ?: '' ?>">
      </div>
      <div style="min-width:140px">
        <label class="small">Max ₹</label>
        <input class="input" type="number" name="max" value="<?= $max ?: '' ?>">
      </div>

      <!-- Sort -->
      <div style="min-width:200px">
        <label class="small">Sort by</label>
        <select name="sort" class="input" onchange="this.form.submit()">
          <option value="new" <?= $sort === 'new' ? 'selected' : ''; ?>>Newest</option>
          <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low → High</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High → Low</option>
          <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : ''; ?>>Name: A → Z</option>
          <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : ''; ?>>Name: Z → A</option>
        </select>
      </div>

      <!-- Context attribute filters -->
      <?php if (!empty($socketOptions)): ?>
        <div style="min-width:200px">
          <label class="small">CPU Socket</label>
          <select name="socket" class="input" onchange="this.form.submit()">
            <option value="">Any</option>
            <?php foreach ($socketOptions as $o):
              $v = $o['v'];
              if ($v === null || $v === '') continue; ?>
              <option value="<?= htmlspecialchars($v) ?>" <?= $socket === $v ? 'selected' : ''; ?>>
                <?= htmlspecialchars($v) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <?php if (!empty($ramOptions)): ?>
        <div style="min-width:200px">
          <label class="small">RAM Type</label>
          <select name="ram_type" class="input" onchange="this.form.submit()">
            <option value="">Any</option>
            <?php foreach ($ramOptions as $o):
              $v = $o['v'];
              if ($v === null || $v === '') continue; ?>
              <option value="<?= htmlspecialchars($v) ?>" <?= $ramtype === $v ? 'selected' : ''; ?>>
                <?= htmlspecialchars($v) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <div style="align-self:flex-end">
        <button class="btn">Apply</button>
      </div>
    </div>
  </form>

  <!-- Results -->
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
    <?php if ($rows): foreach ($rows as $p):
        $img = htmlspecialchars(product_image($pdo, $p['id']));
    ?>
        <div class="card product-card">
          <div class="image-wrap"><img src="<?= $img ?>" alt="<?= htmlspecialchars($p['title']) ?>"></div>
          <div class="p">
            <div class="badge"><?= htmlspecialchars($p['cat']) ?></div>
            <h4><?= htmlspecialchars($p['title']) ?></h4>
            <div><?= money($p['price']) ?></div>

            <!-- AJAX add -->
            <button class="btn secondary"
              style="margin-top:8px;display:inline-block"
              onclick="addToCart(<?= (int)$p['id'] ?>)">
              Add to Cart
            </button>
            <!-- Fallback link in case JS is blocked -->
            <a class="btn secondary" href="pages/cart_add.php?product_id=<?= (int)$p['id'] ?>&qty=1" style="margin-top:8px;display:inline-block">Add & View Cart</a>
          </div>
        </div>
      <?php endforeach;
    else: ?>
      <div class="small">No products match your filters.</div>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <div class="row" style="justify-content:center;margin:18px 0">
      <?php
      // keep current query but change page
      parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
      for ($p = 1; $p <= $totalPages; $p++):
        $qs['page'] = $p;
        $href = 'categories.php?' . http_build_query($qs);
      ?>
        <a class="btn <?= $p === $page ? '' : 'secondary' ?>" href="<?= htmlspecialchars($href) ?>" style="margin:2px 4px"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>

  <div id="toast"></div>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>

</html>