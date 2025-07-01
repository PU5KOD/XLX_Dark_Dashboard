<?php
header('Content-Type: text/plain');

$logFile = '/var/log/xlx.log'; 
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

if (file_exists($logFile) && is_readable($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $filteredLines = array_filter($lines, function($line) use ($filter) {
        return empty($filter) || stripos($line, $filter) !== false;
    });
    $lines = array_reverse($filteredLines); // Inverted order
    echo implode("\n", $lines); // Replicate original data without date/time conversion
} else {
    echo "Error: Could not access log.";
}
?>
