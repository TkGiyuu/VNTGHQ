<?php require 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Cart – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <h1 class="cart-page-title"><i class="bi bi-cart3 me-2"></i>My Cart</h1>
    <button class="btn btn-outline-danger btn-sm fw-semibold" onclick="clearCart()">
      <i class="bi bi-trash me-1"></i>Clear Cart
    </button>
  </div>

  <!-- ── Empty state (hidden until JS confirms cart is empty) ── -->
  <div id="cart-empty" class="text-center py-5" style="display:none">
    <i class="bi bi-cart-x" style="font-size:72px;color:var(--gray-200)"></i>
    <h4 class="mt-3" style="color:var(--gray-400)">Your cart is empty</h4>
    <p style="color:var(--gray-400)">Browse items and tap the cart icon to add them here.</p>
    <a href="homepage.php" class="btn dash-btn-primary mt-2 px-5">Browse Items</a>
  </div>

  <!-- ── Main cart layout (hidden until JS confirms cart has items) ── -->
  <div id="cart-layout" class="row g-4" style="display:none">

    <!-- LEFT: Cart Items -->
    <div class="col-lg-8">

      <!-- Select-all header -->
      <div class="cart-collection-header d-flex align-items-center justify-content-between mb-3 p-3">
        <div class="d-flex align-items-center gap-2">
          <input type="checkbox" class="form-check-input cart-check" id="selectAll" checked
                 onchange="toggleSelectAll(this)">
          <label class="fw-semibold text-white mb-0" for="selectAll">Select All Items</label>
        </div>
        <span class="text-white opacity-75 small" id="cart-item-count"></span>
      </div>

      <!-- Items injected here by JS -->
      <div id="cart-items-list"></div>

    </div>

    <!-- RIGHT: Order Summary -->
    <div class="col-lg-4">
      <div class="cart-summary-box">
        <h4 class="cart-summary-title mb-4">Order Summary</h4>

        <div class="d-flex justify-content-between mb-2">
          <span class="summary-label">Subtotal (<span id="sum-count">0</span> items)</span>
          <span class="summary-val" id="sum-subtotal">PHP 0</span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="summary-label">Shipping</span>
          <span class="summary-val text-success">Free</span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="summary-label">Discount</span>
          <span class="summary-val text-danger" id="sum-discount">– PHP 0</span>
        </div>
        <hr class="my-3">
        <div class="d-flex justify-content-between mb-4">
          <span class="fw-bold summary-total-label">Total</span>
          <span class="fw-bold summary-total-price" id="sum-total">PHP 0</span>
        </div>

        <button class="btn checkout-btn w-100" onclick="window.location.href='checkout.php'">
          <i class="bi bi-bag-check me-2"></i>Check Out (<span id="sum-checkout-count">0</span>)
        </button>
        <a href="homepage.php" class="btn btn-outline-secondary w-100 mt-2">
          <i class="bi bi-arrow-left me-2"></i>Continue Shopping
        </a>
      </div>
    </div>

  </div><!-- /row -->
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
<script>
/* ================================================================
   CART PAGE — renders from localStorage, all changes persist live
   ================================================================ */

document.addEventListener('DOMContentLoaded', renderCartPage);

function parsePHP(priceStr) {
  // "PHP 1,200" → 1200
  return parseInt(priceStr.replace(/[^\d]/g, ''), 10) || 0;
}

function formatPHP(amount) {
  return 'PHP ' + amount.toLocaleString('en-PH');
}

function renderCartPage() {
  const cart   = getCart();
  const layout = document.getElementById('cart-layout');
  const empty  = document.getElementById('cart-empty');
  const list   = document.getElementById('cart-items-list');

  if (cart.length === 0) {
    layout.style.display = 'none';
    empty.style.display  = 'block';
    return;
  }

  layout.style.display = 'flex';
  empty.style.display  = 'none';

  // Render each cart item
  list.innerHTML = cart.map((item, idx) => `
    <div class="cart-item-card mb-3" id="cart-row-${idx}" data-idx="${idx}">
      <div class="d-flex align-items-center gap-3">
        <input type="checkbox" class="form-check-input cart-check cart-item-check mt-0"
               checked onchange="recalcSummary()">
        <img src="${item.img || 'https://via.placeholder.com/100'}"
             alt="${item.name}" class="cart-item-img"
             onerror="this.src='https://via.placeholder.com/100'">
        <div class="flex-grow-1 min-w-0">
          <h5 class="cart-item-name">${item.name}</h5>
          <span class="cart-item-price">${item.price}</span>
        </div>
        <div class="d-flex align-items-center gap-3">
          <div class="cart-qty-controls d-flex align-items-center gap-2">
            <button class="qty-btn" onclick="cartChangeQty(${idx}, -1)"><i class="bi bi-dash"></i></button>
            <span class="qty-val" id="qty-${idx}">${item.qty || 1}</span>
            <button class="qty-btn" onclick="cartChangeQty(${idx}, 1)"><i class="bi bi-plus"></i></button>
          </div>
          <button class="btn-remove-cart" onclick="removeFromCart(${idx})" title="Remove">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
      </div>
    </div>
  `).join('');

  // Update count label
  const total = cart.reduce((s, i) => s + (i.qty || 1), 0);
  document.getElementById('cart-item-count').textContent =
    `${cart.length} item${cart.length !== 1 ? 's' : ''}`;

  recalcSummary();
}

function cartChangeQty(idx, delta) {
  const cart = getCart();
  if (!cart[idx]) return;
  cart[idx].qty = Math.max(1, (cart[idx].qty || 1) + delta);
  saveCart(cart);
  // Update displayed qty without full re-render
  const qtyEl = document.getElementById(`qty-${idx}`);
  if (qtyEl) qtyEl.textContent = cart[idx].qty;
  updateCartBadge();
  recalcSummary();
}

function removeFromCart(idx) {
  const cart = getCart();
  const name = cart[idx]?.name || 'Item';
  cart.splice(idx, 1);
  saveCart(cart);
  updateCartBadge();
  showToast(`<i class="bi bi-trash me-2"></i><strong>${name}</strong> removed from cart.`, 'info');
  renderCartPage(); // full re-render (indexes shifted)
}

function clearCart() {
  if (getCart().length === 0) return;
  if (!confirm('Remove all items from your cart?')) return;
  saveCart([]);
  updateCartBadge();
  renderCartPage();
}

function toggleSelectAll(cb) {
  document.querySelectorAll('.cart-item-check').forEach(c => c.checked = cb.checked);
  recalcSummary();
}

function recalcSummary() {
  const cart  = getCart();
  const rows  = document.querySelectorAll('.cart-item-card');
  let subtotal = 0;
  let count    = 0;

  rows.forEach((row, idx) => {
    const cb = row.querySelector('.cart-item-check');
    if (cb && cb.checked && cart[idx]) {
      const qty   = cart[idx].qty || 1;
      const price = parsePHP(cart[idx].price);
      subtotal += price * qty;
      count    += qty;
    }
  });

  // 5 % discount if subtotal > 2000
  const discount = subtotal > 2000 ? Math.round(subtotal * 0.05) : 0;
  const total    = subtotal - discount;

  document.getElementById('sum-count').textContent         = count;
  document.getElementById('sum-subtotal').textContent      = formatPHP(subtotal);
  document.getElementById('sum-discount').textContent      = discount > 0 ? `– ${formatPHP(discount)}` : '– PHP 0';
  document.getElementById('sum-total').textContent         = formatPHP(total);
  document.getElementById('sum-checkout-count').textContent = count;

  // Keep select-all in sync
  const allChecks = document.querySelectorAll('.cart-item-check');
  const allChecked = Array.from(allChecks).every(c => c.checked);
  const selectAll = document.getElementById('selectAll');
  if (selectAll) selectAll.checked = allChecked;
}

function handleCheckout() {
  const count = parseInt(document.getElementById('sum-checkout-count').textContent);
  if (count === 0) {
    showToast('<i class="bi bi-info-circle me-2"></i>Please select at least one item to check out.', 'info');
    return;
  }
  showToast(`<i class="bi bi-bag-check me-2"></i>Proceeding to checkout with ${count} item(s)…`, 'cart');
}
</script>
</body>
</html>
