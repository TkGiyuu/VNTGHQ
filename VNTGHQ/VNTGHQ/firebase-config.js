// ============================================================
//  VNTG HQ — Firebase Auth Config (for Google/Facebook login)
//  Only used for OAuth popup. All data is stored in MySQL.
//
//  Replace these values with YOUR Firebase project config:
//  https://console.firebase.google.com → Project Settings → Web App
// ============================================================

const firebaseConfig = {
  apiKey:            "AIzaSyDEMO_REPLACE_WITH_YOUR_KEY",
  authDomain:        "vntghq-demo.firebaseapp.com",
  projectId:         "vntghq-demo",
  storageBucket:     "vntghq-demo.appspot.com",
  messagingSenderId: "000000000000",
  appId:             "1:000000000000:web:abc123demo"
};

// Guard: don't initialise twice if included on multiple pages
if (!firebase.apps.length) {
  firebase.initializeApp(firebaseConfig);
}
const fbAuth = firebase.auth();

// Providers
const googleProvider   = new firebase.auth.GoogleAuthProvider();
googleProvider.addScope('profile');
googleProvider.addScope('email');

const facebookProvider = new firebase.auth.FacebookAuthProvider();
facebookProvider.addScope('email');
facebookProvider.addScope('public_profile');

// ── Google Sign-In ────────────────────────────────────────────────────────────
async function signInWithGoogle() {
  const btn = document.querySelector('.social-google');
  if (btn) { btn.disabled=true; btn.textContent='Connecting…'; }
  try {
    const result = await fbAuth.signInWithPopup(googleProvider);
    await _syncToMySQL(result.user, 'google');
  } catch (err) {
    if (btn) { btn.disabled=false; btn.innerHTML='<svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg> Continue with Google'; }
    _handleAuthError(err, 'Google');
  }
}

// ── Facebook Sign-In ──────────────────────────────────────────────────────────
async function signInWithFacebook() {
  const btn = document.querySelector('.social-fb');
  if (btn) { btn.disabled=true; btn.textContent='Connecting…'; }
  try {
    const result = await fbAuth.signInWithPopup(facebookProvider);
    await _syncToMySQL(result.user, 'facebook');
  } catch (err) {
    if (btn) { btn.disabled=false; btn.innerHTML='<svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg> Continue with Facebook'; }
    _handleAuthError(err, 'Facebook');
  }
}

// ── Send Firebase user to PHP → saved in MySQL via social_callback.php ────────
async function _syncToMySQL(user, provider) {
  const parts     = (user.displayName || user.email || '').split(' ');
  const firstname = parts[0] || 'User';
  const lastname  = parts.slice(1).join(' ') || '';
  const token     = await user.getIdToken(true);

  const resp = await fetch('social_callback.php', {
    method:  'POST',
    headers: {'Content-Type':'application/json'},
    body:    JSON.stringify({
      uid:       user.uid,
      firstname, lastname,
      email:     user.email    || '',
      photoURL:  user.photoURL || '',
      provider,
      token
    })
  });
  const data = await resp.json().catch(() => ({ok:false, error:'Parse error'}));
  if (data.ok) {
    window.location.href = 'dashboard.php';
  } else {
    alert('Login failed: ' + (data.error || 'Server error'));
  }
}

// ── Error messages ─────────────────────────────────────────────────────────────
function _handleAuthError(err, provider) {
  const msgs = {
    'auth/popup-closed-by-user':      'Popup was closed. Please try again.',
    'auth/cancelled-popup-request':   'Another popup is already open.',
    'auth/popup-blocked':             'Popup was blocked. Please allow popups for localhost.',
    'auth/operation-not-allowed':     provider + ' sign-in is not enabled in Firebase Console.',
    'auth/invalid-api-key':           'Firebase API key is invalid. Please update firebase-config.js.',
    'auth/configuration-not-found':   'Firebase is not configured. Update firebase-config.js with your project credentials.',
    'auth/account-exists-with-different-credential':
                                      'An account with this email already exists. Try signing in with username/password.',
  };
  const msg = msgs[err.code] || err.message;
  if (typeof showToast === 'function') {
    showToast('<i class="bi bi-exclamation-triangle me-2"></i>' + msg, 'fav');
  } else {
    alert(provider + ' Error: ' + msg);
  }
  console.error('[Firebase ' + provider + ']', err.code, err.message);
}
