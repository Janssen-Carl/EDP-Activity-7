<?php

declare(strict_types=1);

namespace Backend;

use PDO;
use PDOException;

/**
 * BorrowingService
 * Handles all borrowing and return transaction operations
 */
final class BorrowingService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /**
     * Create a new borrowing transaction
     */
    public function createBorrowingTransaction(
        int $bookId,
        int $userId,
        int $dueDays = 14,
        ?string $notes = null,
        int $createdByUserId = 0
    ): array {
        try {
            $this->pdo->beginTransaction();

            // Insert borrowing transaction
            $stmt = $this->pdo->prepare('
                INSERT INTO borrowing_transactions 
                (BookID, User_ID, BorrowDate, DueDate, Status, Notes, CreatedBy)
                VALUES (:bookId, :userId, NOW(), DATE_ADD(NOW(), INTERVAL :dueDays DAY), :status, :notes, :createdBy)
            ');

            $status = 'Active';
            $stmt->execute([
                ':bookId' => $bookId,
                ':userId' => $userId,
                ':dueDays' => $dueDays,
                ':status' => $status,
                ':notes' => $notes ?? '',
                ':createdBy' => $createdByUserId
            ]);

            $transactionId = (int) $this->pdo->lastInsertId();

            // Update inventory - decrease available copies
            $this->decrementAvailableCopies($bookId);

            // Log the transaction
            $this->logTransaction('Borrowing', $transactionId, 'Create', $createdByUserId, [
                'BookID' => $bookId,
                'UserID' => $userId,
                'DueDays' => $dueDays
            ]);

            $this->pdo->commit();

            return [
                'success' => true,
                'transactionId' => $transactionId,
                'message' => 'Book borrowed successfully'
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error creating borrowing transaction: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Return a borrowed book
     */
    public function returnBook(
        int $transactionId,
        int $processedByUserId,
        string $bookCondition = 'Good',
        ?string $notes = null
    ): array {
        try {
            $this->pdo->beginTransaction();

            // Get the original borrowing transaction
            $stmt = $this->pdo->prepare('
                SELECT bt.*, b.BookTitle
                FROM borrowing_transactions bt
                JOIN book b ON bt.BookID = b.BookID
                WHERE bt.TransactionID = :transactionId AND bt.Status IN ("Active", "Overdue")
            ');
            $stmt->execute([':transactionId' => $transactionId]);
            $borrowing = $stmt->fetch();

            if (!$borrowing) {
                throw new PDOException('Borrowing transaction not found or already returned');
            }

            // Calculate late fees if any
            $today = new \DateTime();
            $dueDate = new \DateTime($borrowing['DueDate']);
            $daysOverdue = 0;
            $lateFee = 0;

            if ($today > $dueDate) {
                $daysOverdue = $today->diff($dueDate)->days;
                $lateFee = $daysOverdue * 50; // 50 pesos per day
            }

            // Create return transaction
            $stmt = $this->pdo->prepare('
                INSERT INTO return_transactions 
                (BorrowingTransactionID, BookID, User_ID, ReturnDate, BookCondition, DaysOverdue, LateFee, ProcessedBy, Notes)
                VALUES (:borrowingId, :bookId, :userId, NOW(), :condition, :daysOverdue, :lateFee, :processedBy, :notes)
            ');

            $stmt->execute([
                ':borrowingId' => $transactionId,
                ':bookId' => $borrowing['BookID'],
                ':userId' => $borrowing['User_ID'],
                ':condition' => $bookCondition,
                ':daysOverdue' => $daysOverdue,
                ':lateFee' => $lateFee,
                ':processedBy' => $processedByUserId,
                ':notes' => $notes ?? ''
            ]);

            // Update borrowing transaction status
            $stmt = $this->pdo->prepare('
                UPDATE borrowing_transactions 
                SET Status = "Returned", ReturnDate = NOW(), UpdatedBy = :updatedBy
                WHERE TransactionID = :transactionId
            ');
            $stmt->execute([
                ':transactionId' => $transactionId,
                ':updatedBy' => $processedByUserId
            ]);

            // Update inventory - increase available copies
            $this->incrementAvailableCopies($borrowing['BookID'], $bookCondition);

            // Log the transaction
            $this->logTransaction('Return', $transactionId, 'Create', $processedByUserId, [
                'BookID' => $borrowing['BookID'],
                'UserID' => $borrowing['User_ID'],
                'BookCondition' => $bookCondition,
                'LateFee' => $lateFee
            ]);

            $this->pdo->commit();

            return [
                'success' => true,
                'returnTransactionId' => $this->pdo->lastInsertId(),
                'bookCondition' => $bookCondition,
                'daysOverdue' => $daysOverdue,
                'lateFee' => $lateFee,
                'message' => 'Book returned successfully'
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error returning book: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all borrowing transactions with optional filters
     */
    public function getBorrowingTransactions(
        ?int $userId = null,
        ?string $status = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        try {
            $query = '
                SELECT bt.*, b.BookTitle, b.ISBN, u.FullName as MemberName, u.Email as MemberEmail
                FROM borrowing_transactions bt
                JOIN book b ON bt.BookID = b.BookID
                JOIN user_elibrary u ON bt.User_ID = u.User_ID
                WHERE 1=1
            ';

            $params = [];

            if ($userId !== null) {
                $query .= ' AND bt.User_ID = :userId';
                $params[':userId'] = $userId;
            }

            if ($status !== null) {
                $query .= ' AND bt.Status = :status';
                $params[':status'] = $status;
            }

            $query .= ' ORDER BY bt.BorrowDate DESC';

            if ($limit !== null) {
                $query .= ' LIMIT :limit';
                if ($offset !== null) {
                    $query .= ' OFFSET :offset';
                    $params[':offset'] = $offset;
                }
                $params[':limit'] = $limit;
            }

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            return [
                'success' => true,
                'data' => $stmt->fetchAll(),
                'count' => $stmt->rowCount()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching borrowing transactions: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all return transactions with optional filters
     */
    public function getReturnTransactions(
        ?int $userId = null,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        try {
            $query = '
                SELECT rt.*, b.BookTitle, b.ISBN, u.FullName as MemberName, u.Email as MemberEmail,
                       lib.FullName as ProcessedByName
                FROM return_transactions rt
                JOIN book b ON rt.BookID = b.BookID
                JOIN user_elibrary u ON rt.User_ID = u.User_ID
                JOIN user_elibrary lib ON rt.ProcessedBy = lib.User_ID
                WHERE 1=1
            ';

            $params = [];

            if ($userId !== null) {
                $query .= ' AND rt.User_ID = :userId';
                $params[':userId'] = $userId;
            }

            if ($startDate !== null) {
                $query .= ' AND rt.ReturnDate >= :startDate';
                $params[':startDate'] = $startDate->format('Y-m-d H:i:s');
            }

            if ($endDate !== null) {
                $query .= ' AND rt.ReturnDate <= :endDate';
                $params[':endDate'] = $endDate->format('Y-m-d H:i:s');
            }

            $query .= ' ORDER BY rt.ReturnDate DESC';

            if ($limit !== null) {
                $query .= ' LIMIT :limit';
                if ($offset !== null) {
                    $query .= ' OFFSET :offset';
                    $params[':offset'] = $offset;
                }
                $params[':limit'] = $limit;
            }

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            return [
                'success' => true,
                'data' => $stmt->fetchAll(),
                'count' => $stmt->rowCount()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching return transactions: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get overdue books
     */
    public function getOverdueBooks(): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT bt.*, b.BookTitle, b.ISBN, u.FullName, u.Email,
                       DATEDIFF(NOW(), bt.DueDate) as DaysOverdue,
                       DATEDIFF(NOW(), bt.DueDate) * 50 as EstimatedFine
                FROM borrowing_transactions bt
                JOIN book b ON bt.BookID = b.BookID
                JOIN user_elibrary u ON bt.User_ID = u.User_ID
                WHERE bt.Status IN ("Active", "Overdue") AND bt.DueDate < NOW()
                ORDER BY bt.DueDate ASC
            ');

            $stmt->execute();

            return [
                'success' => true,
                'data' => $stmt->fetchAll(),
                'count' => $stmt->rowCount()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching overdue books: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get book inventory status
     */
    public function getBookInventory(int $bookId): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT bi.*, b.BookTitle, b.ISBN, b.Author
                FROM book_inventory bi
                JOIN book b ON bi.BookID = b.BookID
                WHERE bi.BookID = :bookId
            ');

            $stmt->execute([':bookId' => $bookId]);
            $inventory = $stmt->fetch();

            if (!$inventory) {
                return [
                    'success' => false,
                    'message' => 'Book inventory not found'
                ];
            }

            return [
                'success' => true,
                'data' => $inventory
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching book inventory: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all book inventory
     */
    public function getAllInventory(?int $limit = null, ?int $offset = null): array
    {
        try {
            $query = '
                SELECT bi.*, b.BookTitle, b.ISBN, b.Author, c.Category_Name
                FROM book_inventory bi
                JOIN book b ON bi.BookID = b.BookID
                LEFT JOIN category c ON b.CategoryID = c.CategoryID
                ORDER BY b.BookTitle ASC
            ';

            $params = [];

            if ($limit !== null) {
                $query .= ' LIMIT :limit';
                if ($offset !== null) {
                    $query .= ' OFFSET :offset';
                    $params[':offset'] = $offset;
                }
                $params[':limit'] = $limit;
            }

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            return [
                'success' => true,
                'data' => $stmt->fetchAll(),
                'count' => $stmt->rowCount()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching inventory: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Helper: Decrement available copies when book is borrowed
     */
    private function decrementAvailableCopies(int $bookId): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE book_inventory
            SET AvailableCopies = AvailableCopies - 1,
                BorrowedCopies = BorrowedCopies + 1
            WHERE BookID = :bookId AND AvailableCopies > 0
        ');
        $stmt->execute([':bookId' => $bookId]);
    }

    /**
     * Helper: Increment available copies when book is returned
     */
    private function incrementAvailableCopies(int $bookId, string $condition = 'Good'): void
    {
        if ($condition === 'Good' || $condition === 'Minor Damage') {
            $stmt = $this->pdo->prepare('
                UPDATE book_inventory
                SET AvailableCopies = AvailableCopies + 1,
                    BorrowedCopies = BorrowedCopies - 1
                WHERE BookID = :bookId
            ');
        } else {
            $stmt = $this->pdo->prepare('
                UPDATE book_inventory
                SET BorrowedCopies = BorrowedCopies - 1,
                    DamagedCopies = DamagedCopies + 1
                WHERE BookID = :bookId
            ');
        }

        $stmt->execute([':bookId' => $bookId]);
    }

    /**
     * Helper: Log transaction activity
     */
    private function logTransaction(string $type, int $relatedId, string $action, int $userId, array $details): void
    {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO transaction_logs (TransactionType, RelatedTransactionID, Action, User_ID, ChangeDetails)
                VALUES (:type, :relatedId, :action, :userId, :details)
            ');

            $stmt->execute([
                ':type' => $type,
                ':relatedId' => $relatedId,
                ':action' => $action,
                ':userId' => $userId,
                ':details' => json_encode($details)
            ]);
        } catch (PDOException $e) {
            // Log silently to not disrupt main operation
            error_log('Failed to log transaction: ' . $e->getMessage());
        }
    }
}
