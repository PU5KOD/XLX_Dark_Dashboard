<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['password']) || $_SESSION['password'] != "XLX_log") {
   http_response_code(403);
   echo "Acesso negado.";
   die();
}

// Inicializa o log acumulado se não existir
if (!isset($_SESSION['log_content'])) {
   $_SESSION['log_content'] = [];
}

$log_file = '/var/log/user.log';

// Verifica se o arquivo existe
if (!file_exists($log_file)) {
   echo "Erro: O arquivo $log_file não foi encontrado.";
   die();
}

// Verifica se o arquivo é legível
if (!is_readable($log_file)) {
   echo "Erro: O arquivo $log_file não tem permissões de leitura.";
   die();
}

// Executa o comando tail -n 50 para obter as últimas 50 linhas
$output = shell_exec('tail -n 50 ' . escapeshellarg($log_file));

// Processa as linhas do log
if ($output !== null && !empty($output)) {
   $new_lines = explode("\n", trim($output)); // Divide em linhas
   $new_lines = array_filter($new_lines); // Remove linhas vazias

   // Adiciona apenas linhas novas ao log acumulado
   foreach ($new_lines as $line) {
      // Aplica formatação à linha
      if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}(?:[+-]\d{2}:\d{2})?)\s+[^:]+:\s*(.*)$/', $line, $matches)) {
         // Extrai data/hora e mensagem
         $datetime_str = $matches[1]; // Ex.: 2025-06-19T05:25:43.075316-03:00
         $message = $matches[2]; // Ex.: DCS connect packet for module D from PU5KOD  C at 143.105.25.27

         // Formata data e hora
         try {
            $datetime = new DateTime($datetime_str);
            $formatted_line = $datetime->format('d/m/Y, H:i:s.u') . ': ' . $message;
         } catch (Exception $e) {
            $formatted_line = 'Erro na formatação da data: ' . $line;
         }
      } else {
         // Se a linha não seguir o padrão esperado, mantém como está
         $formatted_line = $line;
      }

      // Armazena a linha formatada se não estiver presente
      if (!in_array($formatted_line, $_SESSION['log_content'])) {
         $_SESSION['log_content'][] = $formatted_line;
      }
   }

   // Limita o tamanho do log acumulado (ex.: 1000 linhas)
   if (count($_SESSION['log_content']) > 1000) {
      $_SESSION['log_content'] = array_slice($_SESSION['log_content'], -1000);
   }
} elseif (empty($_SESSION['log_content'])) {
   $_SESSION['log_content'] = ["O arquivo $log_file está vazio ou não contém dados recentes."];
}

// Aplica o filtro, se fornecido
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';
$filtered_lines = $filter ? array_filter($_SESSION['log_content'], function($line) use ($filter) {
   return stripos($line, $filter) !== false;
}) : $_SESSION['log_content'];

// Prepara o conteúdo para exibição (inverte para o mais recente no topo)
$display_lines = array_reverse($filtered_lines);

// Exibe o log
echo implode("\n", $display_lines);
?>
