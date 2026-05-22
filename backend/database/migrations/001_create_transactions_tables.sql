-- Migration: Create Transaction Tables for Book Borrowing, Returns, and Inventory Counts
-- Date: 2026-05-13
-- Purpose: Support the three primary transactions in the e-Library system

-- ========================================
-- Table: book_inventory
-- Purpose: Track current inventory levels for each book
-- ========================================
CREATE TABLE IF NOT EXISTS `book_inventory` (
  `InventoryID` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `BookID` int NOT NULL,
  `TotalCopies` int NOT NULL DEFAULT 0 COMMENT 'Total number of copies in library',
  `AvailableCopies` int NOT NULL DEFAULT 0 COMMENT 'Currently available for borrowing',
  `BorrowedCopies` int NOT NULL DEFAULT 0 COMMENT 'Currently borrowed by members',
  `DamagedCopies` int NOT NULL DEFAULT 0 COMMENT 'Damaged and unavailable',
  `LastUpdated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_book_inventory` (`BookID`),
  FOREIGN KEY (`BookID`) REFERENCES `book` (`BookID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: borrowing_transactions
-- Purpose: Record all book borrowing transactions with enhanced details
-- ========================================
CREATE TABLE IF NOT EXISTS `borrowing_transactions` (
  `TransactionID` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `BookID` int NOT NULL,
  `User_ID` int NOT NULL,
  `BorrowDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `DueDate` date NOT NULL COMMENT 'Expected return date',
  `ReturnDate` date NULL COMMENT 'Actual return date',
  `Status` varchar(20) NOT NULL DEFAULT 'Active' COMMENT 'Active, Overdue, Returned, Cancelled',
  `Fine` decimal(10, 2) DEFAULT 0 COMMENT 'Late fees if applicable',
  `Notes` varchar(255) NULL,
  `CreatedBy` int DEFAULT NULL,
  `UpdatedBy` int DEFAULT NULL,
  `UpdatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_user_id` (`User_ID`),
  KEY `idx_book_id` (`BookID`),
  KEY `idx_status` (`Status`),
  KEY `idx_borrow_date` (`BorrowDate`),
  FOREIGN KEY (`BookID`) REFERENCES `book` (`BookID`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`User_ID`) REFERENCES `user_elibrary` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`CreatedBy`) REFERENCES `user_elibrary` (`User_ID`) ON DELETE SET NULL,
  FOREIGN KEY (`UpdatedBy`) REFERENCES `user_elibrary` (`User_ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: return_transactions
-- Purpose: Track book return transactions (linked to borrowing transactions)
-- ========================================
CREATE TABLE IF NOT EXISTS `return_transactions` (
  `ReturnTransactionID` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `BorrowingTransactionID` int NOT NULL,
  `BookID` int NOT NULL,
  `User_ID` int NOT NULL,
  `ReturnDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `BookCondition` varchar(20) NOT NULL DEFAULT 'Good' COMMENT 'Good, Minor Damage, Major Damage, Lost',
  `DaysOverdue` int DEFAULT 0,
  `LateFee` decimal(10, 2) DEFAULT 0,
  `ProcessedBy` int NOT NULL COMMENT 'Librarian who processed the return',
  `Notes` varchar(255) NULL,
  KEY `idx_borrowing_transaction` (`BorrowingTransactionID`),
  KEY `idx_user_id` (`User_ID`),
  KEY `idx_return_date` (`ReturnDate`),
  FOREIGN KEY (`BorrowingTransactionID`) REFERENCES `borrowing_transactions` (`TransactionID`) ON DELETE CASCADE,
  FOREIGN KEY (`BookID`) REFERENCES `book` (`BookID`) ON DELETE CASCADE,
  FOREIGN KEY (`User_ID`) REFERENCES `user_elibrary` (`User_ID`) ON DELETE CASCADE,
  FOREIGN KEY (`ProcessedBy`) REFERENCES `user_elibrary` (`User_ID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: inventory_counts
-- Purpose: Track periodic physical inventory counts
-- ========================================
CREATE TABLE IF NOT EXISTS `inventory_counts` (
  `CountID` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `CountDate` date NOT NULL COMMENT 'Date of inventory count',
  `CountStatus` varchar(20) NOT NULL DEFAULT 'In Progress' COMMENT 'In Progress, Completed, Verified',
  `ConductedBy` int NOT NULL COMMENT 'Librarian conducting the count',
  `VerifiedBy` int NULL COMMENT 'Librarian who verified the count',
  `StartTime` datetime NOT NULL,
  `EndTime` datetime NULL,
  `TotalBooksExpected` int NOT NULL DEFAULT 0,
  `TotalBooksFound` int NOT NULL DEFAULT 0,
  `Discrepancies` int DEFAULT 0 COMMENT 'Books found vs expected difference',
  `Notes` varchar(500) NULL,
  `CompletedAt` datetime NULL,
  `VerifiedAt` datetime NULL,
  KEY `idx_count_date` (`CountDate`),
  KEY `idx_status` (`CountStatus`),
  FOREIGN KEY (`ConductedBy`) REFERENCES `user_elibrary` (`User_ID`) ON DELETE RESTRICT,
  FOREIGN KEY (`VerifiedBy`) REFERENCES `user_elibrary` (`User_ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: inventory_count_details
-- Purpose: Detail line items for each inventory count
-- ========================================
CREATE TABLE IF NOT EXISTS `inventory_count_details` (
  `DetailID` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `CountID` int NOT NULL,
  `BookID` int NOT NULL,
  `LocationID` int NULL COMMENT 'Shelf location',
  `ExpectedQuantity` int NOT NULL DEFAULT 0,
  `PhysicalQuantity` int NOT NULL DEFAULT 0,
  `Variance` int GENERATED ALWAYS AS (PhysicalQuantity - ExpectedQuantity) STORED,
  `Notes` varchar(255) NULL,
  KEY `idx_count_id` (`CountID`),
  KEY `idx_book_id` (`BookID`),
  FOREIGN KEY (`CountID`) REFERENCES `inventory_counts` (`CountID`) ON DELETE CASCADE,
  FOREIGN KEY (`BookID`) REFERENCES `book` (`BookID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: transaction_logs
-- Purpose: Audit trail for all transaction-related activities
-- ========================================
CREATE TABLE IF NOT EXISTS `transaction_logs` (
  `LogID` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `TransactionType` varchar(30) NOT NULL COMMENT 'Borrowing, Return, Inventory',
  `RelatedTransactionID` int NULL COMMENT 'Reference to primary transaction ID',
  `Action` varchar(50) NOT NULL COMMENT 'Create, Update, Delete, Approve, Reject',
  `User_ID` int NOT NULL COMMENT 'User who performed the action',
  `ChangeDetails` json NULL COMMENT 'JSON object storing what changed',
  `IPAddress` varchar(50) NULL,
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_transaction_type` (`TransactionType`),
  KEY `idx_related_transaction` (`RelatedTransactionID`),
  KEY `idx_user_id` (`User_ID`),
  KEY `idx_created_at` (`CreatedAt`),
  FOREIGN KEY (`User_ID`) REFERENCES `user_elibrary` (`User_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


