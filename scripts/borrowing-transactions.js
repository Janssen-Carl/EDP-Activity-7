// API endpoint
const API_URL = '../backend/public/transactions-api.php';

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadBorrowingData();
    loadBooks();
    loadMembers();
});

/**
 * Load all borrowing transactions
 */
function loadBorrowingData() {
    fetch(`${API_URL}?endpoint=borrowing-transactions&limit=100`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayBorrowingTable(data.data);
                updateStatistics(data.data);
            } else {
                showAlert('Error loading borrowing data: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to load borrowing data', 'error');
        });
}

/**
 * Display borrowing data in DataTable
 */
function displayBorrowingTable(data) {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';

    data.forEach(record => {
        const row = document.createElement('tr');
        
        const statusBadge = `<span class="badge badge-${record.Status.toLowerCase()}">${record.Status}</span>`;
        const daysOverdue = record.DaysOverdue > 0 ? `(${record.DaysOverdue} days)` : '';
        
        const actionButtons = record.Status === 'Returned' 
            ? '<button class="btn btn-secondary" disabled>Returned</button>'
            : `<button class="btn btn-success" onclick="openReturnModal(${record.TransactionID}, '${record.BookTitle}')">Return Book</button>`;

        row.innerHTML = `
            <td>${record.TransactionID}</td>
            <td>${record.MemberName}</td>
            <td>${record.BookTitle}</td>
            <td>${record.ISBN}</td>
            <td>${new Date(record.BorrowDate).toLocaleDateString()}</td>
            <td>${new Date(record.DueDate).toLocaleDateString()} ${daysOverdue}</td>
            <td>${statusBadge}</td>
            <td>${actionButtons}</td>
        `;
        
        tbody.appendChild(row);
    });
}

/**
 * Update statistics display
 */
function updateStatistics(data) {
    let total = 0;
    let active = 0;
    let overdue = 0;
    let returned = 0;

    data.forEach(record => {
        total++;
        if (record.Status === 'Active') active++;
        else if (record.Status === 'Overdue') overdue++;
        else if (record.Status === 'Returned') returned++;
    });

    document.getElementById('statTotal').textContent = total;
    document.getElementById('statActive').textContent = active;
    document.getElementById('statOverdue').textContent = overdue;
    document.getElementById('statReturned').textContent = returned;
}

/**
 * Load available books
 */
function loadBooks() {
    fetch(`${API_URL}?endpoint=book-inventory&limit=500`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const select = document.getElementById('borrowBookId');
                data.data.forEach(book => {
                    if (book.AvailableCopies > 0) {
                        const option = document.createElement('option');
                        option.value = book.BookID;
                        option.textContent = `${book.BookTitle} (${book.AvailableCopies} available)`;
                        select.appendChild(option);
                    }
                });
            }
        })
        .catch(error => console.error('Error loading books:', error));
}

/**
 * Load available members
 */
function loadMembers() {
    fetch(`${API_URL}?endpoint=get-members`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const select = document.getElementById('borrowUserId');
                data.data.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.User_ID;
                    option.textContent = `${user.FullName} (${user.Email})`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading members:', error));
}

/**
 * Filter table by status
 */
function filterByStatus(status) {
    fetch(`${API_URL}?endpoint=borrowing-transactions&status=${status}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayBorrowingTable(data.data);
            }
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Open borrow modal
 */
function openBorrowModal() {
    document.getElementById('borrowModal').classList.add('show');
}

/**
 * Close borrow modal
 */
function closeBorrowModal() {
    document.getElementById('borrowModal').classList.remove('show');
    document.getElementById('borrowForm').reset();
    document.getElementById('borrowFormAlert').innerHTML = '';
}

/**
 * Submit borrow form
 */
function submitBorrowForm() {
    const formAlert = document.getElementById('borrowFormAlert');
    
    const bookId = document.getElementById('borrowBookId').value;
    const userId = document.getElementById('borrowUserId').value;
    const dueDays = document.getElementById('borrowDueDays').value;
    const notes = document.getElementById('borrowNotes').value;

    if (!bookId || !userId) {
        formAlert.innerHTML = '<div class="alert alert-error">Please fill in all required fields</div>';
        return;
    }

    const payload = {
        bookId: parseInt(bookId),
        userId: parseInt(userId),
        dueDays: parseInt(dueDays),
        notes: notes,
        createdBy: getCurrentUserId()
    };

    fetch(`${API_URL}?endpoint=borrow`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            formAlert.innerHTML = '<div class="alert alert-success">Book borrowed successfully!</div>';
            setTimeout(() => {
                closeBorrowModal();
                loadBorrowingData();
                showAlert('Book borrowed successfully!', 'success');
            }, 1500);
        } else {
            formAlert.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        formAlert.innerHTML = '<div class="alert alert-error">Error creating borrowing transaction</div>';
    });
}

/**
 * Open return modal
 */
function openReturnModal(transactionId, bookTitle) {
    document.getElementById('returnTransactionId').value = transactionId;
    document.getElementById('returnBookTitle').value = bookTitle;
    document.getElementById('returnModal').classList.add('show');
}

/**
 * Close return modal
 */
function closeReturnModal() {
    document.getElementById('returnModal').classList.remove('show');
    document.getElementById('returnForm').reset();
    document.getElementById('returnFormAlert').innerHTML = '';
}

/**
 * Submit return form
 */
function submitReturnForm() {
    const formAlert = document.getElementById('returnFormAlert');
    
    const transactionId = document.getElementById('returnTransactionId').value;
    const condition = document.getElementById('returnBookCondition').value;
    const notes = document.getElementById('returnNotes').value;

    const payload = {
        transactionId: parseInt(transactionId),
        processedBy: getCurrentUserId(),
        bookCondition: condition,
        notes: notes
    };

    fetch(`${API_URL}?endpoint=return-book`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            formAlert.innerHTML = '<div class="alert alert-success">Book returned successfully!</div>';
            setTimeout(() => {
                closeReturnModal();
                loadBorrowingData();
                showAlert('Book returned successfully!', 'success');
            }, 1500);
        } else {
            formAlert.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        formAlert.innerHTML = '<div class="alert alert-error">Error processing return</div>';
    });
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
