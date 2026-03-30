// ============================================
//  LUXE Shop — Frontend JS (connects to PHP API)
// ============================================

const API = {
  products: '/api/products.php',
  orders:   '/api/orders.php',
};

let allProducts  = [];
let cart         = {};          // { productId: quantity }
let currentCategory = 'all';

// ── INIT ──
document.addEventListener('DOMContentLoaded', async () => {
  await loadProducts();
  renderCart();
});

// ── PRODUCTS ──
async function loadProducts(category = 'all') {
  const grid = document.getElementById('product-grid');
  grid.innerHTML = '<div class="loading">Loading products…</div>';
  try {
    const url = category !== 'all' ? `${API.products}?category=${category}` : API.products;
    const res  = await fetch(url);
    if (!res.ok) throw new Error('Failed to load products');
    allProducts = await res.json();
    renderProducts(allProducts);
  } catch (e) {
    grid.innerHTML = `<div class="loading" style="color:var(--red)">Failed to load products. Is the server running?</div>`;
  }
}

function renderProducts(products) {
  const grid = document.getElementById('product-grid');
  if (!products.length) {
    grid.innerHTML = '<div class="loading" style="color:var(--text-dim);font-style:italic">No products found.</div>';
    return;
  }
  grid.innerHTML = products.map((p, i) => `
    <div class="product-card" style="animation-delay:${i * 0.05}s">
      <div class="product-img-wrap">
        <div class="product-img-placeholder">${p.emoji}</div>
        ${p.badge ? `<span class="product-badge">${p.badge}</span>` : ''}
        ${p.stock == 0 ? '<span class="product-badge" style="background:var(--red)">Sold Out</span>' : ''}
      </div>
      <div class="product-info">
        <div class="product-category">${p.category}</div>
        <div class="product-name">${escHtml(p.name)}</div>
        <div class="product-price">$${parseFloat(p.price).toFixed(2)}</div>
        ${p.description ? `<div class="product-desc">${escHtml(p.description)}</div>` : ''}
        <button class="product-add" ${p.stock == 0 ? 'disabled style="opacity:.4;cursor:not-allowed"' : ''} onclick="addToCart(${p.id})">
          ${p.stock == 0 ? 'Out of Stock' : 'Add to Cart'}
        </button>
      </div>
    </div>
  `).join('');
}

function setCategory(cat, btn) {
  currentCategory = cat;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  loadProducts(cat);
}

function scrollToShop() {
  document.querySelector('.shop-header').scrollIntoView({ behavior: 'smooth' });
}

// ── CART ──
function addToCart(id) {
  const product = allProducts.find(p => p.id == id);
  if (!product || product.stock == 0) return;
  cart[id] = (cart[id] || 0) + 1;
  renderCart();
  showToast(`${product.emoji} ${product.name} added`);
}

function renderCart() {
  const count = Object.values(cart).reduce((a, b) => a + b, 0);
  document.getElementById('cart-count').textContent = count;

  const cartProds = Object.entries(cart)
    .map(([id, qty]) => {
      const p = allProducts.find(x => x.id == id);
      return p ? { ...p, qty } : null;
    })
    .filter(Boolean);

  const items = document.getElementById('cart-items');
  if (!cartProds.length) {
    items.innerHTML = '<p class="cart-empty">Your cart is empty</p>';
  } else {
    items.innerHTML = cartProds.map(p => `
      <div class="cart-item">
        <div class="cart-item-img">${p.emoji}</div>
        <div class="cart-item-info">
          <div class="cart-item-name">${escHtml(p.name)}</div>
          <div class="cart-item-price">$${parseFloat(p.price).toFixed(2)}</div>
          <div class="cart-item-qty">
            <button class="qty-btn" onclick="changeQty(${p.id}, -1)">−</button>
            <span class="qty-val">${p.qty}</span>
            <button class="qty-btn" onclick="changeQty(${p.id}, 1)">+</button>
            <button class="remove-item" onclick="removeItem(${p.id})">Remove</button>
          </div>
        </div>
      </div>
    `).join('');
  }

  const total = cartProds.reduce((s, p) => s + parseFloat(p.price) * p.qty, 0);
  document.getElementById('cart-total').textContent = `$${total.toFixed(2)}`;

  // Show checkout form only when cart has items
  document.getElementById('checkout-form').style.display = cartProds.length ? 'block' : 'none';
}

function changeQty(id, delta) {
  cart[id] = (cart[id] || 0) + delta;
  if (cart[id] <= 0) delete cart[id];
  renderCart();
}

function removeItem(id) {
  delete cart[id];
  renderCart();
}

function openCart()  { document.getElementById('cart-overlay').classList.add('open'); document.getElementById('cart-panel').classList.add('open'); }
function closeCart() { document.getElementById('cart-overlay').classList.remove('open'); document.getElementById('cart-panel').classList.remove('open'); }

// ── CHECKOUT ──
async function handleCheckout() {
  if (!Object.keys(cart).length) { showToast('Your cart is empty'); return; }

  const name  = document.getElementById('c-name').value.trim()  || 'Guest Customer';
  const email = document.getElementById('c-email').value.trim() || '';
  const items = Object.entries(cart).map(([id, qty]) => ({ product_id: parseInt(id), quantity: qty }));

  const btn = document.getElementById('checkout-btn');
  btn.textContent = 'Processing…';
  btn.disabled = true;

  try {
    const res  = await fetch(API.orders, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ customer_name: name, customer_email: email, items }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Checkout failed');

    cart = {};
    renderCart();
    closeCart();
    showToast(`✓ Order #${data.id} placed successfully!`);
    // Reload products to reflect updated stock
    await loadProducts(currentCategory);
  } catch (e) {
    showToast('Error: ' + e.message);
  } finally {
    btn.textContent = 'Checkout';
    btn.disabled = false;
  }
}

// ── HELPERS ──
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2400);
}

function escHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
