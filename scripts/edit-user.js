/* ══════════════════════════════════════════════════════════════ */
/* EDIT USER PAGE JAVASCRIPT */
/* ══════════════════════════════════════════════════════════════ */

async function initUser() {
  try {
    // const editUserId = localStorage.getItem("editUserId");

    // console.log("editUserId (raw):", editUserId);
    // console.log("editUserId (parsed):", parseInt(editUserId));

    // if (!editUserId || isNaN(parseInt(editUserId))) {
    //   window.location.href = "pages/user-management.html";
    //   return;
    // }

    const params = new URLSearchParams(window.location.search);
    const id = params.get("id");

    // const id = parseInt(editUserId);

    // Fetch user data from API
    const user = await API.getUser(id);

    document.getElementById("userName").textContent = user.FullName;
    document.getElementById("userEmail").textContent = user.Email;
    const userTypeText = document.getElementById("userTypeText");
    if (userTypeText) userTypeText.textContent = user.UserType;
    document.getElementById("userAvatar").textContent =
      user.FullName.charAt(0).toUpperCase();

    document.getElementById("fullName").value = user.FullName;
    document.getElementById("email").value = user.Email;
    document.getElementById("phone").value = user.PhoneNumber || "";
    document.getElementById("userType").value = user.UserType;
    document.getElementById("accountStatus").value = user.AccountStatus;
    document.getElementById("currentStatus").textContent = user.AccountStatus;
    document.getElementById("currentStatus").className =
      `status-badge status-${user.AccountStatus.toLowerCase()}`;
    document.getElementById("accountType").textContent = user.UserType;
    document.getElementById("memberSince").textContent = "N/A";
    document.getElementById("lastLogin").textContent = "N/A";
  } catch (err) {
    console.error("Failed to load user:", err);

    localStorage.removeItem("editUserId");

    alert("Failed to load user data");
    window.location.href = "user-management.html";
  }
}

function clearErrors() {
  document
    .querySelectorAll(".error-msg")
    .forEach((el) => el.classList.remove("visible"));
  document
    .querySelectorAll("input")
    .forEach((el) => el.classList.remove("error"));
  document.getElementById("alertBanner").classList.remove("visible");
}

function validateForm() {
  clearErrors();
  let valid = true;

  const fullName = document.getElementById("fullName").value.trim();
  if (!fullName) {
    document.getElementById("fullName").classList.add("error");
    valid = false;
  }

  const email = document.getElementById("email").value.trim();
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    document.getElementById("email").classList.add("error");
    valid = false;
  }

  return valid;
}

function setLoading(on) {
  const btn = document.getElementById("submitBtn");
  btn.disabled = on;
  document.getElementById("spinner").style.display = on ? "block" : "none";
  document.getElementById("btnText").textContent = on
    ? "Saving…"
    : "Save Changes";
}

async function handleUpdateUser(event) {
  event.preventDefault();

  if (!validateForm()) return;

  setLoading(true);

  try {
    const params = new URLSearchParams(window.location.search);
    const id = params.get("id");

    const userData = {
      FullName: document.getElementById("fullName").value.trim(),
      Email: document.getElementById("email").value.trim(),
      PhoneNumber: document.getElementById("phone").value.trim() || null,
      UserType: document.getElementById("userType").value,
      AccountStatus: document.getElementById("accountStatus").value,
    };

    await API.updateUser(parseInt(id), userData);

    setLoading(false);
    document.getElementById("alertBanner").classList.add("visible");
    document.getElementById("alertBanner").classList.remove("alert-error");
    document.getElementById("alertBanner").classList.add("alert-success");
    document.getElementById("alertText").textContent =
      "User updated successfully!";

    setTimeout(() => {
      window.location.href = "user-management.html";
    }, 1500);
  } catch (err) {
    setLoading(false);
    document.getElementById("alertBanner").classList.add("visible");
    document.getElementById("alertBanner").classList.remove("alert-success");
    document.getElementById("alertBanner").classList.add("alert-error");
    document.getElementById("alertText").textContent =
      err.error || "Failed to update user";
  }
}

function discardChanges() {
  if (confirm("Discard all changes?")) {
    initUser();
  }
}

function goBack() {
  window.location.href = "user-management.html";
}

// Initialize on page load
window.addEventListener("load", initUser);
