<?php
require_once __DIR__ . '/helpers.php';
ensure_schema();
$pdo = db();

/**
 * Helpers
 */
function esc($s) {
  return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Detect whether categories table has an `image` column.
 * This makes the template tolerant to both DB schemas.
 */
function categories_have_image_col(PDO $pdo) {
  try {
    $info = $pdo->query("PRAGMA table_info('categories')")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($info as $col) {
      if (isset($col['name']) && $col['name'] === 'image') return true;
    }
  } catch (Exception $e) {
    // If PRAGMA fails (non-SQLite), try generic SQL information_schema for MySQL
    try {
      $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'categories' AND COLUMN_NAME = 'image' LIMIT 1");
      $stmt->execute();
      $r = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($r && isset($r['COLUMN_NAME'])) return true;
    } catch (Exception $_) {
      // ignore
    }
  }
  return false;
}

$hasCategoryImage = categories_have_image_col($pdo);

/**
 * Fetch quick categories (limited)
 */
try {
  if ($hasCategoryImage) {
    $catStmt = $pdo->query("SELECT id, name, image FROM categories ORDER BY name LIMIT 12");
  } else {
    $catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name LIMIT 12");
  }
  $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  // graceful fallback
  $categories = [];
}

/**
 * Trending products (safe prepared statement)
 */
try {
  $stmt = $pdo->prepare("SELECT p.*, c.name AS cat FROM products p JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC LIMIT :lim");
  $stmt->bindValue(':lim', 12, PDO::PARAM_INT);
  $stmt->execute();
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $products = [];
}

/**
 * Minimal site-level KPIs (non-critical)
 */
function fetch_kpi(PDO $pdo, $sql) {
  try {
    return (int)$pdo->query($sql)->fetchColumn();
  } catch (Exception $e) {
    return 0;
  }
}
$kpi_products = fetch_kpi($pdo, "SELECT COUNT(*) FROM products");
$kpi_sellers  = fetch_kpi($pdo, "SELECT COUNT(DISTINCT seller_id) FROM products");
$kpi_orders   = fetch_kpi($pdo, "SELECT COUNT(*) FROM orders");

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>PCBANAO P2 — Home</title>
  <base href="/pcbanaop2/">
  <link rel="stylesheet" href="assets/styles.css">
  <script defer src="assets/app.js"></script>
  <style>
    /* tiny helpers so page looks good even if external CSS hasn't loaded yet */
    .sr-only { position: absolute !important; width: 1px; height: 1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); border:0; }
    .trending-empty { padding: 28px; text-align:center; color: #6b7280; }

    /* ------------------ New UI additions (scoped) ------------------ */
    /* Section wrapper */
    .pc-section { margin-top:28px; padding:18px; background: linear-gradient(180deg, rgba(255,255,255,0.0), rgba(255,255,255,0.0)); border-radius:10px; }

    /* Featured Brands slider */
    .brands-slider { display:flex; gap:14px; align-items:center; position:relative; margin-top:12px; }
    .brand-track { display:flex; gap:14px; overflow:hidden; scroll-behavior:smooth; padding:6px; }
    .brand-card { min-width:160px; background:var(--surface, #fff); border:1px solid var(--border, rgba(20,30,40,0.06)); border-radius:10px; padding:12px; display:flex; flex-direction:column; align-items:center; justify-content:center; box-shadow: var(--shadow-soft, 0 8px 22px rgba(10,20,34,0.06)); }
    .brand-card img { width:120px; height:60px; object-fit:contain; opacity:0.95; }

    .slider-controls { display:flex; gap:8px; align-items:center; margin-left:8px; }
    .slider-prev, .slider-next { background:transparent; border:1px solid var(--border, rgba(20,30,40,0.06)); padding:8px; border-radius:8px; cursor:pointer; }

    /* Testimonials slider */
    .testimonials { display:flex; gap:12px; align-items:center; margin-top:12px; overflow:hidden; }
    .test-track { display:flex; gap:12px; transition:transform .36s cubic-bezier(.22,.9,.35,1); }
    .testimonial { min-width:320px; max-width:320px; background:var(--surface, #fff); border-radius:10px; padding:14px; border:1px solid var(--border); box-shadow:var(--shadow-soft); }

    /* Assembly guides */
    .guides-grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:14px; margin-top:12px; }
    .guide { background:var(--surface, #fff); border-radius:10px; padding:12px; border:1px solid var(--border); box-shadow:var(--shadow-soft); display:flex; flex-direction:column; gap:8px; }
    .guide h4 { margin:4px 0; }
    .guide p { margin:0; color:var(--muted, #6b7785); font-size:0.95rem; }

    /* Compatibility checker */
    .compat-card { background:var(--surface, #fff); border-radius:10px; padding:14px; border:1px solid var(--border); box-shadow:var(--shadow-soft); margin-top:12px; }
    .compat-row { display:flex; gap:8px; align-items:center; }
    .compat-result { margin-top:10px; padding:10px; border-radius:8px; background:var(--muted-surface, #eef3fb); color:var(--text); }

    /* Blog teasers */
    .blog-grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:14px; margin-top:12px; }
    .blog-card { background:var(--surface); border-radius:10px; padding:12px; border:1px solid var(--border); box-shadow:var(--shadow-soft); }

    /* Video */
    .video-wrap { margin-top:12px; border-radius:10px; overflow:hidden; background:var(--surface); border:1px solid var(--border); }

    /* FAQ */
    .faq { margin-top:12px; display:grid; gap:8px; }
    .faq-item { background:var(--surface); border-radius:8px; padding:12px; border:1px solid var(--border); }
    .faq-q { font-weight:700; margin-bottom:6px; }
    .faq-a { color:var(--muted); }

    /* Newsletter */
    .newsletter { margin-top:16px; display:flex; gap:10px; align-items:center; }
    .newsletter input { padding:10px 12px; border-radius:8px; border:1px solid var(--border); width:320px; }
    .newsletter button { padding:10px 14px; border-radius:8px; }

    /* Responsive */
    @media (max-width: 980px) {
      .guides-grid, .blog-grid { grid-template-columns: repeat(2,1fr); }
    }
    @media (max-width: 700px) {
      .guides-grid, .blog-grid { grid-template-columns: 1fr; }
      .brands-slider { flex-direction:column; align-items:flex-start; }
    }

    /* small helper */
    .muted { color: var(--muted); font-size:0.95rem; }
    .hr { height:1px; background:var(--border); margin:12px 0; border-radius:2px; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/partials/header.php'; ?>

  <main class="container" role="main" aria-labelledby="home-heading">
    <header class="hero" aria-label="Hero and quick categories">
      <div class="banner" style="padding:28px;">
        <h1 id="home-heading">Build. Buy. Boom. — Parts & builds made simple</h1>
        <p class="small">Smart compatibility filters, saved builds, seller-managed inventory and fast checkout. Start a build or browse curated components.</p>

        <div class="row" style="margin-top:14px;">
          <a class="btn" href="build_pc.php">Start Building</a>
          <a class="btn secondary" href="categories.php">Browse Components</a>
          <a class="btn secondary" href="saved_builds.php">Saved Builds</a>
        </div>

        <div class="kpis" style="margin-top:18px;">
          <div class="kpi"><div class="small text-muted">Products</div><div style="font-weight:800"><?= number_format($kpi_products) ?></div></div>
          <div class="kpi"><div class="small text-muted">Sellers</div><div style="font-weight:800"><?= number_format($kpi_sellers) ?></div></div>
          <div class="kpi"><div class="small text-muted">Orders</div><div style="font-weight:800"><?= number_format($kpi_orders) ?></div></div>
          <div class="kpi"><div class="small text-muted">Support</div><div style="font-weight:800">24/7</div></div>
        </div>
      </div>

      <aside aria-labelledby="quick-cat-heading" style="padding:8px;">
        <h3 id="quick-cat-heading" class="m-0">Quick Categories</h3>
        <div class="quick-vertical" style="margin-top:10px;">
          <?php if (count($categories) === 0): ?>
            <div class="trending-empty">No categories yet — add categories in the admin to populate here.</div>
          <?php else: ?>
            <?php foreach ($categories as $c):
              // If image column exists and row contains it, use it (with safe fallback)
              $catImg = 'assets/placeholder.jpg';
              if ($hasCategoryImage && !empty($c['image'])) {
                $possible = __DIR__ . '/uploads/' . $c['image'];
                if (file_exists($possible)) $catImg = 'uploads/' . rawurlencode($c['image']);
              }
            ?>
              <a class="qc-item" href="categories.php?c=<?= (int)$c['id'] ?>" aria-label="Browse <?= esc($c['name']) ?>">
                <img class="qc-thumb" src="<?= esc($catImg) ?>" alt="<?= esc($c['name']) ?>" loading="lazy" width="64" height="64">
                <div class="qc-meta">
                  <div class="qc-title"><?= esc($c['name']) ?></div>
                  <div class="small">Shop <?= esc($c['name']) ?></div>
                </div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </aside>
    </header>

    <section aria-labelledby="trending-heading" style="margin-top:20px;">
      <div style="display:flex;justify-content:space-between;align-items:end;">
        <h2 id="trending-heading">Trending Products</h2>
        <div class="small text-muted"><?= count($products) ?> featured</div>
      </div>

      <div class="carousel" role="list" aria-live="polite" style="margin-top:12px;">
        <?php if (empty($products)): ?>
          <div class="trending-empty">No trending products yet — add products to see them here.</div>
        <?php else: ?>
          <?php foreach ($products as $p):
            $img = product_image($pdo, $p['id']) ?: 'assets/placeholder.jpg';
            // graceful rating fallback
            $rating = isset($p['rating']) ? (float)$p['rating'] : round((rand(40,50)/10), 1);
            $reviews = isset($p['reviews_count']) ? (int)$p['reviews_count'] : rand(2, 520);
            $seller = !empty($p['seller_name']) ? $p['seller_name'] : 'Marketplace';
          ?>
            <article class="card" role="listitem" aria-labelledby="prod-<?= (int)$p['id'] ?>-title">
              <a href="product.php?id=<?= (int)$p['id'] ?>" style="display:block;">
                <img src="<?= esc($img) ?>" alt="<?= esc($p['title']) ?>" loading="lazy" width="360" height="200">
              </a>
              <div class="p">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                  <div style="flex:1;">
                    <div class="badge"><?= esc($p['cat']) ?></div>
                    <h4 id="prod-<?= (int)$p['id'] ?>-title" style="margin:6px 0 6px;"><?= esc($p['title']) ?></h4>
                    <div class="small text-muted">Sold by <?= esc($seller) ?></div>
                  </div>
                  <div style="text-align:right;min-width:110px;">
                    <div style="font-weight:800"><?= money($p['price']) ?></div>
                    <?php if (!empty($p['fast_shipping'])): ?>
                      <div style="margin-top:6px;"><span class="prime-badge">Fast Ship</span></div>
                    <?php endif; ?>
                  </div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;">
                  <div>
                    <span class="star" aria-hidden="true">★ <?= number_format($rating, 1) ?></span>
                    <span class="small text-muted">(<?= number_format($reviews) ?>)</span>
                  </div>
                  <div>
                    <button class="btn" onclick="addToCart(<?= (int)$p['id'] ?>)">Add to cart</button>
                  </div>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- ==================== NEW: Assembly & Resources Section ==================== -->
    <section class="pc-section" aria-labelledby="assembly-heading">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <h2 id="assembly-heading">PC Assembly & Resources</h2>
        <div class="small text-muted">Guides, compatibility tools, videos & more</div>
      </div>

      <!-- Featured Brands slider -->
    

      <div class="hr"></div>

      <!-- Assembly Guides -->
      <h3 style="margin-top:12px;">Assembly Guides & Checklists</h3>
      <div class="guides-grid" aria-live="polite">
        <article class="guide">
          <h4>Beginner's Guide: Building Your First PC</h4>
          <p class="muted">Step-by-step walkthrough, from picking parts to first boot and BIOS setup. Includes checklist & common pitfalls.</p>
          <a class="btn secondary" href="guides/beginner_build.php">Read guide</a>
        </article>

        <article class="guide">
          <h4>CPU + Motherboard Compatibility</h4>
          <p class="muted">Understand sockets, chipsets, BIOS compatibility and upgrade paths for Intel & AMD systems.</p>
          <a class="btn secondary" href="guides/compatibility.php">Learn more</a>
        </article>

        <article class="guide">
          <h4>Cooling & Thermals</h4>
          <p class="muted">Air vs. AIO vs. custom loop — how to size your cooler and maintain safe temperatures for high-end builds.</p>
          <a class="btn secondary" href="guides/cooling.php">Read tips</a>
        </article>
      </div>

      <div class="hr"></div>

      <!-- Compatibility checker (simple demo) -->
      <h3 style="margin-top:12px;">Compatibility Checker (demo)</h3>
      <div class="compat-card" role="region" aria-label="Compatibility checker" id="compatCard">
        <div class="compat-row">
          <label for="cpu-select" class="sr-only">CPU</label>
          <select id="cpu-select" class="input" style="max-width:320px;">
            <option value="">Select CPU (demo)</option>
            <option value="intel_lga1200">Intel Core i7 (LGA1200)</option>
            <option value="intel_lga1700">Intel Core i9 (LGA1700)</option>
            <option value="amd_am4">AMD Ryzen 5 (AM4)</option>
            <option value="amd_am5">AMD Ryzen 9 (AM5)</option>
          </select>

          <label for="mb-select" class="sr-only">Motherboard</label>
          <select id="mb-select" class="input" style="max-width:320px;">
            <option value="">Select Motherboard (demo)</option>
            <option value="asus_z490">ASUS Prime (LGA1200)</option>
            <option value="msi_z690">MSI MPG (LGA1700)</option>
            <option value="gigabyte_b550">Gigabyte B550 (AM4)</option>
            <option value="asus_x670">ASUS X670 (AM5)</option>
          </select>

          <button class="btn" id="compatCheckBtn" style="margin-left:8px;">Check</button>
        </div>
        <div id="compatResult" class="compat-result" aria-live="polite" style="display:none;"></div>
        <div class="muted" style="margin-top:8px;">This is a demo checker — for production connect to a robust compatibility DB.</div>
      </div>

      <div class="hr"></div>

      <!-- Blog teasers -->
      <h3 style="margin-top:12px;">Latest from the Blog</h3>
      <div class="blog-grid">
        <article class="blog-card">
          <h4>Top 10 Budget GPUs of the Year</h4>
          <p class="muted">Our picks for the best value GPUs for gaming and content creation in different price tiers.</p>
          <a href="blog/gpus-2025.php" class="small">Read →</a>
        </article>
        <article class="blog-card">
          <h4>How to Upgrade BIOS Safely</h4>
          <p class="muted">Step-by-step BIOS update guide without bricking your motherboard. Tested on multiple vendors.</p>
          <a href="blog/bios-upgrade.php" class="small">Read →</a>
        </article>
        <article class="blog-card">
          <h4>Power Supply Sizing Calculator</h4>
          <p class="muted">Quick guide to choosing a PSU: efficiency, rails, cables and headroom considerations.</p>
          <a href="blog/psu-sizing.php" class="small">Read →</a>
        </article>
      </div>

      <div class="hr"></div>

      <!-- Video tutorial -->
      <h3 style="margin-top:12px;">Watch: Step-by-step Build Video</h3>
      <div class="video-wrap">
        <iframe width="100%" height="420" src="https://www.youtube.com/embed/yw02dHyP0K0" title="PC Build Video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
      </div>

      <div class="hr"></div>

      <!-- Testimonials slider -->
      <h3 style="margin-top:12px;">What our builders say</h3>
      <div style="display:flex;align-items:center;">
        <div class="testimonials" aria-hidden="false">
          <div class="test-track" id="testTrack">
            <div class="testimonial">
              <strong>Alex R.</strong>
              <div class="small muted">Verified buyer</div>
              <p class="muted">“PCBANAO helped me pick parts that actually fit together. My first build boots every time — great guides!”</p>
            </div>
            <div class="testimonial">
              <strong>Priya S.</strong>
              <div class="small muted">Verified buyer</div>
              <p class="muted">“Fast shipping and the bundle deals saved me a ton. Seller support answered all my questions.”</p>
            </div>
            <div class="testimonial">
              <strong>Mohammad K.</strong>
              <div class="small muted">Pro builder</div>
              <p class="muted">“I use PCBANAO for quick replacement parts — site is fast and search is reliable.”</p>
            </div>
          </div>
        </div>
        <div style="margin-left:12px;">
          <button class="slider-prev" data-target="testTrack" aria-label="Previous testimonial">‹</button>
          <button class="slider-next" data-target="testTrack" aria-label="Next testimonial">›</button>
        </div>
      </div>

      <div class="hr"></div>

      <!-- FAQ -->
      <h3 style="margin-top:12px;">FAQ</h3>
      <div class="faq">
        <div class="faq-item">
          <div class="faq-q">Do you test compatibility before shipping?</div>
          <div class="faq-a">We provide compatibility filters and guides — sellers are encouraged to confirm compatibility. Use the Compatibility Checker for a quick sanity check.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q">Can I return parts?</div>
          <div class="faq-a">Returns depend on the seller policy. We recommend checking the seller page and contacting support for RMAs.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q">Do you offer assembly services?</div>
          <div class="faq-a">At the moment we provide guidance and curated local sellers; assembly services depend on local partners listed in the seller directory.</div>
        </div>
      </div>

      <div class="hr"></div>

      <!-- Newsletter -->
      <h3 style="margin-top:12px;">Get build tips & deals</h3>
      <div style="display:flex;gap:12px;align-items:center;">
        <form id="newsletterForm" class="newsletter" onsubmit="event.preventDefault(); subscribeNewsletter();">
          <input id="newsEmail" type="email" placeholder="you@example.com" aria-label="Email address">
          <button class="btn" type="submit">Subscribe</button>
        </form>
        <div class="muted">We’ll email you deals and assembly tips once a week. Unsubscribe anytime.</div>
      </div>

    </section>
    <!-- ==================== END: Assembly & Resources Section ==================== -->

    <div style="height:28px;"></div>
  </main>

  <div id="toast" aria-live="polite" aria-atomic="true" style="position:fixed;left:18px;bottom:18px;z-index:200;"></div>

  <?php include __DIR__ . '/partials/footer.php'; ?>

  <!-- ==================== New JS: sliders, compatibility, newsletter, helpers ==================== -->
  <script>
    // ---------- Utilities ----------
    function debounce(fn, ms){ let t; return (...a)=>{ clearTimeout(t); t = setTimeout(()=>fn(...a), ms); }; }
    function showToast(msg, ms=2400){
      const t = document.getElementById('toast');
      if(!t) return;
      const node = document.createElement('div');
      node.className = 'toast';
      node.innerText = msg;
      t.appendChild(node);
      setTimeout(()=>{ node.style.opacity = '0'; node.addEventListener('transitionend', ()=> node.remove()); }, ms);
    }

    // ---------- AddToCart fallback (preserve original behavior) ----------
    if (typeof addToCart !== 'function') {
      window.cart = window.cart || { items: [] };
      window.addToCart = function (id, qty = 1, opts = {}) {
        const productId = parseInt(id,10) || Date.now();
        const existing = window.cart.items.find(it => it.id === productId);
        if (existing) existing.qty = (existing.qty || 0) + qty;
        else window.cart.items.push({ id: productId, qty: qty, title: opts.title || ('Item ' + productId), price: opts.price || '', image: opts.image || '' });
        try { localStorage.setItem('pcb_cart', JSON.stringify(window.cart)); } catch(e){}
        showToast('Added to cart: ' + (opts.title || ('Item ' + productId)));
        // update any visible mini-cart count (if present)
        const count = document.getElementById('cartCount');
        if (count) count.innerText = window.cart.items.length;
      };
    } else {
      // if site provides addToCart, leave it
    }

    // ---------- Brands slider (simple track scroll with controls & autoplay) ----------
    (function brandsSlider(){
      const prevBtn = document.querySelector('.brands-slider .slider-prev') || document.querySelector('.slider-prev[data-target="brandTrack"]');
      const nextBtn = document.querySelector('.brands-slider .slider-next') || document.querySelector('.slider-next[data-target="brandTrack"]');
      const track = document.getElementById('brandTrack');
      if(!track) return;

      // Controls
      document.querySelectorAll('[data-target="brandTrack"]').forEach(btn=>{
        btn.addEventListener('click', ()=> {
          const dir = btn.classList.contains('slider-next') ? 1 : -1;
          track.scrollBy({ left: dir * 200, behavior:'smooth' });
        });
      });

      // autoplay
      let auto = setInterval(()=> { track.scrollBy({ left: 200, behavior:'smooth' }); }, 3500);
      track.addEventListener('mouseenter', ()=> clearInterval(auto));
      track.addEventListener('mouseleave', ()=> { auto = setInterval(()=> { track.scrollBy({ left: 200, behavior:'smooth' }); }, 3500); });
    })();

    // ---------- Testimonials slider ----------
    (function testSlider(){
      const track = document.getElementById('testTrack');
      if (!track) return;
      const prev = document.querySelector('.slider-prev[data-target="testTrack"]');
      const next = document.querySelector('.slider-next[data-target="testTrack"]');
      let index = 0;
      const items = track.children;
      function update() {
        const w = items[0] ? items[0].getBoundingClientRect().width + parseFloat(getComputedStyle(track).gap || 12) : 0;
        track.style.transform = 'translateX(' + (-index * (w)) + 'px)';
      }
      if (prev) prev.addEventListener('click', ()=> { index = Math.max(0, index-1); update(); });
      if (next) next.addEventListener('click', ()=> { index = Math.min(items.length-1, index+1); update(); });

      // autoplay
      let auto = setInterval(()=> { index = (index + 1) % items.length; update(); }, 4200);
      track.addEventListener('mouseenter', ()=> clearInterval(auto));
      track.addEventListener('mouseleave', ()=> { auto = setInterval(()=> { index = (index + 1) % items.length; update(); }, 4200); });
      window.addEventListener('resize', debounce(update, 120));
      update();
    })();

    // ---------- Compatibility Checker (demo logic) ----------
    (function compatDemo(){
      const mapping = {
        'intel_lga1200': 'LGA1200',
        'intel_lga1700': 'LGA1700',
        'amd_am4': 'AM4',
        'amd_am5': 'AM5'
      };
      const mbmap = {
        'asus_z490': 'LGA1200',
        'msi_z690': 'LGA1700',
        'gigabyte_b550': 'AM4',
        'asus_x670': 'AM5'
      };
      const cpuSel = document.getElementById('cpu-select');
      const mbSel = document.getElementById('mb-select');
      const btn = document.getElementById('compatCheckBtn');
      const out = document.getElementById('compatResult');

      if (!cpuSel || !mbSel || !btn || !out) return;

      btn.addEventListener('click', ()=> {
        const cpu = cpuSel.value;
        const mb = mbSel.value;
        if (!cpu || !mb) {
          out.style.display = 'block';
          out.innerHTML = '<strong style="color:#b45309">Select both CPU and motherboard to check compatibility.</strong>';
          return;
        }
        const cpuSocket = mapping[cpu];
        const mbSocket = mbmap[mb];
        if (cpuSocket === mbSocket) {
          out.style.display = 'block';
          out.innerHTML = '<strong style="color:green">Compatible ✔</strong><div class="muted">Socket: ' + cpuSocket + '</div>';
        } else {
          out.style.display = 'block';
          out.innerHTML = '<strong style="color:#b91c1c">Not compatible ✖</strong><div class="muted">CPU socket: ' + cpuSocket + ' • MB socket: ' + mbSocket + '</div>';
        }
      });
    })();

    // ---------- Newsletter stub ----------
    function subscribeNewsletter(){
      const email = document.getElementById('newsEmail').value.trim();
      if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
        showToast('Please enter a valid email address.');
        return;
      }
      // demo: pretend to send — in production send to server endpoint
      showToast('Thanks — subscription saved: ' + email);
      document.getElementById('newsEmail').value = '';
    }

    // ---------- Accessibility: keyboard slider controls ----------
    document.addEventListener('keydown', function(e){
      if (e.key === 'ArrowRight') {
        document.querySelectorAll('.slider-next').forEach(btn => btn.click());
      } else if (e.key === 'ArrowLeft') {
        document.querySelectorAll('.slider-prev').forEach(btn => btn.click());
      } else if (e.key === 'Escape') {
        // close quick view if present
        const qv = document.getElementById('quickViewModal');
        if (qv && qv.getAttribute('aria-hidden') === 'false') {
          qv.setAttribute('aria-hidden','true');
          qv.style.display = 'none';
          document.body.style.overflow = '';
        }
      }
    });

    // Initialize mini cart count from localStorage (if fallback preserved earlier)
    (function initCartCount(){
      try {
        const stored = localStorage.getItem('pcb_cart');
        if (stored) {
          const parsed = JSON.parse(stored);
          if (parsed && Array.isArray(parsed.items)) {
            const countEl = document.getElementById('cartCount');
            if (countEl) countEl.innerText = parsed.items.length;
            window.cart = parsed;
          }
        }
      } catch(e){}
    })();
  </script>

  <!-- ==================== Original lightweight addToCart fallback (kept) ==================== -->
  <script>
    // Lightweight fallback for addToCart if your assets/app.js doesn't implement it.
    if (typeof addToCart !== 'function') {
      window.cart = window.cart || { items: [] };
      window.addToCart = function (id, qty = 1) {
        // find title and price from DOM for friendly toast
        var el = document.querySelector('[onclick*="addToCart(' + id + ')"]');
        var title = 'Item #' + id;
        var price = '';
        if (el) {
          var card = el.closest('.card') || el.closest('.product-card');
          if (card) {
            var h = card.querySelector('h4');
            if (h) title = h.innerText.trim();
            var p = card.querySelector('[style*="font-weight:800"]');
            if (p) price = p.innerText.trim();
          }
        }
        window.cart.items.push({ id: id, qty: qty, title: title, price: price });

        // simple toast
        var t = document.getElementById('toast');
        if (!t) return;
        var node = document.createElement('div');
        node.className = 'toast';
        node.innerText = 'Added to cart: ' + title;
        t.appendChild(node);
        setTimeout(function(){ node.style.opacity = '0'; node.addEventListener('transitionend', function(){ node.remove(); }); }, 2400);
      };
    }
  </script>
</body>
</html>
