<?php

declare(strict_types=1);

namespace Backend;

use Exception;

/**
 * Transactions API
 * Handles all API endpoints for borrowing, return, and inventory transactions
 */

// Include required classes from src/
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Http.php';
require_once __DIR__ . '/../src/AuthService.php';
require_once __DIR__ . '/../src/BorrowingService.php';
require_once __DIR__ . '/../src/InventoryService.php';
require_once __DIR__ . '/../src/ReportService.php';

// Load environment configuration
Config::loadEnv(__DIR__ . '/../.env');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Check for phpspreadsheet - load if available
$spreadsheetPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($spreadsheetPath)) {
    require_once $spreadsheetPath;
    require_once __DIR__ . '/../src/ExcelExportService.php';
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // Determine endpoint from query parameter (frontend uses ?endpoint=xxx)
    $endpoint = $_GET['endpoint'] ?? '';

    // Fallback: try URL path if no query param
    if (empty($endpoint)) {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $parts = explode('/', trim($path, '/'));
        $endpoint = $parts[count($parts) - 1] ?? '';
    }

    // ========== BORROWING TRANSACTIONS ==========
    if ($endpoint === 'borrow' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $borrowingService = new BorrowingService();
        $result = $borrowingService->createBorrowingTransaction(
            (int) $data['bookId'],
            (int) $data['userId'],
            (int) ($data['dueDays'] ?? 14),
            $data['notes'] ?? null,
            (int) ($data['createdBy'] ?? 0)
        );

        Http::json($result);
    }

    // Get borrowing transactions
    elseif ($endpoint === 'borrowing-transactions' && $method === 'GET') {
        $borrowingService = new BorrowingService();
        $result = $borrowingService->getBorrowingTransactions(
            isset($_GET['userId']) ? (int) $_GET['userId'] : null,
            $_GET['status'] ?? null,
            isset($_GET['limit']) ? (int) $_GET['limit'] : null,
            isset($_GET['offset']) ? (int) $_GET['offset'] : null
        );

        Http::json($result);
    }

    // ========== RETURN TRANSACTIONS ==========
    elseif ($endpoint === 'return-book' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $borrowingService = new BorrowingService();
        $result = $borrowingService->returnBook(
            (int) $data['transactionId'],
            (int) $data['processedBy'],
            $data['bookCondition'] ?? 'Good',
            $data['notes'] ?? null
        );

        Http::json($result);
    }

    // Get return transactions
    elseif ($endpoint === 'return-transactions' && $method === 'GET') {
        $borrowingService = new BorrowingService();
        
        $startDate = null;
        $endDate = null;

        if (isset($_GET['startDate'])) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $_GET['startDate']);
        }
        if (isset($_GET['endDate'])) {
            $endDate = \DateTime::createFromFormat('Y-m-d', $_GET['endDate']);
        }

        $result = $borrowingService->getReturnTransactions(
            isset($_GET['userId']) ? (int) $_GET['userId'] : null,
            $startDate,
            $endDate,
            isset($_GET['limit']) ? (int) $_GET['limit'] : null,
            isset($_GET['offset']) ? (int) $_GET['offset'] : null
        );

        Http::json($result);
    }

    // Get overdue books
    elseif ($endpoint === 'overdue-books' && $method === 'GET') {
        $borrowingService = new BorrowingService();
        $result = $borrowingService->getOverdueBooks();
        Http::json($result);
    }

    // ========== INVENTORY TRANSACTIONS ==========
    elseif ($endpoint === 'start-inventory-count' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $inventoryService = new InventoryService();
        $result = $inventoryService->startInventoryCount(
            (int) $data['conductedBy'],
            $data['notes'] ?? null
        );

        Http::json($result);
    }

    // Record physical quantity
    elseif ($endpoint === 'record-quantity' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $inventoryService = new InventoryService();
        $result = $inventoryService->recordPhysicalQuantity(
            (int) $data['countId'],
            (int) $data['bookId'],
            (int) $data['physicalQuantity'],
            $data['notes'] ?? null
        );

        Http::json($result);
    }

    // Complete inventory count
    elseif ($endpoint === 'complete-inventory-count' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $inventoryService = new InventoryService();
        $result = $inventoryService->completeInventoryCount(
            (int) $data['countId'],
            (int) $data['conductedBy']
        );

        Http::json($result);
    }

    // Verify inventory count
    elseif ($endpoint === 'verify-inventory-count' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $inventoryService = new InventoryService();
        $result = $inventoryService->verifyInventoryCount(
            (int) $data['countId'],
            (int) $data['verifiedBy']
        );

        Http::json($result);
    }

    // Get inventory counts
    elseif ($endpoint === 'inventory-counts' && $method === 'GET') {
        $inventoryService = new InventoryService();
        $result = $inventoryService->getAllInventoryCounts(
            $_GET['status'] ?? null,
            isset($_GET['limit']) ? (int) $_GET['limit'] : null,
            isset($_GET['offset']) ? (int) $_GET['offset'] : null
        );

        Http::json($result);
    }

    // Get inventory count by ID
    elseif ($endpoint === 'inventory-count' && $method === 'GET') {
        $countId = (int) ($_GET['countId'] ?? 0);

        $inventoryService = new InventoryService();
        $result = $inventoryService->getInventoryCount($countId);

        Http::json($result);
    }

    // Get inventory discrepancies
    elseif ($endpoint === 'inventory-discrepancies' && $method === 'GET') {
        $inventoryService = new InventoryService();
        $result = $inventoryService->getInventoryDiscrepancies(
            isset($_GET['countId']) ? (int) $_GET['countId'] : null
        );

        Http::json($result);
    }

    // ========== BOOK INVENTORY ==========
    elseif ($endpoint === 'book-inventory' && $method === 'GET') {
        $bookId = (int) ($_GET['bookId'] ?? 0);

        $borrowingService = new BorrowingService();
        if ($bookId > 0) {
            $result = $borrowingService->getBookInventory($bookId);
        } else {
            $result = $borrowingService->getAllInventory(
                isset($_GET['limit']) ? (int) $_GET['limit'] : null,
                isset($_GET['offset']) ? (int) $_GET['offset'] : null
            );
        }

        Http::json($result);
    }

    // Get members (for borrow dropdown)
    elseif ($endpoint === 'get-members' && $method === 'GET') {
        require_once __DIR__ . '/../src/UserService.php';
        $userService = new UserService(Database::connection());
        $users = $userService->list([
            'search' => '',
            'type' => 'Member',
            'status' => 'Active',
        ]);
        Http::json(['success' => true, 'data' => $users]);
    }

    // ========== REPORTS ==========
    elseif ($endpoint === 'borrowing-report' && $method === 'GET') {
        $reportService = new ReportService();
        
        $startDate = null;
        $endDate = null;

        if (isset($_GET['startDate'])) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $_GET['startDate']);
        }
        if (isset($_GET['endDate'])) {
            $endDate = \DateTime::createFromFormat('Y-m-d', $_GET['endDate']);
        }

        $result = $reportService->getBorrowingReport(
            $startDate,
            $endDate,
            $_GET['status'] ?? null
        );

        Http::json($result);
    }

    // Get return report
    elseif ($endpoint === 'return-report' && $method === 'GET') {
        $reportService = new ReportService();
        
        $startDate = null;
        $endDate = null;

        if (isset($_GET['startDate'])) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $_GET['startDate']);
        }
        if (isset($_GET['endDate'])) {
            $endDate = \DateTime::createFromFormat('Y-m-d', $_GET['endDate']);
        }

        $result = $reportService->getReturnReport($startDate, $endDate);

        Http::json($result);
    }

    // Get inventory report
    elseif ($endpoint === 'inventory-report' && $method === 'GET') {
        $reportService = new ReportService();
        $result = $reportService->getInventoryReport(
            isset($_GET['countId']) ? (int) $_GET['countId'] : null
        );

        Http::json($result);
    }

    // Get transaction statistics
    elseif ($endpoint === 'transaction-statistics' && $method === 'GET') {
        $reportService = new ReportService();
        $result = $reportService->getTransactionStatistics();
        Http::json($result);
    }

    // Get top borrowed books
    elseif ($endpoint === 'top-borrowed-books' && $method === 'GET') {
        $reportService = new ReportService();
        $result = $reportService->getTopBorrowedBooks(
            (int) ($_GET['limit'] ?? 10)
        );
        Http::json($result);
    }

    // Get top borrowers
    elseif ($endpoint === 'top-borrowers' && $method === 'GET') {
        $reportService = new ReportService();
        $result = $reportService->getTopBorrowers(
            (int) ($_GET['limit'] ?? 10)
        );
        Http::json($result);
    }

    // ========== EXCEL EXPORT ==========
    elseif ($endpoint === 'export-borrowing-excel' && $method === 'POST') {
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            Http::json([
                'success' => false,
                'message' => 'PhpSpreadsheet library not installed. Run: composer require phpoffice/phpspreadsheet'
            ]);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $startDate = (!empty($data['startDate'])) ? \DateTime::createFromFormat('Y-m-d', $data['startDate']) : null;
        $endDate = (!empty($data['endDate'])) ? \DateTime::createFromFormat('Y-m-d', $data['endDate']) : null;
        $status = (!empty($data['status'])) ? $data['status'] : null;

        $reportService = new ReportService();
        $report = $reportService->getBorrowingReport(
            $startDate instanceof \DateTime ? $startDate : null,
            $endDate instanceof \DateTime ? $endDate : null,
            $status
        );

        if ($report['success']) {
            $excelService = new ExcelExportService($data['generatedBy'] ?? 'System Administrator');
            $filepath = $excelService->generateBorrowingReport($report['data'], $report['summary']);

            Http::json([
                'success' => true,
                'filepath' => '/e-lib-EDP/backend/reports/' . basename($filepath),
                'filename' => basename($filepath),
                'message' => 'Report exported successfully'
            ]);
        } else {
            Http::json($report);
        }
    }

    // Export return report to Excel
    elseif ($endpoint === 'export-return-excel' && $method === 'POST') {
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            Http::json([
                'success' => false,
                'message' => 'PhpSpreadsheet library not installed'
            ]);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $startDate = (!empty($data['startDate'])) ? \DateTime::createFromFormat('Y-m-d', $data['startDate']) : null;
        $endDate = (!empty($data['endDate'])) ? \DateTime::createFromFormat('Y-m-d', $data['endDate']) : null;

        $reportService = new ReportService();
        $report = $reportService->getReturnReport(
            $startDate instanceof \DateTime ? $startDate : null,
            $endDate instanceof \DateTime ? $endDate : null
        );

        if ($report['success']) {
            $excelService = new ExcelExportService($data['generatedBy'] ?? 'System Administrator');
            $filepath = $excelService->generateReturnReport($report['data'], $report['summary']);

            Http::json([
                'success' => true,
                'filepath' => '/e-lib-EDP/backend/reports/' . basename($filepath),
                'filename' => basename($filepath),
                'message' => 'Report exported successfully'
            ]);
        } else {
            Http::json($report);
        }
    }

    // Export inventory report to Excel
    elseif ($endpoint === 'export-inventory-excel' && $method === 'POST') {
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            Http::json([
                'success' => false,
                'message' => 'PhpSpreadsheet library not installed'
            ]);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $countId = (int) ($data['countId'] ?? 0);

        $reportService = new ReportService();
        $report = $reportService->getInventoryReport($countId);

        if ($report['success']) {
            $excelService = new ExcelExportService($data['generatedBy'] ?? 'System Administrator');
            $filepath = $excelService->generateInventoryReport(
                $report['header'],
                $report['data'],
                $report['summary']
            );

            Http::json([
                'success' => true,
                'filepath' => '/e-lib-EDP/backend/reports/' . basename($filepath),
                'filename' => basename($filepath),
                'message' => 'Report exported successfully'
            ]);
        } else {
            Http::json($report);
        }
    }

    else {
        Http::json([
            'success' => false,
            'message' => 'Endpoint not found'
        ], 404);
    }
} catch (Exception $e) {
    Http::json([
        'success' => false,
        'message' => $e->getMessage()
    ], 500);
}
