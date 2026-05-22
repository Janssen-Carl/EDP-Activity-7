<?php

declare(strict_types=1);

namespace Backend;

use PDO;
use PDOException;

/**
 * InventoryService
 * Handles inventory count transactions and management
 */
final class InventoryService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /**
     * Start a new inventory count session
     */
    public function startInventoryCount(int $conductedByUserId, ?string $notes = null): array
    {
        try {
            $this->pdo->beginTransaction();

            // Get total expected books count
            $stmt = $this->pdo->prepare('
                SELECT SUM(TotalCopies) as TotalExpected FROM book_inventory
            ');
            $stmt->execute();
            $result = $stmt->fetch();
            $totalExpected = $result['TotalExpected'] ?? 0;

            // Create inventory count record
            $stmt = $this->pdo->prepare('
                INSERT INTO inventory_counts 
                (CountDate, CountStatus, ConductedBy, StartTime, TotalBooksExpected, Notes)
                VALUES (CURDATE(), "In Progress", :conductedBy, NOW(), :totalExpected, :notes)
            ');

            $stmt->execute([
                ':conductedBy' => $conductedByUserId,
                ':totalExpected' => $totalExpected,
                ':notes' => $notes ?? ''
            ]);

            $countId = (int) $this->pdo->lastInsertId();

            // Create detail records for each book
            $stmt = $this->pdo->prepare('
                INSERT INTO inventory_count_details (CountID, BookID, ExpectedQuantity, PhysicalQuantity)
                SELECT :countId, BookID, TotalCopies, 0
                FROM book_inventory
            ');
            $stmt->execute([':countId' => $countId]);

            $this->logInventoryAction('Create', $countId, $conductedByUserId, [
                'TotalBooksExpected' => $totalExpected
            ]);

            $this->pdo->commit();

            return [
                'success' => true,
                'countId' => $countId,
                'message' => 'Inventory count started successfully'
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error starting inventory count: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Record physical quantity for a book during inventory count
     */
    public function recordPhysicalQuantity(
        int $countId,
        int $bookId,
        int $physicalQuantity,
        ?string $notes = null
    ): array {
        try {
            // Update the detail record
            $stmt = $this->pdo->prepare('
                UPDATE inventory_count_details
                SET PhysicalQuantity = :quantity, Notes = :notes
                WHERE CountID = :countId AND BookID = :bookId
            ');

            $stmt->execute([
                ':countId' => $countId,
                ':bookId' => $bookId,
                ':quantity' => $physicalQuantity,
                ':notes' => $notes ?? ''
            ]);

            return [
                'success' => true,
                'message' => 'Physical quantity recorded'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error recording quantity: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Complete an inventory count session
     */
    public function completeInventoryCount(
        int $countId,
        int $conductedByUserId
    ): array {
        try {
            $this->pdo->beginTransaction();

            // Calculate totals
            $stmt = $this->pdo->prepare('
                SELECT 
                    SUM(ExpectedQuantity) as TotalExpected,
                    SUM(PhysicalQuantity) as TotalPhysical,
                    SUM(ABS(PhysicalQuantity - ExpectedQuantity)) as TotalDiscrepancies,
                    COUNT(*) as TotalItems
                FROM inventory_count_details
                WHERE CountID = :countId
            ');
            $stmt->execute([':countId' => $countId]);
            $totals = $stmt->fetch();

            // Update inventory count record
            $stmt = $this->pdo->prepare('
                UPDATE inventory_counts
                SET CountStatus = "Completed",
                    EndTime = NOW(),
                    CompletedAt = NOW(),
                    TotalBooksFound = :totalPhysical,
                    Discrepancies = :discrepancies
                WHERE CountID = :countId
            ');

            $stmt->execute([
                ':countId' => $countId,
                ':totalPhysical' => $totals['TotalPhysical'],
                ':discrepancies' => abs($totals['TotalExpected'] - $totals['TotalPhysical'])
            ]);

            $this->logInventoryAction('Complete', $countId, $conductedByUserId, [
                'TotalExpected' => $totals['TotalExpected'],
                'TotalFound' => $totals['TotalPhysical'],
                'Discrepancies' => abs($totals['TotalExpected'] - $totals['TotalPhysical'])
            ]);

            $this->pdo->commit();

            return [
                'success' => true,
                'totalExpected' => $totals['TotalExpected'],
                'totalFound' => $totals['TotalPhysical'],
                'discrepancies' => abs($totals['TotalExpected'] - $totals['TotalPhysical']),
                'message' => 'Inventory count completed successfully'
            ];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error completing inventory count: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify an inventory count
     */
    public function verifyInventoryCount(int $countId, int $verifiedByUserId): array
    {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE inventory_counts
                SET CountStatus = "Verified", VerifiedBy = :verifiedBy, VerifiedAt = NOW()
                WHERE CountID = :countId
            ');

            $stmt->execute([
                ':countId' => $countId,
                ':verifiedBy' => $verifiedByUserId
            ]);

            $this->logInventoryAction('Verify', $countId, $verifiedByUserId, [
                'Status' => 'Verified'
            ]);

            return [
                'success' => true,
                'message' => 'Inventory count verified'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error verifying inventory count: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get inventory count by ID with details
     */
    public function getInventoryCount(int $countId): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT ic.*, u1.FullName as ConductedByName, u2.FullName as VerifiedByName
                FROM inventory_counts ic
                LEFT JOIN user_elibrary u1 ON ic.ConductedBy = u1.User_ID
                LEFT JOIN user_elibrary u2 ON ic.VerifiedBy = u2.User_ID
                WHERE ic.CountID = :countId
            ');

            $stmt->execute([':countId' => $countId]);
            $count = $stmt->fetch();

            if (!$count) {
                return [
                    'success' => false,
                    'message' => 'Inventory count not found'
                ];
            }

            // Get details
            $stmt = $this->pdo->prepare('
                SELECT icd.*, b.BookTitle, b.ISBN
                FROM inventory_count_details icd
                JOIN book b ON icd.BookID = b.BookID
                WHERE icd.CountID = :countId
                ORDER BY b.BookTitle ASC
            ');

            $stmt->execute([':countId' => $countId]);
            $details = $stmt->fetchAll();

            return [
                'success' => true,
                'count' => $count,
                'details' => $details
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching inventory count: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all inventory counts
     */
    public function getAllInventoryCounts(?string $status = null, ?int $limit = null, ?int $offset = null): array
    {
        try {
            $query = '
                SELECT ic.*, u1.FullName as ConductedByName, u2.FullName as VerifiedByName
                FROM inventory_counts ic
                LEFT JOIN user_elibrary u1 ON ic.ConductedBy = u1.User_ID
                LEFT JOIN user_elibrary u2 ON ic.VerifiedBy = u2.User_ID
                WHERE 1=1
            ';

            $params = [];

            if ($status !== null) {
                $query .= ' AND ic.CountStatus = :status';
                $params[':status'] = $status;
            }

            $query .= ' ORDER BY ic.CountDate DESC, ic.CountID DESC';

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
                'message' => 'Error fetching inventory counts: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get inventory discrepancies
     */
    public function getInventoryDiscrepancies(?int $countId = null): array
    {
        try {
            $query = '
                SELECT icd.*, b.BookTitle, b.ISBN, ic.CountDate, ic.CountStatus
                FROM inventory_count_details icd
                JOIN book b ON icd.BookID = b.BookID
                JOIN inventory_counts ic ON icd.CountID = ic.CountID
                WHERE (icd.PhysicalQuantity - icd.ExpectedQuantity) != 0
            ';

            $params = [];

            if ($countId !== null) {
                $query .= ' AND icd.CountID = :countId';
                $params[':countId'] = $countId;
            }

            $query .= ' ORDER BY ic.CountDate DESC, ABS(icd.Variance) DESC';

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
                'message' => 'Error fetching discrepancies: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Helper: Log inventory actions
     */
    private function logInventoryAction(string $action, int $countId, int $userId, array $details): void
    {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO transaction_logs (TransactionType, RelatedTransactionID, Action, User_ID, ChangeDetails)
                VALUES ("Inventory", :countId, :action, :userId, :details)
            ');

            $stmt->execute([
                ':countId' => $countId,
                ':action' => $action,
                ':userId' => $userId,
                ':details' => json_encode($details)
            ]);
        } catch (PDOException $e) {
            error_log('Failed to log inventory action: ' . $e->getMessage());
        }
    }
}
