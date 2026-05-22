<?php

declare(strict_types=1);

namespace Backend;

use PDO;
use PDOException;

/**
 * ReportService
 * Generates various reports from transaction data
 */
final class ReportService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    /**
     * Get borrowing report data
     */
    public function getBorrowingReport(
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?string $status = null
    ): array {
        try {
            $query = '
                SELECT bt.*, b.BookTitle, b.ISBN, b.Author, c.Category_Name,
                       u.FullName as MemberName, u.Email as MemberEmail,
                       DATEDIFF(NOW(), bt.DueDate) as DaysOverdue,
                       CASE WHEN bt.DueDate < NOW() AND bt.Status IN ("Active", "Overdue") 
                            THEN DATEDIFF(NOW(), bt.DueDate) * 50 ELSE 0 END as EstimatedFine
                FROM borrowing_transactions bt
                JOIN book b ON bt.BookID = b.BookID
                LEFT JOIN category c ON b.CategoryID = c.CategoryID
                JOIN user_elibrary u ON bt.User_ID = u.User_ID
                WHERE 1=1
            ';

            $params = [];

            if ($startDate !== null) {
                $query .= ' AND DATE(bt.BorrowDate) >= :startDate';
                $params[':startDate'] = $startDate->format('Y-m-d');
            }

            if ($endDate !== null) {
                $query .= ' AND DATE(bt.BorrowDate) <= :endDate';
                $params[':endDate'] = $endDate->format('Y-m-d');
            }

            if ($status !== null) {
                $query .= ' AND bt.Status = :status';
                $params[':status'] = $status;
            }

            $query .= ' ORDER BY bt.BorrowDate DESC';

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            $records = $stmt->fetchAll();

            return [
                'success' => true,
                'data' => $records,
                'count' => count($records),
                'summary' => $this->calculateBorrowingSummary($records)
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching borrowing report: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get return report data
     */
    public function getReturnReport(
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): array {
        try {
            $query = '
                SELECT rt.*, b.BookTitle, b.ISBN, b.Author,
                       u.FullName as MemberName, u.Email as MemberEmail,
                       lib.FullName as LibrarianName,
                       bt.BorrowDate
                FROM return_transactions rt
                JOIN book b ON rt.BookID = b.BookID
                JOIN user_elibrary u ON rt.User_ID = u.User_ID
                JOIN user_elibrary lib ON rt.ProcessedBy = lib.User_ID
                JOIN borrowing_transactions bt ON rt.BorrowingTransactionID = bt.TransactionID
                WHERE 1=1
            ';

            $params = [];

            if ($startDate !== null) {
                $query .= ' AND DATE(rt.ReturnDate) >= :startDate';
                $params[':startDate'] = $startDate->format('Y-m-d');
            }

            if ($endDate !== null) {
                $query .= ' AND DATE(rt.ReturnDate) <= :endDate';
                $params[':endDate'] = $endDate->format('Y-m-d');
            }

            $query .= ' ORDER BY rt.ReturnDate DESC';

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            $records = $stmt->fetchAll();

            return [
                'success' => true,
                'data' => $records,
                'count' => count($records),
                'summary' => $this->calculateReturnSummary($records)
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching return report: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get inventory report data
     */
    public function getInventoryReport(?int $countId = null): array
    {
        try {
            if ($countId !== null) {
                // Get specific inventory count
                $stmt = $this->pdo->prepare('
                    SELECT icd.*, b.BookTitle, b.ISBN, b.Author, c.Category_Name
                    FROM inventory_count_details icd
                    JOIN book b ON icd.BookID = b.BookID
                    LEFT JOIN category c ON b.CategoryID = c.CategoryID
                    WHERE icd.CountID = :countId
                    ORDER BY b.BookTitle ASC
                ');
                $stmt->execute([':countId' => $countId]);
                $records = $stmt->fetchAll();

                // Get count header info
                $stmt = $this->pdo->prepare('
                    SELECT ic.*, u1.FullName as ConductedByName, u2.FullName as VerifiedByName
                    FROM inventory_counts ic
                    LEFT JOIN user_elibrary u1 ON ic.ConductedBy = u1.User_ID
                    LEFT JOIN user_elibrary u2 ON ic.VerifiedBy = u2.User_ID
                    WHERE ic.CountID = :countId
                ');
                $stmt->execute([':countId' => $countId]);
                $header = $stmt->fetch();

                return [
                    'success' => true,
                    'header' => $header,
                    'data' => $records,
                    'count' => count($records),
                    'summary' => $this->calculateInventorySummary($records)
                ];
            } else {
                // Get all recent inventory counts
                $stmt = $this->pdo->prepare('
                    SELECT ic.*, u1.FullName as ConductedByName, u2.FullName as VerifiedByName
                    FROM inventory_counts ic
                    LEFT JOIN user_elibrary u1 ON ic.ConductedBy = u1.User_ID
                    LEFT JOIN user_elibrary u2 ON ic.VerifiedBy = u2.User_ID
                    ORDER BY ic.CountDate DESC
                    LIMIT 10
                ');
                $stmt->execute();

                return [
                    'success' => true,
                    'data' => $stmt->fetchAll()
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching inventory report: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get statistics for dashboard
     */
    public function getTransactionStatistics(): array
    {
        try {
            // Borrowing stats
            $stmt = $this->pdo->prepare('
                SELECT 
                    COUNT(*) as TotalBorrowings,
                    SUM(CASE WHEN Status = "Active" THEN 1 ELSE 0 END) as ActiveBorrowings,
                    SUM(CASE WHEN Status = "Overdue" THEN 1 ELSE 0 END) as OverdueBorrowings,
                    SUM(CASE WHEN Status = "Returned" THEN 1 ELSE 0 END) as ReturnedBorrowings
                FROM borrowing_transactions
            ');
            $stmt->execute();
            $borrowingStats = $stmt->fetch();

            // Return stats
            $stmt = $this->pdo->prepare('
                SELECT 
                    COUNT(*) as TotalReturns,
                    SUM(CASE WHEN BookCondition = "Good" THEN 1 ELSE 0 END) as GoodCondition,
                    SUM(CASE WHEN BookCondition = "Minor Damage" THEN 1 ELSE 0 END) as MinorDamage,
                    SUM(CASE WHEN BookCondition = "Major Damage" THEN 1 ELSE 0 END) as MajorDamage,
                    SUM(CASE WHEN BookCondition = "Lost" THEN 1 ELSE 0 END) as Lost,
                    SUM(LateFee) as TotalLateFees
                FROM return_transactions
            ');
            $stmt->execute();
            $returnStats = $stmt->fetch();

            // Inventory stats
            $stmt = $this->pdo->prepare('
                SELECT 
                    COUNT(*) as TotalCountSessions,
                    SUM(CASE WHEN CountStatus = "In Progress" THEN 1 ELSE 0 END) as InProgressCounts,
                    SUM(CASE WHEN CountStatus = "Completed" THEN 1 ELSE 0 END) as CompletedCounts,
                    SUM(CASE WHEN CountStatus = "Verified" THEN 1 ELSE 0 END) as VerifiedCounts,
                    SUM(Discrepancies) as TotalDiscrepancies
                FROM inventory_counts
            ');
            $stmt->execute();
            $inventoryStats = $stmt->fetch();

            // Overall inventory
            $stmt = $this->pdo->prepare('
                SELECT 
                    SUM(TotalCopies) as TotalInventory,
                    SUM(AvailableCopies) as AvailableCopies,
                    SUM(BorrowedCopies) as BorrowedCopies,
                    SUM(DamagedCopies) as DamagedCopies
                FROM book_inventory
            ');
            $stmt->execute();
            $inventoryTotal = $stmt->fetch();

            return [
                'success' => true,
                'borrowing' => $borrowingStats,
                'returns' => $returnStats,
                'inventory' => $inventoryStats,
                'inventoryTotal' => $inventoryTotal
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching statistics: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get top borrowed books
     */
    public function getTopBorrowedBooks(int $limit = 10): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT b.BookID, b.BookTitle, b.ISBN, b.Author, c.Category_Name,
                       COUNT(bt.TransactionID) as BorrowCount,
                       COUNT(DISTINCT bt.User_ID) as UniqueBorrowers
                FROM book b
                LEFT JOIN borrowing_transactions bt ON b.BookID = bt.BookID
                LEFT JOIN category c ON b.CategoryID = c.CategoryID
                GROUP BY b.BookID
                ORDER BY BorrowCount DESC
                LIMIT :limit
            ');
            $stmt->execute([':limit' => $limit]);

            return [
                'success' => true,
                'data' => $stmt->fetchAll()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching top borrowed books: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get top borrowers
     */
    public function getTopBorrowers(int $limit = 10): array
    {
        try {
            $stmt = $this->pdo->prepare('
                SELECT u.User_ID, u.FullName, u.Email,
                       COUNT(bt.TransactionID) as BorrowCount,
                       COUNT(CASE WHEN bt.Status = "Overdue" THEN 1 END) as OverdueCount,
                       SUM(CASE WHEN rt.LateFee > 0 THEN rt.LateFee ELSE 0 END) as TotalFines
                FROM user_elibrary u
                LEFT JOIN borrowing_transactions bt ON u.User_ID = bt.User_ID
                LEFT JOIN return_transactions rt ON bt.TransactionID = rt.BorrowingTransactionID
                WHERE u.UserType = "Member"
                GROUP BY u.User_ID
                ORDER BY BorrowCount DESC
                LIMIT :limit
            ');
            $stmt->execute([':limit' => $limit]);

            return [
                'success' => true,
                'data' => $stmt->fetchAll()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching top borrowers: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate borrowing report summary
     */
    private function calculateBorrowingSummary(array $records): array
    {
        $summary = [
            'total' => count($records),
            'active' => 0,
            'overdue' => 0,
            'returned' => 0,
            'totalEstimatedFines' => 0
        ];

        foreach ($records as $record) {
            switch ($record['Status']) {
                case 'Active':
                    $summary['active']++;
                    break;
                case 'Overdue':
                    $summary['overdue']++;
                    break;
                case 'Returned':
                    $summary['returned']++;
                    break;
            }
            $summary['totalEstimatedFines'] += $record['EstimatedFine'] ?? 0;
        }

        return $summary;
    }

    /**
     * Calculate return report summary
     */
    private function calculateReturnSummary(array $records): array
    {
        $summary = [
            'total' => count($records),
            'good' => 0,
            'minorDamage' => 0,
            'majorDamage' => 0,
            'lost' => 0,
            'totalLateFees' => 0
        ];

        foreach ($records as $record) {
            switch ($record['BookCondition']) {
                case 'Good':
                    $summary['good']++;
                    break;
                case 'Minor Damage':
                    $summary['minorDamage']++;
                    break;
                case 'Major Damage':
                    $summary['majorDamage']++;
                    break;
                case 'Lost':
                    $summary['lost']++;
                    break;
            }
            $summary['totalLateFees'] += $record['LateFee'] ?? 0;
        }

        return $summary;
    }

    /**
     * Calculate inventory report summary
     */
    private function calculateInventorySummary(array $records): array
    {
        $summary = [
            'total' => count($records),
            'perfectMatch' => 0,
            'discrepancies' => 0,
            'totalVariance' => 0
        ];

        foreach ($records as $record) {
            if ($record['Variance'] == 0) {
                $summary['perfectMatch']++;
            } else {
                $summary['discrepancies']++;
                $summary['totalVariance'] += abs($record['Variance']);
            }
        }

        return $summary;
    }

    /**
     * Export report data as CSV
     */
    public function exportReportAsCSV(string $reportType, array $data, string $filename): string
    {
        $csv = "Report Type: {$reportType}\n";
        $csv .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";

        if (!empty($data)) {
            $headers = array_keys($data[0]);
            $csv .= implode(',', array_map(function ($h) {
                return '"' . str_replace('"', '""', $h) . '"';
            }, $headers)) . "\n";

            foreach ($data as $row) {
                $csv .= implode(',', array_map(function ($v) {
                    return '"' . str_replace('"', '""', $v) . '"';
                }, $row)) . "\n";
            }
        }

        return $csv;
    }
}
