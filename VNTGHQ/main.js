/* ============================================
   VNTG HQ — main.js
   ============================================ */

// ── CART & FAVOURITES (localStorage) ────────────────────────────────────────
const CART_KEY  = 'vntghq_cart';
const FAV_KEY   = 'vntghq_favs';

function getCart() { try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; } catch(e){ return []; } }
function getFavs() { try { return JSON.parse(localStorage.getItem(FAV_KEY))  || []; } catch(e){ return []; } }
function saveCart(c){
  localStorage.setItem(CART_KEY, JSON.stringify(c));
  // Sync to MySQL (non-blocking)
  fetch('api_cart.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({action:'sync', items: c})
  }).catch(()=>{});
}
function saveFavs(f){ localStorage.setItem(FAV_KEY,  JSON.stringify(f)); }

/* Build a product object from a product-card element */
function productFromCard(card) {
  return {
    id:    card.dataset.pid   || card.querySelector('.product-name')?.textContent.trim().replace(/\s+/g,'_'),
    name:  card.querySelector('.product-name')?.textContent.trim()  || 'Item',
    price: card.querySelector('.product-price')?.textContent.trim() || '',
    img:   card.querySelector('.product-img')?.src || ''
  };
}

/* ── Add to Cart ── */
function addToCartAnim(btn) {
  const card = btn.closest('.product-card');
  const product = productFromCard(card);

  let cart = getCart();
  const existing = cart.find(i => i.id === product.id);
  if (existing) { existing.qty = (existing.qty || 1) + 1; }
  else           { cart.push({ ...product, qty: 1 }); }
  saveCart(cart);

  // Button feedback
  btn.classList.add('cart-added');
  btn.innerHTML = '<i class="bi bi-check-lg"></i>';
  setTimeout(() => {
    btn.classList.remove('cart-added');
    btn.innerHTML = '<i class="bi bi-cart-plus"></i>';
  }, 1500);

  // Badge
  updateCartBadge();

  // Toast notification
  showToast(`<i class="bi bi-cart-check me-2"></i><strong>${product.name}</strong> added to cart!`, 'cart');
}

/* ── Toggle Favourite ── */
function toggleFavourite(btn) {
  const card = btn.closest('.product-card');
  const product = productFromCard(card);
  let favs = getFavs();
  const idx = favs.findIndex(i => i.id === product.id);

  if (idx === -1) {
    favs.push(product);
    saveFavs(favs);
    btn.innerHTML = '<i class="bi bi-heart-fill"></i>';
    btn.classList.add('fav-active');
    showToast(`<i class="bi bi-heart-fill me-2"></i><strong>${product.name}</strong> added to favourites!`, 'fav');
  } else {
    favs.splice(idx, 1);
    saveFavs(favs);
    btn.innerHTML = '<i class="bi bi-heart"></i>';
    btn.classList.remove('fav-active');
    showToast(`<i class="bi bi-heart me-2"></i><strong>${product.name}</strong> removed from favourites.`, 'info');
  }
  updateFavBadge();
}

/* ── Sync badge numbers on page load ── */
function updateCartBadge() {
  const cart = getCart();
  const total = cart.reduce((s, i) => s + (i.qty || 1), 0);
  document.querySelectorAll('.cart-badge').forEach(b => {
    b.textContent = total;
    b.style.display = total > 0 ? 'flex' : 'none';
  });
}

function updateFavBadge() {
  const favs = getFavs();
  document.querySelectorAll('.fav-badge').forEach(b => {
    b.textContent = favs.length;
    b.style.display = favs.length > 0 ? 'flex' : 'none';
  });
}

/* Restore wishlist button state on page load */
function syncWishlistButtons() {
  const favs = getFavs();
  document.querySelectorAll('.product-card').forEach(card => {
    const btn = card.querySelector('.btn-wishlist');
    if (!btn) return;
    const id = card.dataset.pid || card.querySelector('.product-name')?.textContent.trim().replace(/\s+/g,'_');
    if (favs.find(f => f.id === id)) {
      btn.innerHTML = '<i class="bi bi-heart-fill"></i>';
      btn.classList.add('fav-active');
    }
  });
}

// ── TOAST NOTIFICATIONS ──────────────────────────────────────────────────────
let toastContainer;

function getToastContainer() {
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'toast-container';
    document.body.appendChild(toastContainer);
  }
  return toastContainer;
}

function showToast(html, type = 'info') {
  const container = getToastContainer();
  const toast = document.createElement('div');
  toast.className = `vntg-toast vntg-toast-${type}`;
  toast.innerHTML = html;
  container.appendChild(toast);

  // Animate in
  requestAnimationFrame(() => {
    requestAnimationFrame(() => toast.classList.add('show'));
  });

  // Auto dismiss
  setTimeout(() => {
    toast.classList.remove('show');
    toast.addEventListener('transitionend', () => toast.remove(), { once: true });
  }, 3000);
}

// ── NOTIFICATIONS PANEL ──────────────────────────────────────────────────────
const NOTIF_KEY = 'vntghq_notifs';

const DEFAULT_NOTIFICATIONS = [
  { id: 1, icon: 'bi-bag-check-fill',   color: '#22c55e', title: 'Order Delivered',       body: 'Your Air Jordan 1 Low has been delivered!',         time: '2 mins ago',  read: false },
  { id: 2, icon: 'bi-truck',            color: '#0ea5e9', title: 'Order Shipped',          body: 'Nike Windrunner is on its way to you.',              time: '1 hour ago',  read: false },
  { id: 3, icon: 'bi-heart-fill',       color: '#ef4444', title: 'New on Wishlist',        body: 'A saved item is back in stock — grab it now!',       time: '3 hours ago', read: false },
  { id: 4, icon: 'bi-tag-fill',         color: '#f59e0b', title: 'Price Drop Alert',       body: 'Vintage Leather Jacket dropped to PHP 2,200!',       time: '5 hours ago', read: true  },
  { id: 5, icon: 'bi-person-check-fill',color: '#8b5cf6', title: 'Welcome to VNTG HQ',    body: 'Your account is set up and ready to shop.',          time: 'Yesterday',   read: true  },
];

function getNotifications() {
  const stored = localStorage.getItem(NOTIF_KEY);
  if (!stored) {
    localStorage.setItem(NOTIF_KEY, JSON.stringify(DEFAULT_NOTIFICATIONS));
    return DEFAULT_NOTIFICATIONS;
  }
  return JSON.parse(stored);
}

function saveNotifications(n) { localStorage.setItem(NOTIF_KEY, JSON.stringify(n)); }

function updateNotifBadge() {
  const unread = getNotifications().filter(n => !n.read).length;
  document.querySelectorAll('.notif-badge').forEach(b => {
    b.textContent = unread;
    b.style.display = unread > 0 ? 'flex' : 'none';
  });
}

function buildNotifPanel() {
  // Remove existing panel if any
  const existing = document.getElementById('notif-panel');
  if (existing) { existing.remove(); return; }

  const notifs = getNotifications();
  const unread = notifs.filter(n => !n.read).length;

  const panel = document.createElement('div');
  panel.id = 'notif-panel';
  panel.innerHTML = `
    <div class="notif-panel-header">
      <span class="notif-panel-title"><i class="bi bi-bell-fill me-2"></i>Notifications</span>
      ${unread > 0 ? `<button class="notif-mark-all" onclick="markAllRead()">Mark all read</button>` : ''}
    </div>
    <div class="notif-panel-body">
      ${notifs.length === 0
        ? `<div class="notif-empty"><i class="bi bi-bell-slash"></i><p>No notifications</p></div>`
        : notifs.map(n => `
          <div class="notif-item ${n.read ? '' : 'unread'}" data-id="${n.id}" onclick="markOneRead(${n.id}, this)">
            <div class="notif-icon-wrap" style="background:${n.color}20;color:${n.color}">
              <i class="bi ${n.icon}"></i>
            </div>
            <div class="notif-content">
              <p class="notif-title">${n.title}</p>
              <p class="notif-body">${n.body}</p>
              <span class="notif-time">${n.time}</span>
            </div>
            ${!n.read ? '<span class="notif-dot"></span>' : ''}
          </div>`).join('')
      }
    </div>
  `;

  document.body.appendChild(panel);

  // Position near the bell button
  const bellBtn = document.querySelector('.notif-trigger');
  if (bellBtn) {
    const rect = bellBtn.getBoundingClientRect();
    panel.style.top  = (rect.bottom + window.scrollY + 8) + 'px';
    panel.style.right = (window.innerWidth - rect.right) + 'px';
  }

  requestAnimationFrame(() => requestAnimationFrame(() => panel.classList.add('open')));

  // Close on outside click
  setTimeout(() => {
    document.addEventListener('click', function outsideClick(e) {
      if (!panel.contains(e.target) && !e.target.closest('.notif-trigger')) {
        panel.classList.remove('open');
        panel.addEventListener('transitionend', () => panel.remove(), { once: true });
        document.removeEventListener('click', outsideClick);
      }
    });
  }, 100);
}

function markAllRead() {
  const notifs = getNotifications().map(n => ({ ...n, read: true }));
  saveNotifications(notifs);
  updateNotifBadge();
  buildNotifPanel(); // rebuild
}

function markOneRead(id, el) {
  const notifs = getNotifications().map(n => n.id === id ? { ...n, read: true } : n);
  saveNotifications(notifs);
  updateNotifBadge();
  el.classList.remove('unread');
  el.querySelector('.notif-dot')?.remove();
}

// ── PASSWORD TOGGLE ──────────────────────────────────────────────────────────
function togglePassword(btn) {
  const wrap  = btn.closest('.input-with-icon');
  const input = wrap.querySelector('input[type="password"], input[type="text"]');
  const icon  = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'bi bi-eye';
  }
}

// ── QTY CONTROLS (cart page) ─────────────────────────────────────────────────
function changeQty(btn, delta) {
  const wrap  = btn.closest('.cart-qty-controls');
  const valEl = wrap.querySelector('.qty-val');
  let val = parseInt(valEl.textContent);
  val = Math.max(1, val + delta);
  valEl.textContent = val;
}

// ── NAVBAR SCROLL EFFECT ─────────────────────────────────────────────────────
window.addEventListener('scroll', () => {
  const nav = document.querySelector('.vntg-navbar');
  if (nav) nav.classList.toggle('scrolled', window.scrollY > 20);
});

// ── SCROLL ANIMATION FOR PRODUCT CARDS ──────────────────────────────────────
const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry, i) => {
    if (entry.isIntersecting) {
      entry.target.style.transitionDelay = `${i * 60}ms`;
      entry.target.classList.add('card-visible');
    }
  });
}, { threshold: 0.1 });

// ── INIT ON DOM READY ────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
  // Load cart from MySQL and merge with localStorage
  try {
    const resp = await fetch('api_cart.php?action=load');
    const data = await resp.json();
    if (data.ok && data.items && data.items.length > 0) {
      const local = getCart();
      // Merge: MySQL is source of truth; local items not in DB get added
      const dbIds = new Set(data.items.map(i => i.id));
      const merged = [...data.items];
      local.forEach(li => { if (!dbIds.has(li.id)) merged.push(li); });
      localStorage.setItem(CART_KEY, JSON.stringify(merged));
    }
  } catch(e) { /* offline or not logged in — use localStorage */ }

  updateCartBadge();
  updateFavBadge();
  updateNotifBadge();
  syncWishlistButtons();
  document.querySelectorAll('.product-card').forEach(card => observer.observe(card));
});

// ── SEARCH ───────────────────────────────────────────────────────────────────
const ALL_PRODUCTS = [
  { name:'Air Jordan 1 Low',      price:'PHP 3,800', tag:'Shoes',       img:'https://images.unsplash.com/photo-1556048219-bb6978360b84?w=200&q=70' },
  { name:'Nike Windrunner',       price:'PHP 2,500', tag:'Jacket',      img:'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=200&q=70' },
  { name:'Black Puma Hoodie',     price:'PHP 1,200', tag:'Hoodie',      img:'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=200&q=70' },
  { name:"Vintage Orange Hoodie", price:'PHP 950',   tag:'Hoodie',      img:'https://images.unsplash.com/photo-1556821840-3a63f15732ce?w=200&q=70' },
  { name:'Classic White Sneakers',price:'PHP 2,100', tag:'Shoes',       img:'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=200&q=70' },
  { name:"Men's Black Watch",     price:'PHP 1,800', tag:'Accessories', img:'https://images.unsplash.com/photo-1523170335258-f5ed11844a49?w=200&q=70' },
  { name:'Streetwear Jacket',     price:'PHP 1,450', tag:'Jacket',      img:'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=200&q=70' },
  { name:'Oversized Tee',         price:'PHP 650',   tag:'T-Shirt',     img:'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=200&q=70' },
  { name:'Vintage Hoodie',        price:'PHP 880',   tag:'Hoodie',      img:'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?w=200&q=70' },
  { name:'Washed Denim Jacket',   price:'PHP 1,700', tag:'Jacket',      img:'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=200&q=70' },
  { name:'Painted Sneakers',      price:'PHP 2,200', tag:'Shoes',       img:'https://images.unsplash.com/photo-1465453869711-7e174808ace9?w=200&q=70' },
  { name:'Leather Crossbody',     price:'PHP 1,100', tag:'Bag',         img:'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=200&q=70' },
  { name:'Vintage Leather Jacket',price:'PHP 2,800', tag:'Jacket',      img:'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=200&q=70' },
  { name:'Canvas Backpack',       price:'PHP 990',   tag:'Bag',         img:'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=200&q=70' },
  { name:'Gold Chain Necklace',   price:'PHP 750',   tag:'Accessories', img:'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=200&q=70' },
  { name:'Retro Sneakers',        price:'PHP 1,950', tag:'Shoes',       img:'https://images.unsplash.com/photo-1465453869711-7e174808ace9?w=200&q=70' },
  { name:'Classic White Tee',     price:'PHP 450',   tag:'T-Shirt',     img:'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=200&q=70' },
  { name:'Bucket Hat',            price:'PHP 380',   tag:'Accessories', img:'https://images.unsplash.com/photo-1575428652377-a2d80e2277fc?w=200&q=70' },
  { name:'Corduroy Pants',        price:'PHP 1,350', tag:'Bottoms',     img:'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=200&q=70' },
  { name:'Denim Shorts',          price:'PHP 600',   tag:'Bottoms',     img:'https://images.unsplash.com/photo-1591195853828-11db59a44f43?w=200&q=70' },
  { name:'Flannel Shirt',         price:'PHP 720',   tag:'Tops',        img:'https://images.unsplash.com/photo-1588359348347-9bc6cbbb689e?w=200&q=70' },
  { name:'Vintage Band Tee',      price:'PHP 850',   tag:'T-Shirt',     img:'https://images.unsplash.com/photo-1503341504253-dff4815485f1?w=200&q=70' },
  { name:'Cargo Pants',           price:'PHP 1,500', tag:'Bottoms',     img:'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=200&q=70' },
  { name:'Slip-on Loafers',       price:'PHP 1,250', tag:'Shoes',       img:'https://images.unsplash.com/photo-1560343090-f0409e92791a?w=200&q=70' },
  { name:'Silk Scarf',            price:'PHP 420',   tag:'Accessories', img:'https://images.unsplash.com/photo-1601924638867-3a6de6b7a500?w=200&q=70' },
];

let searchTimer;
function handleNavSearch(query) {
  clearTimeout(searchTimer);
  const dd = document.getElementById('searchDropdown');
  if (!dd) return;
  if (!query.trim()) { dd.style.display='none'; return; }
  searchTimer = setTimeout(() => {
    const q = query.toLowerCase();
    const results = ALL_PRODUCTS.filter(p =>
      p.name.toLowerCase().includes(q) || p.tag.toLowerCase().includes(q)
    ).slice(0, 6);
    if (!results.length) { dd.style.display='none'; return; }
    dd.innerHTML = results.map(p => `
      <div class="search-result-item" onclick="addProductFromSearch(${JSON.stringify(p).replace(/"/g,'&quot;')})">
        <img src="${p.img}" alt="${p.name}" class="search-result-img">
        <div class="flex-grow-1 min-w-0">
          <p class="search-result-name">${p.name}</p>
          <span class="search-result-tag">${p.tag}</span>
        </div>
        <span class="search-result-price">${p.price}</span>
      </div>
    `).join('');
    dd.style.display = 'block';
  }, 200);
}

function doNavSearch() {
  const q = document.getElementById('navSearchInput')?.value?.trim();
  if (!q) return;
  const dd = document.getElementById('searchDropdown');
  if (dd) dd.style.display = 'none';
  // Navigate to homepage with search query
  window.location.href = 'homepage.php?search=' + encodeURIComponent(q);
}

function addProductFromSearch(product) {
  const id = product.name.replace(/\s+/g,'_').toLowerCase();
  let cart = getCart();
  const ex = cart.find(i=>i.id===id);
  if(ex){ ex.qty=(ex.qty||1)+1; } else { cart.push({...product,id,qty:1}); }
  saveCart(cart);
  updateCartBadge();
  showToast(`<i class="bi bi-cart-check me-2"></i><strong>${product.name}</strong> added to cart!`, 'cart');
  document.getElementById('navSearchInput').value='';
  document.getElementById('searchDropdown').style.display='none';
}

// Close search dropdown on outside click
document.addEventListener('click', e => {
  if (!e.target.closest('.nav-search-wrap')) {
    const dd = document.getElementById('searchDropdown');
    if (dd) dd.style.display = 'none';
  }
});
