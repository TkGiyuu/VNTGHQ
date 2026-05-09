<footer class="vntg-footer mt-5 py-4">
  <div class="container-fluid px-4">
    <div class="row align-items-center">
      <div class="col-md-4">
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="brand-icon sm"><i class="bi bi-bag-heart-fill"></i></div>
          <div>
            <span class="brand-name sm">VNTG HQ</span>
            <span class="d-block brand-tagline">Home of Pre-loved Items</span>
          </div>
        </div>
        <p class="footer-copy">© 2026 VNTG HQ. All rights reserved.</p>
      </div>
      <div class="col-md-4 text-center">
        <div class="footer-links d-flex justify-content-center gap-4">
          <a href="homepage.php">Home</a>
          <a href="cart.php">Cart</a>
          <a href="profile.php">Profile</a>
          <a href="about.php">About</a>
        </div>
      </div>
      <div class="col-md-4 text-end">
        <div class="d-flex justify-content-end gap-3">
          <a href="#" class="footer-social" onclick="openSocialConnect('Facebook');return false"><i class="bi bi-facebook"></i></a>
          <a href="#" class="footer-social" onclick="openSocialConnect('Instagram');return false"><i class="bi bi-instagram"></i></a>
        </div>
      </div>
    </div>
  </div>
</footer>

<!-- Social Connect Modal -->
<div class="pmodal-overlay" id="socialConnectOverlay" onclick="closeSocialConnect()"></div>
<div class="pmodal" id="socialConnectModal" style="max-width:400px">
  <div class="pmodal-header">
    <div class="pmodal-icon" id="socialConnectIcon"><i class="bi bi-link-45deg"></i></div>
    <div><h5 class="pmodal-title" id="socialConnectTitle">Connect Account</h5><p class="pmodal-sub">Link your social profile</p></div>
    <button class="pmodal-close" onclick="closeSocialConnect()"><i class="bi bi-x-lg"></i></button>
  </div>
  <div class="pmodal-body">
    <p class="text-muted small mb-3" id="socialConnectDesc">Enter your account details to link.</p>
    <div class="mb-3">
      <label class="pmodal-label" id="socialInputLabel">Profile URL or Username</label>
      <div class="input-with-icon">
        <i class="bi bi-at input-icon" id="socialInputIcon"></i>
        <input type="text" id="socialInputField" class="form-control auth-input" placeholder="@yourusername">
      </div>
    </div>
    <div class="pmodal-footer">
      <button class="btn pmodal-btn-cancel" onclick="closeSocialConnect()">Cancel</button>
      <button class="btn pmodal-btn-save" onclick="saveSocialConnect()">Connect</button>
    </div>
  </div>
</div>
<script>
let _socialPlatform='';
function openSocialConnect(platform){
  _socialPlatform=platform;
  document.getElementById('socialConnectTitle').textContent='Connect '+platform;
  document.getElementById('socialConnectDesc').textContent='Enter your '+platform+' username to link it to your VNTG HQ profile.';
  document.getElementById('socialInputLabel').textContent=platform+' Username';
  document.getElementById('socialInputField').placeholder='@your'+platform.toLowerCase()+'username';
  document.getElementById('socialConnectIcon').innerHTML='<i class="bi bi-'+platform.toLowerCase()+' "></i>';
  document.getElementById('socialConnectOverlay').classList.add('active');
  document.getElementById('socialConnectModal').classList.add('active');
}
function closeSocialConnect(){
  document.getElementById('socialConnectOverlay').classList.remove('active');
  document.getElementById('socialConnectModal').classList.remove('active');
}
function saveSocialConnect(){
  const val=document.getElementById('socialInputField').value.trim();
  if(!val){ if(typeof showToast==='function') showToast('<i class="bi bi-exclamation-triangle me-2"></i>Please enter your username.','fav'); return; }
  if(typeof showToast==='function') showToast('<i class="bi bi-check-circle me-2"></i><strong>'+_socialPlatform+'</strong> account @'+val+' linked!','cart');
  closeSocialConnect();
}
</script>
