<?php
require_once __DIR__ . '/helpers.php';
ensure_schema();
$pdo = db();

// --- Helpers ---
function get_attr($p, $k) {
  if (!$p) return null;
  $a = json_decode($p['attributes'] ?? '[]', true);
  return $a[$k] ?? null;
}
function row_by_id($pdo, $id) {
  if (!$id) return null;
  $st = $pdo->prepare("SELECT * FROM products WHERE id=?");
  $st->execute([$id]);
  return $st->fetch();
}
function fetch_options($pdo, $cat, $filters = [], $orderBy = 'p.id DESC') {
  $q = "SELECT p.*, c.name cat FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE c.name = ?";
  $params = [$cat];

  // Apply JSON filters (SQLite syntax)
  foreach ($filters as $jsonKey => $value) {
    if ($value === null || $value === '' ) continue;
    $q .= " AND json_extract(p.attributes, '$." . $jsonKey . "') = ?";
    $params[] = $value;
  }

  // Range filters
  if (isset($filters['_gpu_max_len_atleast'])) {
    $q .= " AND CAST(json_extract(p.attributes, '$.gpu_max_len_mm') AS INTEGER) >= ?";
    $params[] = (int)$filters['_gpu_max_len_atleast'];
  }
  if (isset($filters['_psu_watt_atleast'])) {
    $q .= " AND CAST(json_extract(p.attributes, '$.watt') AS INTEGER) >= ?";
    $params[] = (int)$filters['_psu_watt_atleast'];
  }

  $q .= " ORDER BY " . $orderBy;
  $st = $pdo->prepare($q);
  $st->execute($params);
  return $st->fetchAll();
}

// --- Selected IDs from querystring ---
$cpu_id  = (int)($_GET['cpu']  ?? 0);
$mb_id   = (int)($_GET['mb']   ?? 0);
$ram_id  = (int)($_GET['ram']  ?? 0);
$gpu_id  = (int)($_GET['gpu']  ?? 0);
$psu_id  = (int)($_GET['psu']  ?? 0);
$case_id = (int)($_GET['case'] ?? 0);

// --- Loaded rows ---
$cpu  = row_by_id($pdo, $cpu_id);
$mb   = row_by_id($pdo, $mb_id);
$ram  = row_by_id($pdo, $ram_id);
$gpu  = row_by_id($pdo, $gpu_id);
$psu  = row_by_id($pdo, $psu_id);
$case = row_by_id($pdo, $case_id);

// --- Derived specs used for filtering ---
$cpu_socket  = get_attr($cpu, 'socket');           // CPU → Motherboard
$mb_ram_type = get_attr($mb, 'ram_type');          // Motherboard → RAM
$ram_type    = $mb_ram_type ?: get_attr($ram, 'ram_type'); // in case MB not chosen yet
$cpu_watt    = (int)(get_attr($cpu, 'watt') ?: 0);
$gpu_tdp     = (int)(get_attr($gpu, 'tdp') ?: 0);
$gpu_len     = (int)(get_attr($gpu, 'length_mm') ?: 0);

// Suggested PSU wattage (at least 450W; CPU+GPU TDP * 1.6)
$suggest_watt = max(450, (int)ceil(($cpu_watt + $gpu_tdp) * 1.6));

// --- Options (fully linked) ---
$cpu_opts = fetch_options($pdo, 'CPU', [], 'p.title ASC');

$mb_filters = [];
if ($cpu_socket) { $mb_filters['socket'] = $cpu_socket; }
$mb_opts = fetch_options($pdo, 'Motherboard', $mb_filters, 'p.title ASC');

$ram_filters = [];
if ($mb_ram_type) { $ram_filters['ram_type'] = $mb_ram_type; }
elseif ($ram_type) { $ram_filters['ram_type'] = $ram_type; } // if user picked RAM first
$ram_opts = fetch_options($pdo, 'RAM', $ram_filters, 'p.price ASC');

$gpu_opts = fetch_options($pdo, 'GPU', [], 'p.title ASC');

$psu_filters = [];
if ($suggest_watt) { $psu_filters['_psu_watt_atleast'] = $suggest_watt; }
$psu_opts = fetch_options($pdo, 'PSU', $psu_filters, 'CAST(json_extract(p.attributes, \'$.watt\') AS INTEGER) ASC');

$case_filters = [];
if ($gpu_len) { $case_filters['_gpu_max_len_atleast'] = $gpu_len; }
$case_opts = fetch_options($pdo, 'Case', $case_filters, 'p.title ASC');

// --- Summary (selected items) ---
$selected_ids = array_filter([$cpu_id, $mb_id, $ram_id, $gpu_id, $psu_id, $case_id]);
$summary = [];
$total = 0;
if ($selected_ids) {
  $in = implode(',', array_map('intval', $selected_ids));
  foreach ($pdo->query("SELECT * FROM products WHERE id IN ($in)") as $p) {
    $summary[] = $p;
    $total += (int)$p['price'];
  }
}

// Simple helper to keep other selections on each <form> submit
function keep_hidden($map) {
  foreach ($map as $k=>$v) {
    echo '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.(int)$v.'">';
  }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Build PC</title>
  <base href="/pcbanaop2/">
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include __DIR__ . '/partials/header.php'; ?>

<h1>Build Your PC</h1>

<div class="grid">
  <!-- CPU -->
  <div class="card">
    <div class="p">
      <h3>CPU</h3>
      <form>
        <?php keep_hidden(['mb'=>$mb_id,'ram'=>$ram_id,'gpu'=>$gpu_id,'psu'=>$psu_id,'case'=>$case_id]); ?>
        <select name="cpu" class="input" onchange="this.form.submit()">
          <option value="0">Select CPU</option>
          <?php foreach ($cpu_opts as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= $cpu_id===$p['id']?'selected':''; ?>>
              <?= htmlspecialchars($p['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
      <?php if ($cpu): ?>
        <div class="small">Socket: <?= htmlspecialchars($cpu_socket) ?> • TDP: <?= (int)$cpu_watt ?>W</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Motherboard (filtered by CPU socket) -->
  <div class="card">
    <div class="p">
      <h3>Motherboard</h3>
      <form>
        <?php keep_hidden(['cpu'=>$cpu_id,'ram'=>$ram_id,'gpu'=>$gpu_id,'psu'=>$psu_id,'case'=>$case_id]); ?>
        <select name="mb" class="input" onchange="this.form.submit()">
          <option value="0">Select Motherboard<?= $cpu_socket ? " (socket: $cpu_socket)" : '' ?></option>
          <?php foreach ($mb_opts as $p):
            $a = json_decode($p['attributes'] ?? '[]', true);
          ?>
            <option value="<?= (int)$p['id'] ?>" <?= $mb_id===$p['id']?'selected':''; ?>>
              <?= htmlspecialchars($p['title']) ?> (<?= htmlspecialchars($a['socket'] ?? '') ?>, <?= htmlspecialchars($a['ram_type'] ?? '') ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </form>
      <?php if ($mb): ?>
        <div class="small">RAM type: <?= htmlspecialchars($mb_ram_type) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- RAM (filtered by Motherboard/CPU ram_type) -->
  <div class="card">
    <div class="p">
      <h3>RAM</h3>
      <form>
        <?php keep_hidden(['cpu'=>$cpu_id,'mb'=>$mb_id,'gpu'=>$gpu_id,'psu'=>$psu_id,'case'=>$case_id]); ?>
        <select name="ram" class="input" onchange="this.form.submit()">
          <option value="0">Select RAM<?= $mb_ram_type ? " (type: $mb_ram_type)" : ($ram_type ? " (type: $ram_type)" : '') ?></option>
          <?php foreach ($ram_opts as $p):
            $a = json_decode($p['attributes'] ?? '[]', true);
          ?>
            <option value="<?= (int)$p['id'] ?>" <?= $ram_id===$p['id']?'selected':''; ?>>
              <?= htmlspecialchars($p['title']) ?> (<?= htmlspecialchars($a['ram_type'] ?? '') ?>, <?= (int)($a['capacity_gb'] ?? 0) ?>GB)
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
  </div>

  <!-- GPU -->
  <div class="card">
    <div class="p">
      <h3>GPU</h3>
      <form>
        <?php keep_hidden(['cpu'=>$cpu_id,'mb'=>$mb_id,'ram'=>$ram_id,'psu'=>$psu_id,'case'=>$case_id]); ?>
        <select name="gpu" class="input" onchange="this.form.submit()">
          <option value="0">Select GPU</option>
          <?php foreach ($gpu_opts as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= $gpu_id===$p['id']?'selected':''; ?>>
              <?= htmlspecialchars($p['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
      <?php if ($gpu): ?>
        <div class="small">Length: <?= (int)$gpu_len ?>mm • TDP: <?= (int)$gpu_tdp ?>W</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- PSU (filtered by suggested watt) -->
  <div class="card">
    <div class="p">
      <h3>PSU</h3>
      <form>
        <?php keep_hidden(['cpu'=>$cpu_id,'mb'=>$mb_id,'ram'=>$ram_id,'gpu'=>$gpu_id,'case'=>$case_id]); ?>
        <select name="psu" class="input" onchange="this.form.submit()">
          <option value="0">Select PSU (suggested ≥ <?= (int)$suggest_watt ?>W)</option>
          <?php foreach ($psu_opts as $p):
            $a = json_decode($p['attributes'] ?? '[]', true);
            $w = (int)($a['watt'] ?? 0);
          ?>
            <option value="<?= (int)$p['id'] ?>" <?= $psu_id===$p['id']?'selected':''; ?>>
              <?= htmlspecialchars($p['title']) ?> (<?= $w ?>W)
            </option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
  </div>

  <!-- Case (filtered by GPU length) -->
  <div class="card">
    <div class="p">
      <h3>Case</h3>
      <form>
        <?php keep_hidden(['cpu'=>$cpu_id,'mb'=>$mb_id,'ram'=>$ram_id,'gpu'=>$gpu_id,'psu'=>$psu_id]); ?>
        <select name="case" class="input" onchange="this.form.submit()">
          <option value="0">Select Case<?= $gpu_len ? " (fits ≥ {$gpu_len}mm GPU)" : '' ?></option>
          <?php foreach ($case_opts as $p):
            $a = json_decode($p['attributes'] ?? '[]', true);
            $fit = (int)($a['gpu_max_len_mm'] ?? 0);
          ?>
            <option value="<?= (int)$p['id'] ?>" <?= $case_id===$p['id']?'selected':''; ?>>
              <?= htmlspecialchars($p['title']) ?> (GPU max <?= $fit ?>mm)
            </option>
          <?php endforeach; ?>
        </select>
      </form>
      <?php if ($case && $gpu && $gpu_len > (int)get_attr($case, 'gpu_max_len_mm')): ?>
        <div class="toast warning">Warning: GPU may not fit in selected case.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
// --- Summary block ---
?>
<h2>Summary</h2>
<div class="grid">
  <div class="card">
    <div class="p">
      <?php if ($summary): ?>
        <ul>
          <?php foreach ($summary as $p): $img = product_image($pdo, $p['id']); ?>
            <li>
              <img src="<?= htmlspecialchars($img) ?>" style="width:40px;height:40px;vertical-align:middle;border-radius:6px;">
              <?= htmlspecialchars($p['title']) ?> — <?= money((int)$p['price']) ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <h3>Total: <?= money($total) ?></h3>

        <?php if (current_user()): ?>
          <form method="post" action="pages/save_build.php">
            <?php csrf_field(); ?>
            <?php foreach (['cpu'=>$cpu_id,'mb'=>$mb_id,'ram'=>$ram_id,'gpu'=>$gpu_id,'psu'=>$psu_id,'case'=>$case_id] as $k=>$v): ?>
              <input type="hidden" name="<?= $k ?>" value="<?= (int)$v ?>">
            <?php endforeach; ?>
            <label>Build name</label>
            <input class="input" name="name" required>
            <button class="btn">Save build</button>
          </form>

          <form method="post" action="pages/order_build.php" style="margin-top:8px">
            <?php csrf_field(); ?>
            <?php foreach (['cpu'=>$cpu_id,'mb'=>$mb_id,'ram'=>$ram_id,'gpu'=>$gpu_id,'psu'=>$psu_id,'case'=>$case_id] as $k=>$v): ?>
              <input type="hidden" name="<?= $k ?>" value="<?= (int)$v ?>">
            <?php endforeach; ?>
            <label>Shipping address</label>
            <input class="input" name="addr" required>
            <button class="btn">Order this build</button>
          </form>
        <?php else: ?>
          <div class="small">Login to save or order your build.</div>
        <?php endif; ?>

      <?php else: ?>
        <div class="small">Select parts to see the summary.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
