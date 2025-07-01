<?php
session_start();

// Password verification
if (!isset($_SESSION['password']) || $_SESSION['password'] != "XLX_log") {
    die("Access denied!");
}

$logFile = '/var/log/xlx.log'; // Changed from /var/log/user.log to /var/log/xlx.log
$exportFile = 'xlx_log_export_' . date('Ymd_His') . '.txt';

if (file_exists($logFile) && is_readable($logFile)) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $exportFile . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines); // Inverted order
    echo implode("\n", $lines); // Replicate original data without date/time conversion
} else {
    die("Error: Unable to access log for export.");
}
?>
