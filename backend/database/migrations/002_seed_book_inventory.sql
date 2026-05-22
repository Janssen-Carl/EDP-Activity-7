-- Migration: Seed book_inventory from existing physical_book data
-- Date: 2026-05-15
-- Purpose: Populate book_inventory table so borrowing transactions can work

-- Insert inventory records for all physical books
INSERT IGNORE INTO `book_inventory` (`BookID`, `TotalCopies`, `AvailableCopies`, `BorrowedCopies`, `DamagedCopies`)
SELECT 
    pb.BookID,
    pb.Copies as TotalCopies,
    pb.Copies as AvailableCopies,
    0 as BorrowedCopies,
    0 as DamagedCopies
FROM `physical_book` pb;

-- Also add digital books with 999 copies (unlimited digital access)
INSERT IGNORE INTO `book_inventory` (`BookID`, `TotalCopies`, `AvailableCopies`, `BorrowedCopies`, `DamagedCopies`)
SELECT 
    db.BookID,
    999 as TotalCopies,
    999 as AvailableCopies,
    0 as BorrowedCopies,
    0 as DamagedCopies
FROM `digital_book` db
WHERE db.BookID NOT IN (SELECT BookID FROM `book_inventory`);

-- Update borrowed counts based on existing active borrowing_record entries
UPDATE `book_inventory` bi
SET 
    bi.BorrowedCopies = (
        SELECT COUNT(*) 
        FROM `borrowing_record` br 
        WHERE br.BookID = bi.BookID AND br.Status = 'Borrowed'
    ),
    bi.AvailableCopies = bi.TotalCopies - (
        SELECT COUNT(*) 
        FROM `borrowing_record` br 
        WHERE br.BookID = bi.BookID AND br.Status = 'Borrowed'
    );
