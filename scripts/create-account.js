/* ══════════════════════════════════════════════════════════════ */
/* CREATE ACCOUNT PAGE JAVASCRIPT */
/* ══════════════════════════════════════════════════════════════ */
const pwVisible = {};

function togglePassword(fieldId = 'password', iconId = 'eyeIcon') {
  const input = document.getElementById(fieldId);
  if (!input) return;
  pwVisible[fieldId] = !pwVisible[fieldId];
  input.type = pwVisible[fieldId] ? 'text' : 'password';

  const eye = document.getElementById(iconId);
  if (!eye) return;
  eye.innerHTML = pwVisible[fieldId]
    ? '<path d="M13.875 18.825A10.05 10.05 0 0112 19c-5.5 0-9-6-9-6a17.7 17.7 0 013.1-4.1M6.53 6.53A9.97 9.97 0 0112 5c5.5 0 9 6 9 6a17.6 17.6 0 01-2.13 2.97M6.53 6.53L3 3m3.53 3.53l10.94 10.94M9.88 9.88A3 3 0 0114.12 14.12" stroke-linecap="round"/>'
    : '<path d="M1 10s3.5-6 9-6 9 6 9 6-3.5 6-9 6-9-6-9-6z"/><circle cx="10" cy="10" r="2.5"/>';
}

function clearErrors() {
  document.querySelectorAll('.error-msg').forEach(el => el.classList.remove('visible'));
  document.querySelectorAll('input, select').forEach(el => el.classList.remove('error'));
  document.getElementById('alertBanner').classList.remove('visible');
}

function validateForm() {
  clearErrors();
  let valid = true;

  // Full Name
  const fullName = document.getElementById('fullName').value.trim();
  if (!fullName) {
    document.getElementById('fullName').classList.add('error');
    document.getElementById('fullNameErr').classList.add('visible');
    valid = false;
  }

  // Email
  const email = document.getElementById('email').value.trim();
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    document.getElementById('email').classList.add('error');
    document.getElementById('emailErr').classList.add('visible');
    valid = false;
  }

  // Password
  const password = document.getElementById('password').value;
  if (!password || password.length < 8) {
    document.getElementById('password').classList.add('error');
    document.getElementById('passwordErr').classList.add('visible');
    valid = false;
  }

  // Confirm Password
  const confirmPassword = document.getElementById('confirmPassword').value;
  if (confirmPassword !== password) {
    document.getElementById('confirmPassword').classList.add('error');
    document.getElementById('confirmPasswordErr').classList.add('visible');
    valid = false;
  }

  // User Type (if present in markup)
  const userTypeEl = document.getElementById('userType');
  if (userTypeEl && !userTypeEl.value) {
    userTypeEl.classList.add('error');
    valid = false;
  }

  // Account Status (if present in markup)
  const accountStatusEl = document.getElementById('accountStatus');
  if (accountStatusEl && !accountStatusEl.value) {
    accountStatusEl.classList.add('error');
    valid = false;
  }

  // Agreement checkbox
  if (!document.getElementById('agreeTerms').checked) {
    document.getElementById('alertBanner').classList.add('visible', 'alert-error');
    document.getElementById('alertText').textContent = 'You must agree to the Terms and Conditions.';
    valid = false;
  }

  return valid;
}

function setLoading(on) {
  const btn = document.getElementById('submitBtn');
  btn.disabled = on;
  document.getElementById('spinner').style.display = on ? 'block' : 'none';
  document.getElementById('btnText').textContent = on ? 'Creating…' : 'Create Account';
}

async function handleCreateAccount(event) {
  event.preventDefault();

  if (!validateForm()) return;

  setLoading(true);

  try {
    const userData = {
      FullName: document.getElementById('fullName').value.trim(),
      Email: document.getElementById('email').value.trim(),
      PhoneNumber: document.getElementById('phone').value.trim() || null,
      UserType: document.getElementById('userType')?.value || 'Member',
      AccountStatus: document.getElementById('accountStatus')?.value || 'Active',
      password: document.getElementById('password').value,
    };

    await API.createUser(userData);

    setLoading(false);
    document.getElementById('alertBanner').classList.add('visible', 'alert-success');
    document.getElementById('alertBanner').classList.remove('alert-error');
    document.getElementById('alertText').textContent = 'Account created successfully! Redirecting...';

    setTimeout(() => {
      window.location.href = 'user-management.html';
    }, 1500);
  } catch (err) {
    setLoading(false);
    document.getElementById('alertBanner').classList.add('visible', 'alert-error');
    document.getElementById('alertBanner').classList.remove('alert-success');
    document.getElementById('alertText').textContent = err.error || 'Failed to create account. Please try again.';
  }
}

function goBack() {
  if (confirm('Discard changes and go back?')) {
    window.location.href = 'user-management.html';
  }
}
