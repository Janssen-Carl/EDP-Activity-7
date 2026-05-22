/* ══════════════════════════════════════════════════════════════ */
/* LOGIN PAGE JAVASCRIPT */
/* ══════════════════════════════════════════════════════════════ */

let currentRole = "Member";
let pwVisible = false;

function setRole(role) {
  currentRole = role;
  document
    .getElementById("btnMember")
    .classList.toggle("active", role === "Member");
  document
    .getElementById("btnLibrarian")
    .classList.toggle("active", role === "Librarian");
  document
    .getElementById("btnMember")
    .setAttribute("aria-selected", role === "Member");
  document
    .getElementById("btnLibrarian")
    .setAttribute("aria-selected", role === "Librarian");
  clearErrors();
}

function togglePassword() {
  pwVisible = !pwVisible;
  const input = document.getElementById("pwInput");
  input.type = pwVisible ? "text" : "password";
  document.getElementById("eyeIcon").innerHTML = pwVisible
    ? '<path d="M13.875 18.825A10.05 10.05 0 0112 19c-5.5 0-9-6-9-6a17.7 17.7 0 013.1-4.1M6.53 6.53A9.97 9.97 0 0112 5c5.5 0 9 6 9 6a17.6 17.6 0 01-2.13 2.97M6.53 6.53L3 3m3.53 3.53l10.94 10.94M9.88 9.88A3 3 0 0114.12 14.12" stroke-linecap="round"/>'
    : '<path d="M1 10s3.5-6 9-6 9 6 9 6-3.5 6-9 6-9-6-9-6z"/><circle cx="10" cy="10" r="2.5"/>';
}

function clearErrors() {
  ["emailInput", "pwInput"].forEach((id) =>
    document.getElementById(id).classList.remove("error"),
  );
  ["emailErr", "pwErr"].forEach((id) =>
    document.getElementById(id).classList.remove("visible"),
  );
  document
    .getElementById("alertBanner")
    .classList.remove("visible", "alert-error", "alert-success");
}

function validateForm() {
  let valid = true;
  const email = document.getElementById("emailInput").value.trim();
  const pw = document.getElementById("pwInput").value;

  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    document.getElementById("emailInput").classList.add("error");
    document.getElementById("emailErr").classList.add("visible");
    valid = false;
  }
  if (!pw) {
    document.getElementById("pwInput").classList.add("error");
    document.getElementById("pwErr").classList.add("visible");
    valid = false;
  }
  return valid;
}

function setLoading(on) {
  const btn = document.getElementById("signInBtn");
  btn.disabled = on;
  document.getElementById("spinner").style.display = on ? "block" : "none";
  document.getElementById("btnText").textContent = on
    ? "Signing in…"
    : "Sign In";
  document.getElementById("btnArrow").style.display = on ? "none" : "block";
}

async function handleSignIn() {
  clearErrors();
  if (!validateForm()) return;

  setLoading(true);

  const email = document.getElementById("emailInput").value.trim();
  const pw = document.getElementById("pwInput").value;

  try {
    console.log('Attempting login with email:', email);
    const response = await API.login(email, pw, currentRole);
    console.log('Login response:', response);

    // Validate response
    if (!response || response.error || response.success === false) {
      console.error('Invalid response structure:', response);
      throw response;
    }

    // Ensure user data is saved to localStorage for dashboard compatibility
    if (response.user && response.token) {
      console.log('Saving user and token to localStorage');
      localStorage.setItem("accessToken", response.token);
      localStorage.setItem("userData", JSON.stringify(response.user));
      localStorage.setItem("user", JSON.stringify(response.user)); // For compatibility with transaction pages
    } else if (response.data && response.data.user && response.data.token) {
      // Handle nested response structure
      console.log('Saving nested user and token to localStorage');
      localStorage.setItem("accessToken", response.data.token);
      localStorage.setItem("userData", JSON.stringify(response.data.user));
      localStorage.setItem("user", JSON.stringify(response.data.user));
    } else {
      console.error('Response missing user or token:', response);
      throw new Error('Response missing user or token');
    }

    console.log('Stored data. User:', localStorage.getItem('user'));
    
    setLoading(false);
    const banner = document.getElementById("alertBanner");
    banner.className = "alert alert-success visible";
    document.getElementById("alertText").textContent =
      `Welcome back! Redirecting to transactions dashboard…`;

    setTimeout(() => {
      console.log('Redirecting to transactions-dashboard.html');
      window.location.href = "transactions-dashboard.html";
    }, 1500);
  } catch (err) {
    console.error('Login error:', err);
    setLoading(false);
    const banner = document.getElementById("alertBanner");
    banner.className = "alert alert-error visible";
    document.getElementById("alertText").textContent =
      err.error || err.message || "Invalid email or password. Please try again.";
    document.getElementById("emailInput").classList.add("error");
    document.getElementById("pwInput").classList.add("error");
  }
}

// Enter key support
document.addEventListener("keydown", (e) => {
  if (e.key === "Enter") handleSignIn();
});
