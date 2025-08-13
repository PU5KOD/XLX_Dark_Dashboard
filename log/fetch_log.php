<?php
session_start();

// Session verification
if (!isset($_SESSION['password']) || $_SESSION['password'] != "XLX_log") {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Error: Access denied. Please log in again.";
    exit();
}

header('Content-Type: text/plain; charset=utf-8');

// Sanitize filter parameter
$filter = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_STRING) ?? '';
$logFile = '/var/log/xlx.log';

if (file_exists($logFile) && is_readable($logFile)) {
    $file = new SplFileObject($logFile, 'r');
    $lines = [];
    while (!$file->eof()) {
        $line = $file->fgets();
        if (trim($line) !== '') {
            if (empty($filter) || stripos($line, $filter) !== false) {
                $lines[] = trim($line);
            }
        }
    }
    $file = null; // Close file
    echo implode("\n", array_reverse($lines));
} else {
    // Log error for debugging
    error_log("Error accessing log at $logFile: " . (file_exists($logFile) ? 'Permission denied' : 'File not found'));
    echo "Error: Unable to access log.";
}
?>