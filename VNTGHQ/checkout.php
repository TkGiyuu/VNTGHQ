<?php require 'auth_check.php';
$firstname = $_SESSION['firstname'] ?? 'User';
$lastname  = $_SESSION['lastname']  ?? '';
$email     = $_SESSION['email']     ?? '';
$phone     = $_SESSION['phone']     ?? '';
$fullname  = trim("$firstname $lastname");
$uid       = $_SESSION['firebase_uid'] ?? $_SESSION['user'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout – VNTG HQ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-4">
  <!-- Back button -->
  <a href="cart.php" class="back-btn mb-4 d-inline-flex align-items-center gap-2">
    <i class="bi bi-arrow-left"></i> Back to Cart
  </a>

  <!-- Step progress -->
  <div class="checkout-steps mb-4">
    <div class="co-step active" id="stepTab1"><span class="co-step-num">1</span><span class="co-step-label">Shipping</span></div>
    <div class="co-step-line"></div>
    <div class="co-step" id="stepTab2"><span class="co-step-num">2</span><span class="co-step-label">Payment</span></div>
    <div class="co-step-line"></div>
    <div class="co-step" id="stepTab3"><span class="co-step-num">3</span><span class="co-step-label">Review</span></div>
  </div>

  <div class="row g-4">
    <!-- LEFT: Steps -->
    <div class="col-lg-7">

      <!-- ── STEP 1: Shipping ── -->
      <div class="co-panel" id="panel1">
        <h4 class="co-panel-title"><i class="bi bi-geo-alt me-2"></i>Shipping Information</h4>
        <div class="row g-3">
          <div class="col-6">
            <label class="pmodal-label">First Name</label>
            <div class="input-with-icon">
              <i class="bi bi-person input-icon"></i>
              <input type="text" id="sh_first" class="form-control auth-input" value="<?= htmlspecialchars($firstname) ?>">
            </div>
          </div>
          <div class="col-6">
            <label class="pmodal-label">Last Name</label>
            <div class="input-with-icon">
              <i class="bi bi-person input-icon"></i>
              <input type="text" id="sh_last" class="form-control auth-input" value="<?= htmlspecialchars($lastname) ?>">
            </div>
          </div>
          <div class="col-12">
            <label class="pmodal-label">Email</label>
            <div class="input-with-icon">
              <i class="bi bi-envelope input-icon"></i>
              <input type="email" id="sh_email" class="form-control auth-input" value="<?= htmlspecialchars($email) ?>" placeholder="your@email.com">
            </div>
          </div>
          <div class="col-12">
            <label class="pmodal-label">Phone Number</label>
            <div class="input-with-icon">
              <i class="bi bi-telephone input-icon"></i>
              <input type="tel" id="sh_phone" class="form-control auth-input" value="<?= htmlspecialchars($phone) ?>" placeholder="+63 9XX XXX XXXX">
            </div>
          </div>
          <div class="col-12">
            <label class="pmodal-label">Street Address</label>
            <div class="input-with-icon">
              <i class="bi bi-house input-icon"></i>
              <input type="text" id="sh_street" class="form-control auth-input" placeholder="House No., Street, Barangay">
            </div>
          </div>
          <div class="col-6">
            <label class="pmodal-label">City / Municipality</label>
            <input type="text" id="sh_city" class="form-control auth-input ps-3" placeholder="e.g. Cebu City">
          </div>
          <div class="col-6">
            <label class="pmodal-label">Province</label>
            <input type="text" id="sh_province" class="form-control auth-input ps-3" placeholder="e.g. Cebu">
          </div>
          <div class="col-6">
            <label class="pmodal-label">ZIP Code</label>
            <input type="text" id="sh_zip" class="form-control auth-input ps-3" placeholder="6000" maxlength="4">
          </div>
          <div class="col-6">
            <label class="pmodal-label">Delivery Option</label>
            <select id="sh_delivery" class="form-select pmodal-select">
              <option value="standard">Standard (3–5 days) — Free</option>
              <option value="express">Express (1–2 days) — PHP 150</option>
              <option value="same_day">Same Day — PHP 250</option>
            </select>
          </div>
        </div>
        <div class="d-flex justify-content-end mt-4">
          <button class="btn pmodal-btn-save px-5" onclick="goStep(2)">
            Continue to Payment <i class="bi bi-arrow-right ms-2"></i>
          </button>
        </div>
      </div>

      <!-- ── STEP 2: Payment ── -->
      <div class="co-panel" id="panel2" style="display:none">
        <h4 class="co-panel-title"><i class="bi bi-credit-card me-2"></i>Payment Method</h4>
        <div class="co-pay-methods mb-4">
          <label class="co-pay-option active" onclick="selectPayOption(this,'card')">
            <input type="radio" name="pay_method" value="card" checked>
            <i class="bi bi-credit-card-2-front-fill"></i> Credit / Debit Card
          </label>
          <label class="co-pay-option" onclick="selectPayOption(this,'gcash')">
            <input type="radio" name="pay_method" value="gcash">
            <i class="bi bi-phone-fill"></i> GCash
          </label>
          <label class="co-pay-option" onclick="selectPayOption(this,'maya')">
            <input type="radio" name="pay_method" value="maya">
            <i class="bi bi-phone-fill"></i> Maya
          </label>
          <label class="co-pay-option" onclick="selectPayOption(this,'cod')">
            <input type="radio" name="pay_method" value="cod">
            <i class="bi bi-box-seam-fill"></i> Cash on Delivery
          </label>
        </div>

        <!-- Card details -->
        <div id="cardFields" class="row g-3">
          <div class="col-12">
            <label class="pmodal-label">Card Number</label>
            <div class="input-with-icon">
              <i class="bi bi-credit-card input-icon"></i>
              <input type="text" id="pay_card" class="form-control auth-input" placeholder="1234 5678 9012 3456" maxlength="19" oninput="formatCard(this)">
            </div>
          </div>
          <div class="col-6">
            <label class="pmodal-label">Expiry</label>
            <div class="input-with-icon">
              <i class="bi bi-calendar input-icon"></i>
              <input type="text" id="pay_expiry" class="form-control auth-input" placeholder="MM/YY" maxlength="5" oninput="formatExpiry(this)">
            </div>
          </div>
          <div class="col-6">
            <label class="pmodal-label">CVV</label>
            <div class="input-with-icon">
              <i class="bi bi-lock input-icon"></i>
              <input type="password" id="pay_cvv" class="form-control auth-input" placeholder="•••" maxlength="4">
            </div>
          </div>
          <div class="col-12">
            <label class="pmodal-label">Cardholder Name</label>
            <div class="input-with-icon">
              <i class="bi bi-person input-icon"></i>
              <input type="text" id="pay_name" class="form-control auth-input" placeholder="Name on card" value="<?= htmlspecialchars($fullname) ?>">
            </div>
          </div>
        </div>
        <!-- GCash / Maya fields -->
        <div id="ewalletFields" style="display:none" class="row g-3">
          <div class="col-12">
            <label class="pmodal-label">Mobile Number</label>
            <div class="input-with-icon">
              <i class="bi bi-phone input-icon"></i>
              <input type="tel" id="pay_mobile" class="form-control auth-input" placeholder="+63 9XX XXX XXXX" value="<?= htmlspecialchars($phone) ?>">
            </div>
          </div>
          <div class="col-12">
            <div class="co-ewallet-note"><i class="bi bi-info-circle me-2"></i>A payment request will be sent to your e-wallet after order confirmation.</div>
          </div>
        </div>
        <!-- COD -->
        <div id="codFields" style="display:none">
          <div class="co-ewallet-note"><i class="bi bi-box-seam me-2"></i>Pay in cash when your order arrives. Please prepare the exact amount.</div>
        </div>

        <div class="d-flex justify-content-between mt-4">
          <button class="btn pmodal-btn-cancel px-4" onclick="goStep(1)"><i class="bi bi-arrow-left me-2"></i>Back</button>
          <button class="btn pmodal-btn-save px-5" onclick="goStep(3)">Review Order <i class="bi bi-arrow-right ms-2"></i></button>
        </div>
      </div>

      <!-- ── STEP 3: Review ── -->
      <div class="co-panel" id="panel3" style="display:none">
        <h4 class="co-panel-title"><i class="bi bi-clipboard-check me-2"></i>Review Your Order</h4>
        <div id="co-review-content"></div>
        <div class="d-flex justify-content-between mt-4">
          <button class="btn pmodal-btn-cancel px-4" onclick="goStep(2)"><i class="bi bi-arrow-left me-2"></i>Back</button>
          <button class="btn checkout-btn px-5" onclick="placeOrder()">
            <i class="bi bi-bag-check me-2"></i>Place Order
          </button>
        </div>
      </div>

    </div>

    <!-- RIGHT: Order Summary -->
    <div class="col-lg-5">
      <div class="cart-summary-box">
        <h4 class="cart-summary-title mb-3">Your Items</h4>
        <div id="co-items-list" class="mb-3"></div>
        <hr>
        <div class="d-flex justify-content-between mb-1">
          <span class="summary-label">Subtotal</span>
          <span class="summary-val" id="co-subtotal">PHP 0</span>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span class="summary-label">Shipping</span>
          <span class="summary-val" id="co-shipping">Free</span>
        </div>
        <div class="d-flex justify-content-between mb-1">
          <span class="summary-label">Discount</span>
          <span class="summary-val text-danger" id="co-discount">– PHP 0</span>
        </div>
        <hr>
        <div class="d-flex justify-content-between">
          <span class="fw-bold summary-total-label">Total</span>
          <span class="fw-bold summary-total-price" id="co-total">PHP 0</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── ORDER SUCCESS MODAL ── -->
<div class="pmodal-overlay" id="successOverlay"></div>
<div class="pmodal" id="successModal">
  <div class="pmodal-body text-center py-4">
    <div class="fp-success-icon mb-3" style="background:rgba(14,165,233,0.1)">
      <i class="bi bi-bag-check-fill" style="color:var(--brand-blue)"></i>
    </div>
    <h3 class="auth-form-title mb-2">Order Placed!</h3>
    <p class="auth-form-sub mb-1">Thank you for your purchase.</p>
    <p class="text-muted small mb-3">Order ID: <strong id="orderIdDisplay"></strong></p>
    <div class="co-order-detail-box mb-4" id="orderDetailBox"></div>
    <a href="homepage.php" class="btn pmodal-btn-save w-100 mb-2">Continue Shopping</a>
    <a href="dashboard.php" class="btn pmodal-btn-cancel w-100">Go to Dashboard</a>
  </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="main.js"></script>
<script>

let currentStep = 1;
let selectedPayMethod = 'card';
let shippingCost = 0;

document.addEventListener('DOMContentLoaded', () => {
  renderCoItems();
  updateCartBadge(); updateFavBadge(); updateNotifBadge();
});

function parsePHP(s){ return parseInt((s||'').replace(/[^\d]/g,''),10)||0; }
function formatPHP(n){ return 'PHP '+n.toLocaleString('en-PH'); }

function renderCoItems(){
  const cart = getCart();
  const list = document.getElementById('co-items-list');
  if(!cart.length){ list.innerHTML='<p class="text-muted small">No items.</p>'; return; }
  let sub=0;
  list.innerHTML = cart.map(i=>{
    const line = parsePHP(i.price)*(i.qty||1);
    sub+=line;
    return `<div class="co-item-row">
      <img src="${i.img}" alt="${i.name}" class="co-item-img" onerror="this.src='https://via.placeholder.com/50'">
      <div class="flex-grow-1 min-w-0">
        <p class="co-item-name">${i.name}</p>
        <small class="text-muted">Qty: ${i.qty||1}</small>
      </div>
      <span class="co-item-price">${formatPHP(line)}</span>
    </div>`;
  }).join('');
  const disc = sub>2000?Math.round(sub*0.05):0;
  const total= sub-disc+shippingCost;
  document.getElementById('co-subtotal').textContent=formatPHP(sub);
  document.getElementById('co-discount').textContent=disc>0?`– ${formatPHP(disc)}`:'– PHP 0';
  document.getElementById('co-total').textContent=formatPHP(total);
}

function goStep(n){
  if(n===2){
    const f=document.getElementById('sh_first').value.trim();
    const s=document.getElementById('sh_street').value.trim();
    const c=document.getElementById('sh_city').value.trim();
    if(!f||!s||!c){ showToast('<i class="bi bi-exclamation-triangle me-2"></i>Please fill in all shipping fields.','fav'); return; }
    const del=document.getElementById('sh_delivery').value;
    shippingCost=del==='express'?150:del==='same_day'?250:0;
    document.getElementById('co-shipping').textContent=shippingCost>0?formatPHP(shippingCost):'Free';
    renderCoItems();
  }
  if(n===3){ buildReview(); }
  currentStep=n;
  [1,2,3].forEach(i=>{
    document.getElementById('panel'+i).style.display=i===n?'block':'none';
    const tab=document.getElementById('stepTab'+i);
    tab.classList.toggle('active',i<=n);
    tab.classList.toggle('done',i<n);
  });
  window.scrollTo({top:0,behavior:'smooth'});
}

function selectPayOption(label, method){
  document.querySelectorAll('.co-pay-option').forEach(l=>l.classList.remove('active'));
  label.classList.add('active');
  selectedPayMethod=method;
  document.getElementById('cardFields').style.display=method==='card'?'block':'none';
  document.getElementById('ewalletFields').style.display=(method==='gcash'||method==='maya')?'block':'none';
  document.getElementById('codFields').style.display=method==='cod'?'block':'none';
}

function buildReview(){
  const cart=getCart();
  const sub=cart.reduce((s,i)=>s+parsePHP(i.price)*(i.qty||1),0);
  const disc=sub>2000?Math.round(sub*0.05):0;
  const total=sub-disc+shippingCost;
  const methodLabels={card:'Credit/Debit Card',gcash:'GCash',maya:'Maya',cod:'Cash on Delivery'};
  const delivLabel={standard:'Standard (3–5 days)',express:'Express (1–2 days)',same_day:'Same Day'}[document.getElementById('sh_delivery').value]||'Standard';
  document.getElementById('co-review-content').innerHTML=`
    <div class="co-review-section">
      <h6 class="co-review-heading"><i class="bi bi-geo-alt me-2"></i>Shipping To</h6>
      <p class="co-review-val">
        ${document.getElementById('sh_first').value} ${document.getElementById('sh_last').value}<br>
        ${document.getElementById('sh_street').value}, ${document.getElementById('sh_city').value}, ${document.getElementById('sh_province').value} ${document.getElementById('sh_zip').value}<br>
        <span class="text-muted">${document.getElementById('sh_phone').value||''}</span>
      </p>
    </div>
    <div class="co-review-section">
      <h6 class="co-review-heading"><i class="bi bi-credit-card me-2"></i>Payment</h6>
      <p class="co-review-val">${methodLabels[selectedPayMethod]||selectedPayMethod}</p>
    </div>
    <div class="co-review-section">
      <h6 class="co-review-heading"><i class="bi bi-truck me-2"></i>Delivery</h6>
      <p class="co-review-val">${delivLabel} ${shippingCost>0?'— '+formatPHP(shippingCost):'— Free'}</p>
    </div>
    <div class="co-review-section border-0">
      <h6 class="co-review-heading"><i class="bi bi-tag me-2"></i>Order Total</h6>
      <p class="co-review-val fw-bold" style="color:var(--brand-blue);font-size:20px">${formatPHP(total)}</p>
    </div>`;
}

async function placeOrder(){
  const btn=document.querySelector('#panel3 .checkout-btn');
  btn.disabled=true;
  btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Processing…';

  const cart=getCart();
  const sub=cart.reduce((s,i)=>s+parsePHP(i.price)*(i.qty||1),0);
  const disc=sub>2000?Math.round(sub*0.05):0;
  const total=sub-disc+shippingCost;

  const orderData={
    items: cart,
    shipping:{
      firstname:document.getElementById('sh_first').value,
      lastname:document.getElementById('sh_last').value,
      email:document.getElementById('sh_email').value,
      phone:document.getElementById('sh_phone').value,
      street:document.getElementById('sh_street').value,
      city:document.getElementById('sh_city').value,
      province:document.getElementById('sh_province').value,
      zip:document.getElementById('sh_zip').value,
      delivery:document.getElementById('sh_delivery').value,
    },
    payment:{ method:selectedPayMethod },
    summary:{ subtotal:sub, discount:disc, shipping:shippingCost, total },
  };

  let orderRef='ORD-'+Date.now();
  try {
    // POST to MySQL via api_order.php
    const resp = await fetch('api_order.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(orderData)
    });
    const result = await resp.json();
    if(result.ok){
      orderRef = result.order_ref || orderRef;
    } else {
      console.warn('Order save warning:', result.error);
    }
  } catch(e){ console.warn('Order API error:', e.message); }

  // Also sync cart clear to MySQL
  try {
    await fetch('api_cart.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'sync', items:[]})
    });
  } catch(e){}

  // Clear localStorage cart
  saveCart([]);
  updateCartBadge();

  // Show success modal
  document.getElementById('orderIdDisplay').textContent=orderRef;
  document.getElementById('orderDetailBox').innerHTML=`
    <div class="d-flex justify-content-between mb-1"><span>Items</span><span>${cart.length}</span></div>
    <div class="d-flex justify-content-between mb-1"><span>Total Paid</span><span class="fw-bold" style="color:var(--brand-blue)">${formatPHP(total)}</span></div>
    <div class="d-flex justify-content-between"><span>Method</span><span>${selectedPayMethod.toUpperCase()}</span></div>`;
  document.getElementById('successOverlay').classList.add('active');
  document.getElementById('successModal').classList.add('active');
}

function formatCard(i){ let v=i.value.replace(/\D/g,'').slice(0,16); i.value=v.replace(/(.{4})/g,'$1 ').trim(); }
function formatExpiry(i){ let v=i.value.replace(/\D/g,'').slice(0,4); if(v.length>=3)v=v.slice(0,2)+'/'+v.slice(2); i.value=v; }
</script>
</body>
</html>
