function basePath() {
  const base = document.querySelector("base");
  return base ? base.getAttribute("href") : "";
}

function showToast(msg, type = "toast") {
  const el = document.getElementById("toast");
  if (!el) return;
  el.textContent = msg;
  el.className = type;
  el.style.opacity = 1;
  clearTimeout(window.__toastTimer);
  window.__toastTimer = setTimeout(() => (el.style.opacity = 0), 2200);
}

async function addToCart(productId) {
  const base = document.querySelector("base")?.getAttribute("href") || "";
  try {
    const res = await fetch(base + "pages/cart_add.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin", // ensures PHPSESSID cookie is sent
      body: JSON.stringify({ product_id: productId, qty: 1 }),
    });
    const data = await res.json();
    const el = document.getElementById("toast");
    if (el) {
      el.textContent = data.message || "Added to cart.";
      el.className = "toast";
    }
  } catch (e) {
    const el = document.getElementById("toast");
    if (el) {
      el.textContent = "Could not add to cart.";
      el.className = "toast warning";
    }
  }
}
