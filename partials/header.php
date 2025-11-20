<?php require_once __DIR__ . '/../helpers.php'; ?>

<style>
  /* Inline header + cart styles — new palette: purple + gold */
  :root {
    --header-bg: rgba(255, 255, 255, 0.98);
    --muted: #6b7280;
    --text: #111827;
    --accent-1: #7b61ff;
    /* purple */
    --accent-2: #ffb86b;
    /* warm gold */
    --btn-bg: linear-gradient(90deg, var(--accent-1), #6fc3d8);
    --border: rgba(17, 24, 39, 0.06);
    --shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
    --radius: 10px;
    font-family: Inter, system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  }

  /* header layout */
  .nav {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: var(--header-bg);
    border-radius: 12px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    position: sticky;
    top: 12px;
    z-index: 120;
  }

  .nav .container {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
  }

  /* logo */
  .nav .logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 800;
    font-size: 1.05rem;
    color: var(--text);
    text-decoration: none;
    padding: 6px 8px;
    border-radius: 8px;
  }

  .logo-sub {
    font-size: 11px;
    color: var(--muted);
    margin-left: 6px;
  }

  /* links */
  .nav>a:not(.logo):not(.btn):not(.btn.secondary) {
    padding: 8px 10px;
    border-radius: 8px;
    color: var(--text);
    text-decoration: none;
  }

  .nav>a:not(.logo):not(.btn):not(.btn.secondary):hover {
    background: rgba(123, 97, 255, 0.06);
  }

  /* spacer */
  .nav .spacer {
    flex: 1;
  }

  /* buttons */
  .btn {
    border: 0;
    padding: 8px 12px;
    border-radius: 10px;
    background: var(--btn-bg);
    color: #fff;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 6px 18px rgba(123, 97, 255, 0.08);
  }

  .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(123, 97, 255, 0.10);
  }

  .btn.secondary {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text);
    padding: 7px 11px;
  }

  /* cart badge */
  .cart-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.15);
    padding: 2px 8px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 12px;
    margin-left: 6px;
  }

  /* mobile hamburger */
  #mobileMenuBtn {
    display: none;
    border: 0;
    background: transparent;
    padding: 8px;
    font-size: 18px;
    cursor: pointer;
    border-radius: 8px;
  }

  #mobileMenuBtn:focus {
    outline: 3px solid rgba(123, 97, 255, 0.14);
    outline-offset: 2px;
  }

  /* mini cart dialog styles */
  #miniCart {
    position: fixed;
    right: 18px;
    top: 80px;
    z-index: 9999;
    display: none;
  }

  #miniCart[aria-hidden="false"] {
    display: block;
  }

  #miniCart .panel {
    width: 360px;
    background: #fff;
    border-radius: 10px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    overflow: hidden;
  }

  #miniCart .head {
    padding: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  #miniCart .body {
    padding: 12px;
    max-height: 320px;
    overflow: auto;
  }

  #miniCart .foot {
    padding: 12px;
    display: flex;
    gap: 8px;
    justify-content: space-between;
  }

  /* item row */
  .cart-item {
    display: flex;
    gap: 10px;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(17, 24, 39, 0.03);
  }

  .cart-item img {
    width: 56px;
    height: 40px;
    object-fit: cover;
    border-radius: 6px;
  }

  /* mobile drawer */
  #mobileDrawer {
    position: fixed;
    inset: 0;
    z-index: 1100;
    display: none;
  }

  #mobileDrawer[aria-hidden="false"] {
    display: block;
  }

  #mobileDrawer .backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.35);
  }

  #mobileDrawer .panel {
    position: absolute;
    left: 8px;
    top: 8px;
    bottom: 8px;
    width: 300px;
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    overflow: auto;
  }

  /* focus */
  a:focus,
  button:focus,
  input:focus {
    outline: 3px solid rgba(123, 97, 255, 0.14);
    outline-offset: 3px;
    border-radius: 8px;
  }

  /* responsiveness */
  @media (max-width:880px) {
    .nav>a:not(.logo):not(.btn):not(.btn.secondary) {
      display: none;
    }

    #mobileMenuBtn {
      display: inline-block;
    }

    .nav .spacer {
      display: none;
    }

    #miniCart {
      right: 12px;
      top: 68px;
    }
  }
</style>

<div class="nav" role="navigation" aria-label="Main navigation">
  <base href="/pcbanaop2/">

  <button id="mobileMenuBtn" aria-controls="mobileDrawer" aria-expanded="false" aria-label="Open menu" onclick="toggleMobileDrawer()">☰</button>

  <a class="logo" href="index.php" aria-label="PCBANAO home">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <rect width="24" height="24" rx="6" fill="url(#g)"></rect>
      <defs>
        <linearGradient id="g" x1="0" x2="1">
          <stop offset="0" stop-color="#7b61ff" />
          <stop offset="1" stop-color="#6fc3d8" />
        </linearGradient>
      </defs>
    </svg>
    <strong>PCBANAO P2</strong>
    <span class="logo-sub">Build & Buy</span>
  </a>

  <a href="index.php">Home</a>
  <a href="categories.php">Categories</a>
  <a href="build_pc.php">Build PC</a>
  <a href="orders.php">Orders/Cart</a>

  <div class="spacer"></div>

  <div class="header-actions" role="group" aria-label="User actions">
    <?php
      // get current user once
      $user = current_user();
      if ($user):
        // normalize role (lowercase) and protect from undefined index
        $role = isset($user['role']) ? strtolower(trim($user['role'])) : 'user';
    ?>
      <span class="small">Hi, <?= htmlspecialchars($user['name'] ?? 'User') ?></span>

      <?php
        // show dashboard button only for admin or seller
        if ($role === 'admin'): ?>
          <a class="btn secondary" href="admin/dashboard.php" title="Admin dashboard">Admin Dashboard</a>
      <?php
        elseif ($role === 'seller'): ?>
          <a class="btn secondary" href="seller/dashboard.php" title="Seller dashboard">Seller Dashboard</a>
      <?php
        endif;
      ?>

      <a class="btn secondary" href="profile.php">Profile</a>
      <a class="btn" href="logout.php">Logout</a>

    <?php else: ?>
      <a class="btn secondary" href="login.php">Login</a>
      <a class="btn" href="register.php">Sign up</a>
    <?php endif; ?>
  </div>
</div>

<!-- mobile drawer -->
<div id="mobileDrawer" aria-hidden="true">
  <div class="backdrop" onclick="toggleMobileDrawer()"></div>
  <div class="panel" role="dialog" aria-label="Mobile menu">
    <button onclick="toggleMobileDrawer()" style="border:0;background:transparent;padding:6px;margin-bottom:8px;cursor:pointer;font-weight:700">✕ Close</button>
    <nav aria-label="Mobile main">
      <a href="index.php">Home</a>
      <a href="categories.php">Categories</a>
      <a href="build_pc.php">Build PC</a>
      <a href="orders.php">Orders/Cart</a>
      <hr style="border:none;border-top:1px solid var(--border);margin:8px 0;">
      <?php if (current_user()): ?>
        <div style="margin:8px 0;color:var(--muted)">Signed in as <strong><?= htmlspecialchars(current_user()['name']) ?></strong></div>
        <?php if ((isset(current_user()['role']) && strtolower(current_user()['role']) === 'seller')): ?><a href="seller/dashboard.php">Seller Dashboard</a><?php endif; ?>
        <?php if ((isset(current_user()['role']) && strtolower(current_user()['role']) === 'admin')): ?><a href="admin/dashboard.php">Admin Dashboard</a><?php endif; ?>
        <?php if (is_seller()): ?><!-- legacy helper kept if present --><?php endif; ?>
        <?php if (is_admin()): ?><!-- legacy helper kept if present --><?php endif; ?>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Sign up</a>
      <?php endif; ?>
    </nav>
  </div>
</div>

<!-- mini cart -->
<div id="miniCart" role="dialog" aria-hidden="true">
  <div class="panel">
    <div class="head">
      <strong>Your Cart</strong>
      <button onclick="toggleMiniCart()" style="border:0;background:transparent;cursor:pointer;font-weight:700">✕</button>
    </div>
    <div id="miniCartBody" class="body">
      <div class="small" style="color:var(--muted)">Cart is empty</div>
    </div>
    <div class="foot">
      <a class="btn secondary" href="cart.php">View cart</a>
      <a class="btn" href="checkout.php">Checkout</a>
    </div>
  </div>
</div>

<script>
  /* Cart & UI JS: robust fallback + localStorage persistence */

  // ensure window.cart exists and persisted
  (function initCart() {
    try {
      const stored = localStorage.getItem('pcbanao_cart_v1');
      if (stored) {
        window.cart = JSON.parse(stored);
        if (!Array.isArray(window.cart.items)) window.cart = {
          items: []
        };
      } else {
        window.cart = {
          items: []
        };
      }
    } catch (e) {
      window.cart = {
        items: []
      };
    }
    refreshCartUI();
  })();

  function persistCart() {
    try {
      localStorage.setItem('pcbanao_cart_v1', JSON.stringify(window.cart));
    } catch (e) {
      /* ignore */ }
  }

  // safe addToCart fallback if not provided by global scripts
  if (typeof window.addToCart !== 'function') {
    window.addToCart = function(productId, qty = 1, opts = {}) {
      // Accept product info via opts: { title, price, image }
      const id = parseInt(productId, 10) || Date.now();
      const existing = window.cart.items.find(i => i.id === id);
      if (existing) {
        existing.qty = (existing.qty || 0) + qty;
      } else {
        window.cart.items.push({
          id: id,
          title: opts.title || ('Item ' + id),
          price: opts.price || '',
          image: opts.image || '',
          qty: qty
        });
      }
      persistCart();
      refreshCartUI();
      showToast('Added to cart: ' + (opts.title || ('Item ' + id)));
    };
  }

  // update cart count & mini cart body
  function refreshCartUI() {
    const countEl = document.getElementById('cartCount');
    const body = document.getElementById('miniCartBody');
    if (countEl) countEl.innerText = (window.cart && window.cart.items) ? window.cart.items.length : 0;
    if (!body) return;
    const items = (window.cart && window.cart.items) ? window.cart.items : [];
    if (items.length === 0) {
      body.innerHTML = '<div class=\"small\" style=\"color:var(--muted)\">Cart is empty</div>';
      return;
    }
    body.innerHTML = '';
    items.forEach(it => {
      const row = document.createElement('div');
      row.className = 'cart-item';
      const imgHtml = it.image ? '<img src=\"' + it.image + '\" alt=\"\">' : '<div style=\"width:56px;height:40px;border-radius:6px;background:#f3f4f6\"></div>';
      row.innerHTML = '<div style=\"display:flex;gap:10px;align-items:center;flex:1\">' + imgHtml + '<div style=\"flex:1\"><div style=\"font-weight:700\">' + escapeHtml(it.title) + '</div><div class=\"small\" style=\"color:var(--muted)\">Qty: ' + (it.qty || 1) + '</div></div></div><div style=\"min-width:80px;text-align:right\">' + escapeHtml(it.price || '') + '</div>';
      body.appendChild(row);
    });
  }

  // toggle mini cart visibility
  function toggleMiniCart() {
    const mc = document.getElementById('miniCart');
    if (!mc) return;
    const hidden = mc.getAttribute('aria-hidden') === 'true' || mc.style.display === '' || mc.style.display === 'none';
    mc.setAttribute('aria-hidden', hidden ? 'false' : 'true');
    mc.style.display = hidden ? 'block' : 'none';
    if (hidden) refreshCartUI();
  }

  // mobile drawer toggle
  function toggleMobileDrawer() {
    const d = document.getElementById('mobileDrawer');
    const btn = document.getElementById('mobileMenuBtn');
    if (!d) return;
    const open = d.getAttribute('aria-hidden') === 'false';
    d.setAttribute('aria-hidden', open ? 'true' : 'false');
    if (btn) btn.setAttribute('aria-expanded', open ? 'false' : 'true');
  }

  // small toast utility
  function showToast(message, ms = 2400) {
    try {
      const t = document.createElement('div');
      t.className = 'toast';
      t.style.position = 'fixed';
      t.style.left = '18px';
      t.style.bottom = '18px';
      t.style.zIndex = '99999';
      t.innerText = message;
      document.body.appendChild(t);
      setTimeout(() => {
        t.style.opacity = '0';
        t.addEventListener('transitionend', () => t.remove());
      }, ms);
    } catch (e) {}
  }

  // HTML-escape helper for JS-inserted strings
  function escapeHtml(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, function(m) {
      return ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      })[m];
    });
  }

  // close drawers on ESC
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const d = document.getElementById('mobileDrawer');
      if (d && d.getAttribute('aria-hidden') === 'false') toggleMobileDrawer();
      const mc = document.getElementById('miniCart');
      if (mc && mc.getAttribute('aria-hidden') === 'false') toggleMiniCart();
    }
  });

  // initialize UI after DOM ready
  document.addEventListener('DOMContentLoaded', function() {
    refreshCartUI();
  });
</script>
