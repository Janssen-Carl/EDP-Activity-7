/* ══════════════════════════════════════════════════════════════ */
/* USER MANAGEMENT PAGE JAVASCRIPT */
/* ══════════════════════════════════════════════════════════════ */

let allUsers = [];

function renderUsers(data) {
  const tbody = document.getElementById("usersTable");
  tbody.innerHTML = "";

  if (data.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" style="border: none; padding: 60px 20px;">
          <div class="empty-state">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="8" cy="6" r="2.5"/><path d="M2 14c0-2 2-4 6-4s6 2 6 4"/><circle cx="15" cy="10" r="2.5"/><path d="M11 14c0-1.5 1.3-3 4-3"/></svg>
            <h3>No users found</h3>
            <p>Try adjusting your filters or search query</p>
          </div>
        </td>
      </tr>
    `;
    return;
  }

  data.forEach((user) => {
    const statusClass = `status-${user.AccountStatus.toLowerCase()}`;
    const initial = user.FullName.charAt(0).toUpperCase();
    const actionLabel =
      user.AccountStatus === "Active" ? "Deactivate" : "Activate";
    const actionClass = user.AccountStatus === "Active" ? "danger" : "toggle";
    const dateJoined = user.DateJoined
      ? new Date(user.DateJoined).toLocaleDateString("en-US", {
          year: "numeric",
          month: "short",
          day: "numeric",
        })
      : "N/A";

    const row = `
      <tr>
        <td>
          <div class="user-cell">
            <div class="user-avatar">${initial}</div>
            <div class="user-info">
              <h4>${user.FullName}</h4>
            </div>
          </div>
        </td>
        <td>${user.Email}</td>
        <td><span class="type-badge">${user.UserType}</span></td>
        <td><span class="status-badge ${statusClass}">● ${user.AccountStatus}</span></td>
        <td>${dateJoined}</td>
        <td>
          <div class="action-buttons">
            <button class="btn-sm" onclick="editUser(${user.User_ID})">Edit</button>
            <button class="btn-sm ${actionClass}" onclick="toggleStatus(${user.User_ID})">${actionLabel}</button>
          </div>
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });

  document.getElementById("resultCount").textContent = data.length;
}

async function loadUsers() {
  try {
    // // Check if user is authenticated
    // const token = API.getAccessToken();
    // console.log('TOKEN:', API.getAccessToken());

    // if (!token || token === "undefined" || token === "null") {
    //   window.location.href = "pages/login.html";
    //   return;
    // }


    // Fetch all users
    const users = await API.listUsers();
    allUsers = Array.isArray(users) ? users : [];
    filterUsers();
  } catch (err) {
    console.error("Failed to load users:", err);
    allUsers = [];
    renderUsers([]);
  }
}

function filterUsers() {
  const searchText = document.getElementById("searchInput").value.toLowerCase();
  const typeFilter = document.getElementById("typeFilter").value;
  const statusFilter = document.getElementById("statusFilter").value;

  const filtered = allUsers.filter((user) => {
    const matchesSearch =
      user.FullName.toLowerCase().includes(searchText) ||
      user.Email.toLowerCase().includes(searchText);
    const matchesType = !typeFilter || user.UserType === typeFilter;
    const matchesStatus = !statusFilter || user.AccountStatus === statusFilter;

    return matchesSearch && matchesType && matchesStatus;
  });

  renderUsers(filtered);
}

function editUser(userId) {
  localStorage.setItem("editUserId", userId);
  window.location.href = `edit-user.html?id=${userId}`;
}

async function toggleStatus(userId) {
  const user = allUsers.find((u) => u.User_ID === userId);
  if (user) {
    try {
      const newStatus = user.AccountStatus === "Active" ? "Inactive" : "Active";
      await API.updateUserStatus(userId, newStatus);
      user.AccountStatus = newStatus;
      filterUsers();
    } catch (err) {
      alert("Failed to update user status: " + (err.error || "Unknown error"));
    }
  }
}

function openCreateAccount() {
  window.location.href = "create-account.html";
}

function goBack() {
  API.clearSession();
  window.location.href = "login.html";
}

function goToTransactions() {
  window.location.href = "transactions-dashboard.html";
}

// Initialize
window.addEventListener("load", () => {
  loadUsers();
});
