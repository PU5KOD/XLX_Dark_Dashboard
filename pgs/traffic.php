<?php
// Enable error logging
//ini_set('log_errors', 1);
//ini_set('error_log', '/var/www/html/xlxd/pgs/php_errors.log');
// Check if $VNStat is set
if (!isset($VNStat)) {
    die("Error: \$VNStat isn't defined");
}
// Interface validation
if (!isset($_GET['iface'])) {
    if (isset($VNStat['Interfaces'][0]['Address'])) {
        $_GET['iface'] = $VNStat['Interfaces'][0]['Address'];
    } else {
        $_GET['iface'] = "";
    }
} else {
    $f = false;
    $i = 0;
    while ($i < count($VNStat['Interfaces']) && (!$f)) {
        if ($_GET['iface'] == $VNStat['Interfaces'][$i]['Address']) {
            $f = true;
        }
        $i++;
    }
    if (!$f) {
        $_GET['iface'] = "";
    }
}
// Set locale to English
setlocale(LC_TIME, 'en.UTF-8', 'en', 'english');
?>
<table class="listingtable" style="padding: 0; margin: 0 auto; width: 900px;">
 <tr>
   <th>Network Interface Statistics</th>
 </tr>
 <tr>
    <td bgcolor="#2c2c2c" style="padding: 10px;">
    <?php
    $Data = VNStatGetData($_GET['iface'], $VNStat['Binary']);
    // Style for tables
    $tableStyle = 'border-collapse: collapse; margin: 10px auto; width: 100%;';
    $thStyle = 'background-color: #3a3a3a; color: #c3dcba; font-weight: bold; padding: 1px; border: 1px solid #606060; text-align: center; white-space: nowrap;';
    $tdStyle = 'padding: 3px; border: 1px solid #606060; text-align: center; white-space: nowrap; color: #c3dcba;';
    // Display grand totals
    echo '
    <table style="' . $tableStyle . '">
        <tr>
            <th style="' . $thStyle . '">RX Total</th>
            <th style="' . $thStyle . '">TX Total</th>
            <th style="' . $thStyle . '">Grand Total (TX + RX)</th>
        </tr>
        <tr>
            <td style="' . $tdStyle . '">' . format_traffic($Data['totals']['rx'], $Data['totals']['rx_unit']) . '</td>
            <td style="' . $tdStyle . '">' . format_traffic($Data['totals']['tx'], $Data['totals']['tx_unit']) . '</td>
            <td style="' . $tdStyle . '">' . format_traffic($Data['totals']['total'], $Data['totals']['total_unit']) . '</td>
        </tr>
    </table>';
    // Display monthly data
    echo '
    <table style="' . $tableStyle . '">
        <tr>
            <th style="' . $thStyle . '" colspan="5">Monthly Data</th>
            <th style="' . $thStyle . '" colspan="3">Estimated Values</th>
        </tr>
        <tr>
            <th style="' . $thStyle . '">Month</th>
            <th style="' . $thStyle . '">RX</th>
            <th style="' . $thStyle . '">TX</th>
            <th style="' . $thStyle . '">Total</th>
            <th style="' . $thStyle . '">Average Rate</th>
            <th style="' . $thStyle . '">RX</th>
            <th style="' . $thStyle . '">TX</th>
            <th style="' . $thStyle . '">Total</th>
        </tr>';
    if (empty($Data['monthly'])) {
        echo "<tr><td colspan='8' style='$tdStyle'>No monthly data available</td></tr>";
    }
    foreach ($Data['monthly'] as $month) {
        if (isset($month['time']) && $month['time'] > 0) {
            echo '
            <tr>
                <td style="' . $tdStyle . '">' . strftime("%B %Y", $month['time']) . '</td>
                <td style="' . $tdStyle . '">' . format_traffic($month['rx'], $month['rx_unit']) . '</td>
                <td style="' . $tdStyle . '">' . format_traffic($month['tx'], $month['tx_unit']) . '</td>
                <td style="' . $tdStyle . '">' . format_traffic($month['total'], $month['total_unit']) . '</td>
                <td style="' . $tdStyle . '">' . sprintf("%.2f %s", $month['avg_rate'], $month['avg_rate_unit']) . '</td>
                <td style="' . $tdStyle . '">' . (isset($month['estimated']['rx']) ? format_traffic($month['estimated']['rx'], $month['estimated']['rx_unit']) : '-') . '</td>
                <td style="' . $tdStyle . '">' . (isset($month['estimated']['tx']) ? format_traffic($month['estimated']['tx'], $month['estimated']['tx_unit']) : '-') . '</td>
                <td style="' . $tdStyle . '">' . (isset($month['estimated']['total']) ? format_traffic($month['estimated']['total'], $month['estimated']['total_unit']) : '-') . '</td>
            </tr>';
        } else {
            echo "<tr><td colspan='8' style='$tdStyle'>Monthly data without valid timestamp</td></tr>";
        }
    }
    echo '</table>';
    // Display daily data
    echo '
    <table style="' . $tableStyle . '">
        <tr>
            <th style="' . $thStyle . '" colspan="5">Daily Data</th>
            <th style="' . $thStyle . '" colspan="3">Estimated Values</th>
        </tr>
        <tr>
            <th style="' . $thStyle . '">Day</th>
            <th style="' . $thStyle . '">RX</th>
            <th style="' . $thStyle . '">TX</th>
            <th style="' . $thStyle . '">Total</th>
            <th style="' . $thStyle . '">Average Rate</th>
            <th style="' . $thStyle . '">RX</th>
            <th style="' . $thStyle . '">TX</th>
            <th style="' . $thStyle . '">Total</th>
        </tr>';
    if (empty($Data['daily'])) {
        echo "<tr><td colspan='8' style='$tdStyle'>No daily data available</td></tr>";
    }
    foreach ($Data['daily'] as $day) {
        if (isset($day['time']) && $day['time'] > 0) {
            echo '
            <tr>
                <td style="' . $tdStyle . '">' . date("d/m/Y", $day['time']) . '</td>
                <td style="' . $tdStyle . '">' . format_traffic($day['rx'], $day['rx_unit']) . '</td>
                <td style="' . $tdStyle . '">' . format_traffic($day['tx'], $day['tx_unit']) . '</td>
                <td style="' . $tdStyle . '">' . format_traffic($day['total'], $day['total_unit']) . '</td>
                <td style="' . $tdStyle . '">' . sprintf("%.2f %s", $day['avg_rate'], $day['avg_rate_unit']) . '</td>
                <td style="' . $tdStyle . '">' . (isset($day['estimated']['rx']) ? format_traffic($day['estimated']['rx'], $day['estimated']['rx_unit']) : '-') . '</td>
                <td style="' . $tdStyle . '">' . (isset($day['estimated']['tx']) ? format_traffic($day['estimated']['tx'], $day['estimated']['tx_unit']) : '-') . '</td>
                <td style="' . $tdStyle . '">' . (isset($day['estimated']['total']) ? format_traffic($day['estimated']['total'], $day['estimated']['total_unit']) : '-') . '</td>
            </tr>';
        } else {
            echo "<tr><td colspan='8' style='$tdStyle'>Daily data without valid timestamp</td></tr>";
        }
    }
    echo '</table>';
    // Display "Database updated" and "since"
    echo '<div style="margin: 10px; text-align: right; font-size: 12px; color: #c3dcba;">';
    // Adjust database update date format
    $databaseUpdated = DateTime::createFromFormat('Y-m-d H:i:s', $Data['database_updated']);
    if ($databaseUpdated) {
        echo "Last update: " . $databaseUpdated->format('d/m/Y, H:i:s') . "<br>";
    } else {
        echo "Last update: " . htmlspecialchars($Data['database_updated']) . "<br>";
    }
    // Adjust "since" date format
    $sinceDate = DateTime::createFromFormat('Y-m-d', $Data['since']);
    if ($sinceDate) {
        echo "Interface " . htmlspecialchars($_GET['iface']) . " since " . $sinceDate->format('d/m/Y') . "<br>";
    } else {
        echo "Interface " . htmlspecialchars($_GET['iface']) . " since " . htmlspecialchars($Data['since']) . "<br>";
    }
    echo '</div>';
    ?>
    </td>
 </tr>
</table>
