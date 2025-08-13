<?php
session_start();

// Verificação da sessão
if (!isset($_SESSION['password']) || $_SESSION['password'] != XLX_log") {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Erro: Acesso negado. Faça login novamente.";
    exit();
}

header('Content-Type: text/plain; charset=utf-8');

// Sanitizar o filtro
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
    $file = null; // Fechar o arquivo
    echo implode("\n", array_reverse($lines));
} else {
    error_log("Erro ao acessar log em $logFile: " . (file_exists($logFile) ? 'Permissão negada' : 'Arquivo não encontrado'));
    echo "Erro: Não foi possível acessar o log.";
}
?>