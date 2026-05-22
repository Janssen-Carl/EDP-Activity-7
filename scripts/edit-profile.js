/* ══════════════════════════════════════════════════════════════ */
/* EDIT PROFILE PAGE JAVASCRIPT */
/* ══════════════════════════════════════════════════════════════ */

async function initProfile() {
  // const token = API.getAccessToken();
  // const sessionUser = API.getUser();
  // if (!token || !sessionUser?.id) {
  //   window.location.href = 'pages/login.html';
  //   return;
  // }

  try {
    const user = await API.getUser(sessionUser.id);

    document.getElementById('profileName').textContent = user.FullName;
    document.getElementById('profileEmail').textContent = user.Email;
    document.getElementById('profileType').textContent = user.UserType;
    document.getElementById('profileAvatar').textContent = user.FullName.charAt(0).toUpperCase();

    document.getElementById('fullName').value = user.FullName || '';
    document.getElementById('email').value = user.Email || '';
    document.getElementById('phone').value = user.PhoneNumber || '';
    document.getElementById('accountType').textContent = user.UserType || '';
    document.getElementById('currentStatus').textContent = user.AccountStatus || '';
    document.getElementById('currentStatus').className = `status-badge ${String(user.AccountStatus || '').toLowerCase()}`;
    document.getElementById('memberSince').textContent = 'N/A';
    document.getElementById('accountStatus').value = user.AccountStatus || 'Active';
  } catch (err) {
    console.error('Failed to load profile:', err);
    window.location.href = 'user-management.html';
  }
}

function clearErrors() {
  document.querySelectorAll('.error-msg').forEach(el => el.classList.remove('visible'));
  document.querySelectorAll('input').forEach(el => el.classList.remove('error'));
  document.getElementById('alertBanner').classList.remove('visible');
}

function validateForm() {
  clearErrors();
  let valid = true;

  const fullName = document.getElementById('fullName').value.trim();
  if (!fullName) {
    document.getElementById('fullName').classList.add('error');
    document.getElementById('fullNameErr').classList.add('visible');
    valid = false;
  }

  const email = document.getElementById('email').value.trim();
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    document.getElementById('email').classList.add('error');
    document.getElementById('emailErr').classList.add('visible');
    valid = false;
  }

  return valid;
}

function setLoading(on) {
  const btn = document.getElementById('submitBtn');
  btn.disabled = on;
  document.getElementById('spinner').style.display = on ? 'block' : 'none';
  document.getElementById('btnText').textContent = on ? 'Saving…' : 'Save Changes';
}

async function handleUpdateProfile(event) {
  event.preventDefault();

  if (!validateForm()) return;

  setLoading(true);

  try {
    const sessionUser = API.getUser();
    await API.updateUser(sessionUser.id, {
      FullName: document.getElementById('fullName').value.trim(),
      Email: document.getElementById('email').value.trim(),
      PhoneNumber: document.getElementById('phone').value.trim() || null,
      AccountStatus: document.getElementById('accountStatus').value,
      // userType not editable here; keep existing
    });
  } catch (err) {
    setLoading(false);
    document.getElementById('alertBanner').classList.add('visible');
    document.getElementById('alertBanner').classList.remove('alert-success');
    document.getElementById('alertBanner').classList.add('alert-error');
    document.getElementById('alertText').textContent = err.error || 'Failed to update profile.';
    return;
  }

  setLoading(false);
  document.getElementById('alertBanner').classList.add('visible');
  document.getElementById('alertBanner').classList.remove('alert-error');
  document.getElementById('alertBanner').classList.add('alert-success');
  document.getElementById('alertText').textContent = 'Profile updated successfully!';

  setTimeout(() => {
    window.location.href = 'user-management.html';
  }, 1500);
}

function discardChanges() {
  if (confirm('Discard all changes?')) {
    initProfile();
  }
}

function openChangePassword() {
  window.location.href = 'change-password.html';
}

function goBack() {
  window.location.href = 'user-management.html';
}

// Initialize on page load
window.addEventListener('load', initProfile);
