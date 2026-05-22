/* ══════════════════════════════════════════════════════════════ */
/* FORGOT PASSWORD PAGE JAVASCRIPT */
/* ══════════════════════════════════════════════════════════════ */

let resendCooldown = false;
let resendTimer = null;

function clearErrors() {
  document.getElementById('emailInput').classList.remove('error');
  document.getElementById('emailErr').classList.remove('visible');
  document.getElementById('alertBanner').classList.remove('visible');
}

function validateEmail() {
  const val = document.getElementById('emailInput').value.trim();
  if (!val || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
    document.getElementById('emailInput').classList.add('error');
    document.getElementById('emailErr').classList.add('visible');
    document.getElementById('emailErrText').textContent = val
      ? 'Please enter a valid email address.'
      : 'Email is required.';
    return false;
  }
  return true;
}

function setLoading(on) {
  const btn = document.getElementById('sendBtn');
  btn.disabled = on;
  document.getElementById('spinner').style.display = on ? 'block' : 'none';
  document.getElementById('sendIcon').style.display = on ? 'none' : 'block';
  document.getElementById('btnText').textContent = on ? 'Sending…' : 'Send Reset Link';
}

async function handleSend() {
  clearErrors();
  if (!validateEmail()) return;

  setLoading(true);

  const email = document.getElementById('emailInput').value.trim();

  try {
    const response = await API.forgotPassword(email);
    
    setLoading(false);
    // Always show success message for security (don't reveal if email is registered)
    showSuccess(email);
  } catch (err) {
    setLoading(false);
    // Still show success for security reasons
    showSuccess(email);
  }
}

function showSuccess(email) {
  document.getElementById('sentEmail').textContent = email;
  document.getElementById('formPanel').style.display = 'none';
  document.getElementById('successPanel').style.display = 'block';
  startResendCooldown();
}

function startResendCooldown(seconds = 60) {
  resendCooldown = true;
  const btn = document.getElementById('resendText');
  let remaining = seconds;
  btn.textContent = `Resend in ${remaining}s`;
  document.querySelector('.btn-outline').disabled = true;

  resendTimer = setInterval(() => {
    remaining--;
    if (remaining <= 0) {
      clearInterval(resendTimer);
      resendCooldown = false;
      btn.textContent = 'Resend Email';
      document.querySelector('.btn-outline').disabled = false;
    } else {
      btn.textContent = `Resend in ${remaining}s`;
    }
  }, 1000);
}

async function handleResend() {
  if (resendCooldown) return;
  const email = document.getElementById('sentEmail').textContent;

  document.querySelector('.btn-outline').disabled = true;
  document.getElementById('resendText').textContent = 'Sending…';

  try {
    const response = await API.forgotPassword(email);
    startResendCooldown(60);
  } catch (err) {
    // Always show success for security
    startResendCooldown(60);
  }
}

// Enter key
document.addEventListener('keydown', e => {
  if (e.key === 'Enter' && document.getElementById('formPanel').style.display !== 'none') {
    handleSend();
  }
});
