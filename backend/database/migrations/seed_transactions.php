<?php
require_once __DIR__ . '/../../src/Config.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/BorrowingService.php';
require_once __DIR__ . '/../../src/InventoryService.php';

Backend\Config::loadEnv(__DIR__ . '/../../.env');

try {
    $db = Backend\Database::connection();
    
    // Get members
    $stmt = $db->query("SELECT User_ID FROM user_elibrary WHERE UserType = 'Member' AND AccountStatus = 'Active'");
    $members = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($members)) {
        // Fallback
        $stmt = $db->query("SELECT User_ID FROM user_elibrary LIMIT 5");
        $members = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Get librarians
    $stmt = $db->query("SELECT User_ID FROM user_elibrary WHERE UserType = 'Librarian' AND AccountStatus = 'Active'");
    $librarians = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($librarians)) {
        // Fallback
        $librarians = $members;
    }
    
    // Get physical books
    $stmt = $db->query("SELECT BookID FROM physical_book LIMIT 20");
    $books = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($books)) {
        // Fallback
        $stmt = $db->query("SELECT BookID FROM book LIMIT 20");
        $books = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    if (empty($members) || empty($books)) {
        die("Need members and books to seed.\n");
    }
    
    $borrowingService = new Backend\BorrowingService();
    $inventoryService = new Backend\InventoryService();
    
    echo "Seeding borrowing transactions...\n";
    $statuses = ['Active', 'Returned', 'Returned', 'Returned', 'Overdue'];
    $conditions = ['Good', 'Good', 'Good', 'Minor Damage', 'Major Damage', 'Lost'];
    
    $db->exec("SET FOREIGN_KEY_CHECKS=0");
    $db->exec("TRUNCATE TABLE return_transactions");
    $db->exec("TRUNCATE TABLE borrowing_transactions");
    $db->exec("TRUNCATE TABLE inventory_count_details");
    $db->exec("TRUNCATE TABLE inventory_counts");
    $db->exec("SET FOREIGN_KEY_CHECKS=1");

    for ($i = 0; $i < 40; $i++) {
        $bookId = $books[array_rand($books)];
        $memberId = $members[array_rand($members)];
        $librarianId = $librarians[array_rand($librarians)];
        
        $status = $statuses[array_rand($statuses)];
        
        // Random days ago between 1 and 60
        $daysAgo = rand(1, 60);
        $borrowDate = new DateTime("-$daysAgo days");
        $borrowDateStr = $borrowDate->format('Y-m-d H:i:s');
        
        // Due date is usually 14 days after borrow
        $dueDate = clone $borrowDate;
        $dueDate->modify('+14 days');
        $dueDateStr = $dueDate->format('Y-m-d');
        
        $returnDateStr = null;
        if ($status === 'Returned') {
            $returnDaysAgo = rand(0, $daysAgo); // return happened sometime between borrow date and now
            $returnDate = new DateTime("-$returnDaysAgo days");
            // Ensure return date is after borrow date
            if ($returnDate < $borrowDate) {
                 $returnDate = clone $borrowDate;
                 $returnDate->modify('+2 days');
            }
            $returnDateStr = $returnDate->format('Y-m-d H:i:s');
        } elseif ($status === 'Overdue') {
            // Must be overdue, meaning due date is in the past
            if ($dueDate > new DateTime()) {
                $status = 'Active'; // Correction if not actually overdue
            }
        } elseif ($status === 'Active') {
            if ($dueDate < new DateTime()) {
                $status = 'Overdue'; // Correction
            }
        }
        
        $stmt = $db->prepare("INSERT INTO borrowing_transactions (BookID, User_ID, BorrowDate, DueDate, ReturnDate, Status, CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$bookId, $memberId, $borrowDateStr, $dueDateStr, $returnDateStr, $status, $librarianId]);
        $borrowTxId = $db->lastInsertId();
        
        // If returned, create return transaction
        if ($status === 'Returned') {
            $condition = $conditions[array_rand($conditions)];
            $returnDateObj = new DateTime($returnDateStr);
            
            $daysOverdue = 0;
            $lateFee = 0;
            if ($returnDateObj > clone $dueDate) {
                $interval = $returnDateObj->diff($dueDate);
                $daysOverdue = $interval->days;
                $lateFee = $daysOverdue * 10; // 10 pesos per day
            }
            
            // Adjust fee based on condition
            if ($condition === 'Minor Damage') $lateFee += 50;
            if ($condition === 'Major Damage') $lateFee += 200;
            if ($condition === 'Lost') $lateFee += 500;
            
            $stmt = $db->prepare("INSERT INTO return_transactions (BorrowingTransactionID, BookID, User_ID, ReturnDate, BookCondition, DaysOverdue, LateFee, ProcessedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$borrowTxId, $bookId, $memberId, $returnDateStr, $condition, $daysOverdue, $lateFee, $librarianId]);
        }
    }
    
    echo "Seeding inventory counts...\n";
    for ($i = 0; $i < 2; $i++) {
        $librarianId = $librarians[array_rand($librarians)];
        $daysAgo = rand(1, 30);
        $date = new DateTime("-$daysAgo days");
        
        $stmt = $db->prepare("INSERT INTO inventory_counts (CountDate, CountStatus, ConductedBy, StartTime, EndTime, TotalBooksExpected, TotalBooksFound, Discrepancies, Notes) VALUES (?, 'Completed', ?, ?, ?, ?, ?, ?, 'Routine check')");
        $stmt->execute([$date->format('Y-m-d'), $librarianId, $date->format('Y-m-d 08:00:00'), $date->format('Y-m-d 16:00:00'), 0, 0, 0]);
        $countId = $db->lastInsertId();
        
        $totalExp = 0;
        $totalFound = 0;
        $totalDisc = 0;
        
        foreach ($books as $index => $bookId) {
            if ($index > 10) continue; // Just check 10 books per count
            
            $expected = rand(2, 5);
            $found = $expected;
            if (rand(1, 10) > 8) { // 20% chance of discrepancy
                $found = $expected - rand(1, 2);
            }
            
            $variance = $found - $expected;
            
            $stmt = $db->prepare("INSERT INTO inventory_count_details (CountID, BookID, ExpectedQuantity, PhysicalQuantity) VALUES (?, ?, ?, ?)");
            $stmt->execute([$countId, $bookId, $expected, $found]);
            
            $totalExp += $expected;
            $totalFound += $found;
            if ($variance !== 0) $totalDisc++;
        }
        
        $db->exec("UPDATE inventory_counts SET TotalBooksExpected = $totalExp, TotalBooksFound = $totalFound, Discrepancies = $totalDisc WHERE CountID = $countId");
    }
    
    // Update book_inventory BorrowedCopies and AvailableCopies based on Active/Overdue borrows
    echo "Updating book_inventory...\n";
    $db->exec("UPDATE book_inventory bi SET 
        BorrowedCopies = (SELECT COUNT(*) FROM borrowing_transactions br WHERE br.BookID = bi.BookID AND br.Status IN ('Active', 'Overdue')),
        AvailableCopies = TotalCopies - (SELECT COUNT(*) FROM borrowing_transactions br WHERE br.BookID = bi.BookID AND br.Status IN ('Active', 'Overdue'))
    ");
    
    echo "Done seeding database.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
