<?php
session_start();

// Session verification
if (!isset($_SESSION['password']) || $_SESSION['password'] != "XLX_log") {
    $_SESSION['error'] = "Access denied. Please log in.";
    header('Location: index.php');
    exit();
}

// Optional: Verify CSRF token via GET
if (isset($_GET['csrf_token']) && $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Invalid CSRF token.";
    header('Location: index.php');
    exit();
}

$logFile = '/var/log/xlx.log';
$exportFile = 'xlx_log_export_' . date('Ymd_His') . '.txt';

if (file_exists($logFile) && is_readable($logFile)) {
    // Escape filename to prevent issues in headers
    $exportFile = str_replace(["\r", "\n", "\"", "'"], '', $exportFile);
    
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $exportFile . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Read file in chunks for efficiency
    $file = new SplFileObject($logFile, 'r');
    $lines = [];
    while (!$file->eof()) {
        $line = $file->fgets();
        if (trim($line) !== '') {
            $lines[] = trim($line);
        }
    }
    $file = null; // Close file
    echo implode("\n", array_reverse($lines));
} else {
    // Log error for debugging
    error_log("Error accessing log at $logFile: " . (file_exists($logFile) ? 'Permission denied' : 'File not found'));
    $_SESSION['error'] = "Error: Unable to access log for export.";
    header('Location: index.php');
    exit();
}
?>