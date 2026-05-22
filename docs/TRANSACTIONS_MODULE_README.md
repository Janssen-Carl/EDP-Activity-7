# 📚 e-Library System - Transaction Management Module

## Overview

This comprehensive module adds three primary transaction types to the Metro City e-Library System with advanced reporting and Excel export capabilities.

### Three Core Transactions Implemented:

1. **Book Borrowing Transactions** - Track member book loans with due dates
2. **Book Return Transactions** - Process book returns and track book conditions
3. **Book Inventory Count Transactions** - Conduct and verify physical inventory counts

---

## Installation & Setup

### Step 1: Database Migration

Run the migration SQL file to create all necessary tables:

```bash
# Copy the migration file to your MySQL client
mysql -u root -p elibrary_system < backend/database/migrations/001_create_transactions_tables.sql
```

**Tables Created:**
- `book_inventory` - Tracks inventory levels
- `borrowing_transactions` - Loan records
- `return_transactions` - Return records
- `inventory_counts` - Count sessions
- `inventory_count_details` - Count line items
- `transaction_logs` - Audit trail

### Step 2: Install PhpSpreadsheet (for Excel Export)

Required for generating professional Excel reports with charts:

```bash
cd backend
composer require phpoffice/phpspreadsheet
```

If composer is not installed:
1. Download from [getcomposer.org](https://getcomposer.org/)
2. Run the installer
3. Then run the composer command above

### Step 3: Create Reports Directory

```bash
mkdir -p public/reports
chmod 755 public/reports
```

---

## Features

### 1. Book Borrowing Management
**File:** `borrowing-transactions.html`

- ✅ Create new borrowing transactions
- ✅ Track active borrowings
- ✅ Monitor overdue books
- ✅ Automatic due date calculation (default 14 days)
- ✅ Real-time statistics dashboard
- ✅ Book availability tracking

### 2. Book Return Processing
**Integrated with borrowing-transactions.html**

- ✅ Process book returns
- ✅ Track book condition (Good, Minor Damage, Major Damage, Lost)
- ✅ Calculate late fees automatically (₱50/day)
- ✅ Update inventory based on condition
- ✅ Generate return receipts

### 3. Inventory Count Management
**File:** `inventory-count.html`

- ✅ Start new physical inventory count sessions
- ✅ Record physical quantities for each book
- ✅ Track discrepancies automatically
- ✅ Complete and verify counts
- ✅ Monitor in-progress and completed counts
- ✅ Generate variance reports

### 4. Report Generation with DataGrid
**Files:** `borrowing-report.html`, `return-report.html`, `inventory-report.html`

#### Borrowing Report Features:
- Summary statistics (Total, Active, Overdue, Returned)
- Estimated fine calculations
- Filter by date range and status
- DataGrid display with sorting/filtering
- Export to Excel with charts

#### Return Report Features:
- Book condition distribution
- Late fee analysis
- Librarian tracking
- Variance by condition type
- Professional Excel export

#### Inventory Report Features:
- Expected vs physical quantities
- Discrepancy highlighting
- Count session details
- Variance analysis
- Approval workflow tracking

### 5. Excel Export with Professional Formatting

All reports export to Excel with:

✅ **Sheet 1: Formatted Report**
- Company header with logo placeholder
- Report title and metadata
- Summary section with key metrics
- Formatted data table
- Signature placeholder for authorization
- Professional color scheme

✅ **Sheet 2: Analytics & Charts**
- Pie charts for status distribution
- Bar charts for top items
- Trend visualization
- Key performance indicators
- Analysis summary

---

## API Endpoints

### Borrowing Transactions

```
POST   /backend/public/transactions-api.php?endpoint=borrow
GET    /backend/public/transactions-api.php?endpoint=borrowing-transactions
POST   /backend/public/transactions-api.php?endpoint=return-book
GET    /backend/public/transactions-api.php?endpoint=return-transactions
GET    /backend/public/transactions-api.php?endpoint=overdue-books
```

### Inventory Management

```
POST   /backend/public/transactions-api.php?endpoint=start-inventory-count
POST   /backend/public/transactions-api.php?endpoint=record-quantity
POST   /backend/public/transactions-api.php?endpoint=complete-inventory-count
POST   /backend/public/transactions-api.php?endpoint=verify-inventory-count
GET    /backend/public/transactions-api.php?endpoint=inventory-counts
GET    /backend/public/transactions-api.php?endpoint=inventory-count
GET    /backend/public/transactions-api.php?endpoint=inventory-discrepancies
```

### Reporting

```
GET    /backend/public/transactions-api.php?endpoint=borrowing-report
GET    /backend/public/transactions-api.php?endpoint=return-report
GET    /backend/public/transactions-api.php?endpoint=inventory-report
GET    /backend/public/transactions-api.php?endpoint=transaction-statistics
GET    /backend/public/transactions-api.php?endpoint=top-borrowed-books
GET    /backend/public/transactions-api.php?endpoint=top-borrowers
POST   /backend/public/transactions-api.php?endpoint=export-borrowing-excel
POST   /backend/public/transactions-api.php?endpoint=export-return-excel
POST   /backend/public/transactions-api.php?endpoint=export-inventory-excel
```

---

## Backend Services

### BorrowingService.php
Handles all borrowing and return operations:
- `createBorrowingTransaction()` - Create new loans
- `returnBook()` - Process returns with condition tracking
- `getBorrowingTransactions()` - Query loans with filters
- `getReturnTransactions()` - Query returns with filters
- `getOverdueBooks()` - Identify overdue items
- `getBookInventory()` - Check inventory levels

### InventoryService.php
Manages inventory count operations:
- `startInventoryCount()` - Initiate count session
- `recordPhysicalQuantity()` - Record scanned quantities
- `completeInventoryCount()` - Finish counting
- `verifyInventoryCount()` - Approve count
- `getInventoryCount()` - Retrieve count details
- `getInventoryDiscrepancies()` - Find mismatches

### ReportService.php
Generates comprehensive reports:
- `getBorrowingReport()` - Loan analysis
- `getReturnReport()` - Return analysis
- `getInventoryReport()` - Count analysis
- `getTransactionStatistics()` - Dashboard metrics
- `getTopBorrowedBooks()` - Popular items
- `getTopBorrowers()` - Active members

### ExcelExportService.php
Professional Excel report generation:
- `generateBorrowingReport()` - Create borrowing report
- `generateReturnReport()` - Create return report
- `generateInventoryReport()` - Create inventory report
- Includes headers, summaries, charts, and signatures

---

## Usage Examples

### Creating a Borrowing Transaction

```javascript
const payload = {
    bookId: 101,
    userId: 3,
    dueDays: 14,
    notes: "Regular borrowing",
    createdBy: 6
};

fetch('backend/public/transactions-api.php?endpoint=borrow', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
});
```

### Processing a Book Return

```javascript
const payload = {
    transactionId: 1,
    processedBy: 2,
    bookCondition: 'Good',
    notes: "Returned on time"
};

fetch('backend/public/transactions-api.php?endpoint=return-book', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
});
```

### Starting Inventory Count

```javascript
const payload = {
    conductedBy: 2,
    notes: "Monthly inventory check"
};

fetch('backend/public/transactions-api.php?endpoint=start-inventory-count', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
});
```

### Exporting Report to Excel

```javascript
const payload = {
    startDate: '2026-04-01',
    endDate: '2026-05-13',
    status: 'Active',
    generatedBy: 'Maria Santos'
};

fetch('backend/public/transactions-api.php?endpoint=export-borrowing-excel', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
});
```

---

## File Structure

```
activity 5 EDP/
├── borrowing-transactions.html        # Borrowing & return management
├── inventory-count.html                # Inventory count management
├── borrowing-report.html               # Borrowing report & export
├── return-report.html                  # Return report & export
├── inventory-report.html               # Inventory report & export
├── scripts/
│   ├── borrowing-transactions.js      # Borrowing transaction logic
│   ├── inventory-count.js             # Inventory count logic
│   └── (report scripts embedded)      # Report page logic
├── backend/
│   ├── src/
│   │   ├── BorrowingService.php       # Borrowing operations
│   │   ├── InventoryService.php       # Inventory operations
│   │   ├── ReportService.php          # Report generation
│   │   └── ExcelExportService.php     # Excel export
│   ├── public/
│   │   ├── transactions-api.php       # API endpoints
│   │   └── reports/                   # Generated Excel files
│   └── database/
│       ├── elibrary_system.sql        # Main schema
│       └── migrations/
│           └── 001_create_transactions_tables.sql
└── styles/
    └── shared.css                      # Shared styling
```

---

## Late Fee Calculation

- **Rate:** ₱50 per day
- **Applied:** Automatically when returning after due date
- **Example:** 5 days overdue = ₱250 fine

---

## Book Conditions

1. **Good** - Returned in acceptable condition, immediately available for borrowing
2. **Minor Damage** - Repairable damage, cleaned and returned to inventory
3. **Major Damage** - Significant damage, sent for repair or replacement
4. **Lost** - Not returned, marked as lost, inventory reduced

---

## Database Schema Relationships

```
user_elibrary
    ↓
    ├→ borrowing_transactions
    │   ├→ book
    │   └→ return_transactions
    │       └→ user_elibrary (ProcessedBy)
    │
    └→ inventory_counts
        ├→ inventory_count_details
        │   └→ book
        └→ book_inventory

transaction_logs
    ├→ borrowing_transactions
    ├→ return_transactions
    └→ inventory_counts
```

---

## Statistics & Analytics

### Dashboard Metrics
- Total borrowings (all time)
- Active borrowings (currently borrowed)
- Overdue borrowings (past due date)
- Returned books (completed loans)
- Total late fees collected
- Book condition distribution
- Inventory accuracy percentage

### Top Analytics
- Most borrowed books (by count)
- Most active borrowers (by count)
- Books with most returns
- Books with most damage
- Late payment patterns

---

## Security Features

✅ **Authentication Required** - API endpoints validate user session
✅ **User Type Validation** - Role-based access (Member/Librarian/Guest)
✅ **Transaction Logging** - All changes logged with user and timestamp
✅ **Data Validation** - Input validation on all endpoints
✅ **SQL Injection Prevention** - Prepared statements throughout
✅ **Error Handling** - Graceful error responses

---

## Testing the System

### Quick Test Workflow

1. **Add Borrowing**
   - Navigate to Borrowing Transactions
   - Click "+ New Borrowing"
   - Select book and member
   - Submit

2. **View Report**
   - Go to Borrowing Report
   - Select date range
   - View DataGrid with all borrowings

3. **Export to Excel**
   - Click "Export to Excel" button
   - Download report with charts
   - Open in Microsoft Excel

4. **Start Inventory**
   - Go to Inventory Count
   - Click "+ Start New Count"
   - Record physical quantities
   - Complete and verify count

---

## Troubleshooting

### Excel Export Not Working
- Ensure PhpSpreadsheet is installed: `composer require phpoffice/phpspreadsheet`
- Check reports directory exists and is writable
- Verify permissions: `chmod 755 public/reports`

### Reports Not Showing Data
- Verify database migration was run
- Check database connection in Config.php
- Ensure user has proper role (Librarian/Admin)

### Transaction API Errors
- Check that transactions-api.php is in correct path
- Verify database tables exist
- Test with sample curl command

### DataGrid Not Displaying
- Ensure jQuery is loaded before DataTables
- Check browser console for JavaScript errors
- Verify CSV/JSON data format

---

## Future Enhancements

- 📱 Mobile app for on-the-go borrowing
- 📧 Automated email notifications for overdue books
- 📊 Advanced analytics dashboard
- 🔔 SMS alerts for due dates
- 💳 Online fine payment integration
- 📚 Recommendation engine
- 🌐 RFID integration
- 🔄 Automated return location tracking

---

## Support & Documentation

For additional help:
- Check PHP error logs: `php_errors.log`
- Review database logs for SQL issues
- Test API endpoints with Postman
- Browser DevTools for JavaScript debugging

---

## License & Attribution

This module is part of the Metro City e-Library System v2.0
Developed for educational and production use

**Version:** 2.0
**Date:** May 13, 2026
**Author:** System Development Team
