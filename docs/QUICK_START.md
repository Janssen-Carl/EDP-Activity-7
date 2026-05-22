# Quick Start Guide - Transaction Module Installation

## 🚀 5-Minute Setup

### Prerequisites
- PHP 8.0+
- MySQL 8.0+
- Composer
- Web server (Apache/Nginx)

### Step 1: Install PhpSpreadsheet

```bash
cd activity\ 5\ EDP/backend
composer require phpoffice/phpspreadsheet
```

**✅ Expected Output:**
```
Using version ^1.29 for phpoffice/phpspreadsheet
./composer.json has been updated
...
```

### Step 2: Run Database Migration

```sql
-- Open MySQL Workbench or command line
mysql -u root -p elibrary_system < backend/database/migrations/001_create_transactions_tables.sql
```

**✅ Tables Created:**
- book_inventory
- borrowing_transactions
- return_transactions
- inventory_counts
- inventory_count_details
- transaction_logs

### Step 3: Create Reports Directory

```bash
mkdir -p backend/public/reports
chmod 755 backend/public/reports
```

### Step 4: Access the Pages

Open in your browser:
```
http://localhost/activity%205%20EDP/borrowing-transactions.html
http://localhost/activity%205%20EDP/inventory-count.html
http://localhost/activity%205%20EDP/borrowing-report.html
http://localhost/activity%205%20EDP/return-report.html
http://localhost/activity%205%20EDP/inventory-report.html
```

---

## 📋 What's Included

### Backend Services (3)
1. **BorrowingService.php** - Loan management
2. **InventoryService.php** - Inventory counts
3. **ReportService.php** - Report generation
4. **ExcelExportService.php** - Excel exports

### Frontend Pages (5)
1. **borrowing-transactions.html** - Manage loans & returns
2. **inventory-count.html** - Physical counts
3. **borrowing-report.html** - Loan analysis with export
4. **return-report.html** - Return analysis with export
5. **inventory-report.html** - Count analysis with export

### Database (6 new tables)
- book_inventory
- borrowing_transactions
- return_transactions
- inventory_counts
- inventory_count_details
- transaction_logs

### JavaScript (3)
- borrowing-transactions.js
- inventory-count.js
- Inline report scripts

---

## 🎯 First Actions

### 1. Create a Borrowing Transaction

1. Go to **Borrowing Transactions**
2. Click **+ New Borrowing**
3. Select a book (must have available copies)
4. Select a member
5. Click **Create Borrowing**

### 2. Return a Book

1. In **Borrowing Transactions**, find active borrowing
2. Click **Return Book**
3. Select book condition
4. Click **Process Return**

### 3. Start Inventory Count

1. Go to **Inventory Count**
2. Click **+ Start New Count**
3. Add notes if needed
4. Click **Start Count**
5. Record physical quantities
6. Click **Complete Count**

### 4. Generate Report

1. Go to **Borrowing Report**
2. Select date range
3. Click **Generate Report**
4. Click **Export to Excel**
5. Download Excel file with charts

---

## 🔍 Verification Checklist

- [ ] PhpSpreadsheet installed (`composer list`)
- [ ] Database migration ran successfully
- [ ] Reports directory exists and is writable
- [ ] Can create a borrowing transaction
- [ ] Can return a book
- [ ] Can generate a report
- [ ] Can export to Excel
- [ ] Excel file opens with charts

---

## 📊 Sample Data

The migration includes sample data:
- **Books:** 198 books across 50 categories
- **Users:** 6 users (1 Admin, 1 Librarian, 4 Members)
- **Inventory:** All books have initial inventory counts
- **Transactions:** 42 sample borrowing transactions

---

## 🆘 Common Issues & Fixes

### "Class not found: PhpOffice\PhpSpreadsheet"
**Solution:** Run `composer require phpoffice/phpspreadsheet`

### "Table already exists"
**Solution:** Drop tables first:
```sql
DROP TABLE IF EXISTS transaction_logs, inventory_count_details, 
inventory_counts, return_transactions, borrowing_transactions, book_inventory;
```

### "Permission denied" on reports directory
**Solution:** 
```bash
chmod 777 backend/public/reports
```

### Excel download redirects to empty page
**Solution:**
1. Check PHP error log
2. Verify reports directory has write permissions
3. Check that PhpSpreadsheet is properly installed

### "No data to export"
**Solution:**
1. Generate report first
2. Wait for table to populate
3. Select valid date range
4. Check that database has transactions

---

## 📚 Key Features Summary

| Feature | Status | Location |
|---------|--------|----------|
| Book Borrowing | ✅ Complete | borrowing-transactions.html |
| Book Returns | ✅ Complete | borrowing-transactions.html |
| Inventory Counts | ✅ Complete | inventory-count.html |
| Borrowing Reports | ✅ Complete | borrowing-report.html |
| Return Reports | ✅ Complete | return-report.html |
| Inventory Reports | ✅ Complete | inventory-report.html |
| Excel Export | ✅ Complete | All report pages |
| DataGrid Display | ✅ Complete | All report pages |
| Charts (Sheet 2) | ✅ Complete | Excel exports |
| Signatures | ✅ Complete | Excel reports |
| Statistics Dashboard | ✅ Complete | All transaction pages |

---

## 🎓 Learning Resources

### Understanding the Flow

**Borrowing Flow:**
```
User → New Borrowing → DB Insert → Inventory ↓
                                        ↓
                          Return → Update Status → Inventory ↑
                                        ↓
                          Report → Analytics → Excel
```

**Inventory Flow:**
```
Start Count → Record Quantities → Calculate Variance → Complete Count → Verify
                                                              ↓
                                              Report → Analytics → Excel
```

---

## 💡 Best Practices

1. **Always verify inventory** before completing count
2. **Check late fees** when returning overdue books
3. **Export reports monthly** for archival
4. **Review discrepancies** immediately after count
5. **Monitor overdue books** weekly
6. **Update book conditions** accurately for preservation

---

## 🔐 Security Notes

- ✅ All endpoints require user authentication
- ✅ Role-based access control enforced
- ✅ All database queries use prepared statements
- ✅ All transactions logged for audit trail
- ✅ Input validation on all forms
- ✅ XSS and SQL injection prevention enabled

---

## 📞 Support

For issues or questions:
1. Check the TRANSACTIONS_MODULE_README.md
2. Review backend/src/ PHP files for documentation
3. Check browser console for JavaScript errors
4. Review MySQL error logs for database issues
5. Test API endpoints with Postman

---

## ✨ Next Steps

1. ✅ Complete the quick start
2. ✅ Test all transaction types
3. ✅ Generate sample reports
4. ✅ Review Excel exports
5. ✅ Create custom dashboard
6. ✅ Set up automated tasks
7. ✅ Integrate with user management
8. ✅ Deploy to production

---

**Enjoy the new Transaction Module! 🎉**
