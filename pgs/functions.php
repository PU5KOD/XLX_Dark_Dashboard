<?php
function GetSystemUptime() {
 $out = exec("uptime");
 return substr($out, 0, strpos($out, ","));
}
function Debug($message) {
 echo '<br><hr><pre>';
 print_r($message);
 echo '</pre><hr><br>';
}
function ParseTime($Input) {
 if (strpos($Input, "<") !== false) {
 $Input = substr($Input, 0, strpos($Input, "<"));
 }
 // Tuesday Tue Nov 17 14:23:22 2015
 $tmp = explode(" ", $Input);
 if (strlen(trim($tmp[3])) == 0) {
 unset($tmp[3]);
 $tmp = array_values($tmp);
 }
 $tmp1 = explode(":", $tmp[4]);
 $month = "";
 switch (strtolower($tmp[2])) {
 case 'jan' : $month = 1; break;
 case 'feb' : $month = 2; break;
 case 'mar' : $month = 3; break;
 case 'apr' : $month = 4; break;
 case 'may' : $month = 5; break;
 case 'jun' : $month = 6; break;
 case 'jul' : $month = 7; break;
 case 'aug' : $month = 8; break;
 case 'sep' : $month = 9; break;
 case 'oct' : $month = 10; break;
 case 'nov' : $month = 11; break;
 case 'dec' : $month = 12; break;
 default : $month = 1;
 }
 return @mktime($tmp1[0], $tmp1[1], $tmp1[2], $month, $tmp[3], $tmp[5]);
}
function FormatSeconds($seconds) {
   $seconds = abs($seconds);
   $days = floor($seconds / 60 / 60 / 24);
   if ($days == 0) {
      return sprintf("%02d:%02d:%02d", ($seconds / 60 / 60) % 24, ($seconds / 60) % 60, $seconds % 60);
   } else {
      return sprintf("%d d. %02d:%02d:%02d", $days, ($seconds / 60 / 60) % 24, ($seconds / 60) % 60, $seconds % 60);
   }
}
function CreateCode($laenge) {
 $zeichen = "1234567890abcdefghijklmnopqrstuvwyxzABCDEFGHIJKLMNAOPQRSTUVWYXZ";
 $out = "";
 for ($i=1;$i<=$laenge;$i++){
 $out .= $zeichen[mt_rand(0,(strlen($zeichen)-1))];
 }
 return $out;
}
function VNStatLocalize($str) {
 global $L;
 if (isset($L[$str])) {
 return $L[$str];
 } else {
 return $str;
 }
}
function VNStatGetData($iface, $vnstat_bin) {
 $data = array(
 'database_updated' => '',
 'since' => '',
 'totals' => array('rx' => 0, 'rx_unit' => 'MiB', 'tx' => 0, 'tx_unit' => 'MiB', 'total' => 0, 'total_unit' => 'MiB'),
 'daily' => array(),
 'monthly' => array()
 );
 // Executar vnstat sem argumentos para capturar informações gerais
 $output = shell_exec("$vnstat_bin -i $iface");
 if ($output === null) {
 error_log("Failed to execute vnstat -i $iface");
 return $data;
 }
 $lines = explode("\n", $output);
 // Capturar "Database updated"
 if (preg_match('/Database updated: (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $output, $matches)) {
 $data['database_updated'] = $matches[1];
 }
 // Capturar "since"
 if (preg_match('/since (\d{4}-\d{2}-\d{2})/', $output, $matches)) {
 $data['since'] = $matches[1];
 }
 // Capturar totais gerais
 if (preg_match('/rx:\s+([\d\.]+)\s+([KMGT]iB)\s+tx:\s+([\d\.]+)\s+([KMGT]iB)\s+total:\s+([\d\.]+)\s+([KMGT]iB)/', $output, $matches)) {
 $data['totals']['rx'] = floatval($matches[1]);
 $data['totals']['rx_unit'] = $matches[2];
 $data['totals']['tx'] = floatval($matches[3]);
 $data['totals']['tx_unit'] = $matches[4];
 $data['totals']['total'] = floatval($matches[5]);
 $data['totals']['total_unit'] = $matches[6];
 }
 // Dados mensais (vnstat -m)
 $monthly_output = shell_exec("$vnstat_bin -i $iface -m");
 if ($monthly_output !== null) {
 $monthly_lines = explode("\n", $monthly_output);
 foreach ($monthly_lines as $line) {
 // Dados mensais
 if (preg_match('/(\d{4}-\d{2})\s+([\d\.]+)\s+([KMGT]iB)\s*[\|]\s*([\d\.]+)\s+([KMGT]iB)\s*[\|]\s*([\d\.]+)\s+([KMGT]iB)\s*[\|]\s*([\d\.]+)\s+(kbit\/s|Mbit\/s)/', $line, $matches)) {
 $date = strtotime($matches[1] . "-01");
 if ($date === false) {
 error_log("Failed to parse monthly date: " . $matches[1]);
 continue;
 }
 $rx = floatval($matches[2]);
 $rx_unit = $matches[3];
 $tx = floatval($matches[4]);
 $tx_unit = $matches[5];
 $total = floatval($matches[6]);
 $total_unit = $matches[7];
 $avg_rate = floatval($matches[8]);
 $avg_rate_unit = $matches[9]; // Capturar a unidade (kbit/s ou Mbit/s)
 $data['monthly'][] = array(
 'time' => $date,
 'rx' => $rx,
 'rx_unit' => $rx_unit,
 'tx' => $tx,
 'tx_unit' => $tx_unit,
 'total' => $total,
 'total_unit' => $total_unit,
 'avg_rate' => $avg_rate,
 'avg_rate_unit' => $avg_rate_unit // Armazenar a unidade
 );
 }
 // Valores estimados mensais
 if (preg_match('/estimated\s+([\d\.]+)\s+([KMGT]iB)\s*[\|]\s*([\d\.]+)\s+([KMGT]iB)\s*[\|]\s*([\d\.]+)\s+([KMGT]iB)/', $line, $matches)) {
 if (!empty($data['monthly'])) {
 $data['monthly'][count($data['monthly']) - 1]['estimated'] = array(
 'rx' => floatval($matches[1]),
 'rx_unit' => $matches[2],
 'tx' => floatval($matches[3]),
 'tx_unit' => $matches[4],
 'total' => floatval($matches[5]),
 'total_unit' => $matches[6]
 );
 }
 }
 }
 } else {
 error_log("Failed to retrieve monthly data for interface $iface");
 }
 // Dados diários (vnstat -d)
 $daily_output = shell_exec("$vnstat_bin -i $iface -d");
 if ($daily_output !== null) {
 $daily_lines = explode("\n", $daily_output);
 foreach ($daily_lines as $line) {
 // Dados diários
 if (preg_match('/(\d{4}-\d{2}-\d{2})\s+([\d\.]+)\s+([KMGT]iB)\s*[\|]\s*([\d\.]+)\s+([KMGT]iB)\s*[\|]\s*([\d\.]+)\s+([KMGT]iB)\s*[\|]\s*([\d\.]+)\s+(kbit\/s|Mbit\/s)/', $line, $matches)) {
 $date = strtotime($matches[1]);
 if ($date === false) {
 error_log("Failed to parse daily date: " . $matches[1]);
 continue;
 }
 $rx = floatval($matches[2]);
 $rx_unit = $matches[3];
 $tx = floatval($matches[4]);
 $tx_unit = $matches[5];
 $total = floatval($matches[6]);
 $total_unit = $matches[7];
 $avg_rate = floatval($matches[8]);
 $avg_rate_unit = $matches[9]; // Capturar a unidade (kbit/s ou Mbit/s)
 $data['daily'][] = array(
 'time' => $date,
 'rx' => $rx,
 'rx_unit' => $rx_unit,
 'tx' => $tx,
 'tx_unit' => $tx_unit,
 'total' => $total,
 'total_unit' => $total_unit,
 'avg_rate' => $avg_rate,
 'avg_rate_unit' => $avg_rate_unit // Armazenar a unidade
 );
 }
 // Valores estimados diários
 if (preg_match('/estimated\s+([\d\.]+)\s+([KMGT]iB)\s*[\|]\s*([\d\.]+)\s+([KMGT]iB)\s*[\|]\s*([\d\.]+)\s+([KMGT]iB)/', $line, $matches)) {
 if (!empty($data['daily'])) {
 $data['daily'][count($data['daily']) - 1]['estimated'] = array(
 'rx' => floatval($matches[1]),
 'rx_unit' => $matches[2],
 'tx' => floatval($matches[3]),
 'tx_unit' => $matches[4],
 'total' => floatval($matches[5]),
 'total_unit' => $matches[6]
 );
 }
 }
 }
 } else {
 error_log("Failed to retrieve daily data for interface $iface");
 }
 return $data;
}
// Função para formatar valores de tráfego com suas unidades
function format_traffic($value, $unit) {
 // Arredondar o valor para 2 casas decimais
 $value = round(floatval($value), 2);
 // Retornar o valor formatado com a unidade
 return "$value $unit";
}
?>
