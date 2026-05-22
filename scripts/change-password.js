/* ══════════════════════════════════════════════════════════════ */
/* CHANGE PASSWORD PAGE JAVASCRIPT */
/* ══════════════════════════════════════════════════════════════ */

let pwVisibility = {
  currentPw: false,
  newPw: false,
  confirmPw: false
};

function togglePasswordVisibility(fieldId) {
  const input = document.getElementById(fieldId);
  pwVisibility[fieldId] = !pwVisibility[fieldId];
  input.type = pwVisibility[fieldId] ? 'text' : 'password';
}

function checkPasswordStrength() {
  const pw = document.getElementById('newPw').value;
  const fill = document.getElementById('strengthFill');
  const text = document.getElementById('strengthText');

  let strength = 'weak';
  if (pw.length >= 8) {
    if (pw.length >= 12 && /[A-Z]/.test(pw) && /[0-9]/.test(pw) && /[!@#$%^&*]/.test(pw)) {
      strength = 'strong';
    } else if (pw.length >= 10 || (/[0-9]/.test(pw) && /[A-Z]/.test(pw))) {
      strength = 'medium';
    }
  }

  fill.className = `strength-fill ${strength}`;
  text.textContent = `Password strength: ${strength.charAt(0).toUpperCase() + strength.slice(1)}`;
}

function clearErrors() {
  document.querySelectorAll('.error-msg').forEach(el => el.classList.remove('visible'));
  document.querySelectorAll('input').forEach(el => el.classList.remove('error'));
  document.getElementById('alertBanner').classList.remove('visible');
}

function validateForm() {
  clearErrors();
  let valid = true;

  const currentPw = document.getElementById('currentPw').value;
  if (!currentPw) {
    document.getElementById('currentPw').classList.add('error');
    document.getElementById('currentPwErr').classList.add('visible');
    valid = false;
  }

  const newPw = document.getElementById('newPw').value;
  if (!newPw || newPw.length < 8) {
    document.getElementById('newPw').classList.add('error');
    document.getElementById('newPwErr').classList.add('visible');
    valid = false;
  }

  const confirmPw = document.getElementById('confirmPw').value;
  if (confirmPw !== newPw) {
    document.getElementById('confirmPw').classList.add('error');
    document.getElementById('confirmPwErr').classList.add('visible');
    valid = false;
  }

  return valid;
}

function setLoading(on) {
  const btn = document.getElementById('submitBtn');
  btn.disabled = on;
  document.getElementById('spinner').style.display = on ? 'block' : 'none';
  document.getElementById('btnText').textContent = on ? 'Changing…' : 'Change Password';
}

async function handleChangePassword(event) {
  event.preventDefault();

  if (!validateForm()) return;

  setLoading(true);

  try {
    const currentPassword = document.getElementById('currentPw').value;
    const newPassword = document.getElementById('newPw').value;

    const response = await API.changePassword(currentPassword, newPassword);

    setLoading(false);
    document.getElementById('alertBanner').classList.add('visible');
    document.getElementById('alertBanner').classList.remove('alert-error');
    document.getElementById('alertBanner').classList.add('alert-success');
    document.getElementById('alertText').textContent = 'Password changed successfully! Redirecting…';

    setTimeout(() => {
      window.location.href = 'user-management.html';
    }, 1500);
  } catch (err) {
    setLoading(false);
    document.getElementById('alertBanner').classList.add('visible');
    document.getElementById('alertBanner').classList.remove('alert-success');
    document.getElementById('alertBanner').classList.add('alert-error');
    document.getElementById('alertText').textContent = err.error || 'Failed to change password. Please try again.';
  }
}

function goBack() {
  window.location.href = 'user-management.html';
}
