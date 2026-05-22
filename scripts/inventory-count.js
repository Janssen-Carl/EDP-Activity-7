// API endpoint
const API_URL = '../backend/public/transactions-api.php';
let currentCountId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadActiveCounts();
});

/**
 * Switch between tabs
 */
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');

    if (tabName === 'all-counts') {
        loadAllCounts();
    }
}

/**
 * Load active inventory counts
 */
function loadActiveCounts() {
    fetch(`${API_URL}?endpoint=inventory-counts&status=In%20Progress`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayActiveCounts(data.data || []);
            } else {
                showAlert('Error loading counts: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to load inventory counts', 'error');
        });
}

/**
 * Load all inventory counts
 */
function loadAllCounts() {
    fetch(`${API_URL}?endpoint=inventory-counts&limit=100`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAllCounts(data.data || []);
            } else {
                showAlert('Error loading counts: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to load inventory counts', 'error');
        });
}

/**
 * Display active counts
 */
function displayActiveCounts(data) {
    const tbody = document.getElementById('activeCountsBody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">No active inventory counts</td></tr>';
        return;
    }

    data.forEach(count => {
        const row = document.createElement('tr');
        const statusBadge = `<span class="badge badge-${count.CountStatus.toLowerCase().replace(' ', '-')}">${count.CountStatus}</span>`;
        
        row.innerHTML = `
            <td>${count.CountID}</td>
            <td>${new Date(count.CountDate).toLocaleDateString()}</td>
            <td>${count.ConductedByName}</td>
            <td>${statusBadge}</td>
            <td>${count.TotalBooksExpected}</td>
            <td>${count.TotalBooksFound || 0}</td>
            <td>${count.Discrepancies || 0}</td>
            <td>
                <button class="btn btn-success" style="padding: 5px 10px; font-size: 12px;" onclick="openRecordQuantityModal(${count.CountID})">Record</button>
                ${count.CountStatus === 'In Progress' ? `<button class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" onclick="completeCount(${count.CountID})">Complete</button>` : ''}
            </td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Display all counts
 */
function displayAllCounts(data) {
    const tbody = document.getElementById('allCountsBody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 20px;">No inventory counts found</td></tr>';
        return;
    }

    data.forEach(count => {
        const row = document.createElement('tr');
        const statusBadge = `<span class="badge badge-${count.CountStatus.toLowerCase().replace(' ', '-')}">${count.CountStatus}</span>`;
        
        row.innerHTML = `
            <td>${count.CountID}</td>
            <td>${new Date(count.CountDate).toLocaleDateString()}</td>
            <td>${count.ConductedByName}</td>
            <td>${count.VerifiedByName || '-'}</td>
            <td>${statusBadge}</td>
            <td>${count.TotalBooksExpected}</td>
            <td>${count.TotalBooksFound || 0}</td>
            <td>${count.Discrepancies || 0}</td>
            <td>
                <button class="btn" style="padding: 5px 10px; font-size: 12px;" onclick="viewCountDetails(${count.CountID})">View</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Start new inventory count
 */
function startNewCount() {
    document.getElementById('startCountModal').classList.add('show');
}

/**
 * Close start count modal
 */
function closeStartCountModal() {
    document.getElementById('startCountModal').classList.remove('show');
    document.getElementById('startCountForm').reset();
    document.getElementById('startCountAlert').innerHTML = '';
}

/**
 * Submit start count form
 */
function submitStartCount() {
    const notes = document.getElementById('countNotes').value;
    const alertDiv = document.getElementById('startCountAlert');

    const payload = {
        conductedBy: getCurrentUserId(),
        notes: notes
    };

    fetch(`${API_URL}?endpoint=start-inventory-count`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alertDiv.innerHTML = '<div class="alert alert-success">Inventory count started successfully!</div>';
            setTimeout(() => {
                closeStartCountModal();
                currentCountId = data.countId;
                openRecordQuantityModal(data.countId);
                loadActiveCounts();
                showAlert('Inventory count started!', 'success');
            }, 1000);
        } else {
            alertDiv.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alertDiv.innerHTML = '<div class="alert alert-error">Error starting inventory count</div>';
    });
}

/**
 * Open record quantity modal
 */
function openRecordQuantityModal(countId) {
    currentCountId = countId;
    document.getElementById('recordQuantityModal').classList.add('show');
    loadInventoryForCount(countId);
}

/**
 * Close record quantity modal
 */
function closeRecordQuantityModal() {
    document.getElementById('recordQuantityModal').classList.remove('show');
    currentCountId = null;
}

/**
 * Load inventory details for a count
 */
function loadInventoryForCount(countId) {
    fetch(`${API_URL}?endpoint=inventory-count&countId=${countId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayInventoryTable(data.details || []);
            } else {
                document.getElementById('recordQuantityAlert').innerHTML = 
                    `<div class="alert alert-error">${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('recordQuantityAlert').innerHTML = 
                '<div class="alert alert-error">Failed to load inventory</div>';
        });
}

/**
 * Display inventory table for recording
 */
function displayInventoryTable(details) {
    const tbody = document.getElementById('inventoryTableBody');
    tbody.innerHTML = '';

    details.forEach(item => {
        const row = document.createElement('tr');
        const variance = item.PhysicalQuantity - item.ExpectedQuantity;
        const varianceClass = variance === 0 ? '' : (variance > 0 ? 'style="background: #D1FAE5;"' : 'style="background: #FEE2E2;"');

        row.innerHTML = `
            <td>${item.BookTitle}</td>
            <td><input type="number" value="${item.ExpectedQuantity}" readonly style="width: 60px; text-align: center;"></td>
            <td>
                <input type="number" class="physical-qty-input" data-bookid="${item.BookID}" value="${item.PhysicalQuantity}" 
                       style="width: 60px; text-align: center;">
            </td>
            <td ${varianceClass}>${variance}</td>
            <td>
                <input type="text" class="notes-input" data-bookid="${item.BookID}" placeholder="Notes" value="${item.Notes || ''}"
                       style="width: 100%; padding: 4px;">
            </td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Filter inventory table
 */
function filterInventoryTable() {
    const filter = document.getElementById('bookFilter').value.toLowerCase();
    const rows = document.querySelectorAll('#inventoryTableBody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

/**
 * Save current progress
 */
function saveInventoryCountProgress(isCompleting = false) {
    if (!currentCountId) return Promise.resolve();

    const qtyInputs = document.querySelectorAll('.physical-qty-input');
    const notesInputs = document.querySelectorAll('.notes-input');
    
    const requests = [];

    qtyInputs.forEach((input, index) => {
        const bookId = parseInt(input.getAttribute('data-bookid'));
        const quantity = parseInt(input.value) || 0;
        const notes = notesInputs[index] ? notesInputs[index].value : '';

        const payload = {
            countId: currentCountId,
            bookId: bookId,
            physicalQuantity: quantity,
            notes: notes
        };

        requests.push(
            fetch(`${API_URL}?endpoint=record-quantity`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
        );
    });

    return Promise.all(requests)
        .then(() => {
            if (!isCompleting) {
                showAlert('Progress saved successfully!', 'success');
                // Reload to refresh variances
                loadInventoryForCount(currentCountId);
            }
        })
        .catch(error => {
            console.error('Error saving progress:', error);
            if (!isCompleting) {
                showAlert('Error saving progress', 'error');
            }
        });
}

/**
 * Complete inventory count
 */
function completeInventoryCount() {
    if (!currentCountId) return;

    // Save progress first, then complete
    saveInventoryCountProgress(true).then(() => {
        const payload = {
            countId: currentCountId,
            conductedBy: getCurrentUserId()
        };

        fetch(`${API_URL}?endpoint=complete-inventory-count`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Inventory count completed successfully!', 'success');
                closeRecordQuantityModal();
                loadActiveCounts();
            } else {
                showAlert('Error completing count: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to complete inventory count', 'error');
        });
    });
}

/**
 * Complete a count from list
 */
function completeCount(countId) {
    if (confirm('Complete this inventory count?')) {
        const payload = {
            countId: countId,
            conductedBy: getCurrentUserId()
        };

        fetch(`${API_URL}?endpoint=complete-inventory-count`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Inventory count completed!', 'success');
                loadActiveCounts();
            } else {
                showAlert('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to complete inventory count', 'error');
        });
    }
}

/**
 * View count details
 */
function viewCountDetails(countId) {
    alert('Viewing details for count ' + countId + '. This feature will open a detailed view page.');
}

/**
 * Get current user ID from localStorage
 */
function getCurrentUserId() {
    try {
        const userStr = localStorage.getItem('user') || localStorage.getItem('userData');
        if (userStr) {
            const user = JSON.parse(userStr);
            return user.User_ID || user.id || user.userId || 0;
        }
    } catch (e) {
        console.error('Error parsing user data', e);
    }
    return 0;
}

/**
 * Show alert message
 */
function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    alertContainer.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
    setTimeout(() => {
        alertContainer.innerHTML = '';
    }, 5000);
}
