<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['password']) || $_SESSION['password'] != "XLX_log") {
   http_response_code(403);
   echo "Acesso negado.";
   die();
}

// Verifica se há log acumulado
if (!isset($_SESSION['log_content']) || empty($_SESSION['log_content'])) {
   echo "Nenhum log disponível para exportação.";
   die();
}

// Prepara o conteúdo para download
$log_content = implode("\n", $_SESSION['log_content']);
$filename = 'xlx_log_' . date('Y-m-d_H-i-s') . '.txt';

// Define os cabeçalhos para download
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($log_content));

// Exibe o conteúdo
echo $log_content;
exit;
?>
