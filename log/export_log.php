<?php
session_start();

// Verificação da sessão
if (!isset($_SESSION['password']) || $_SESSION['password'] != XLX_log") {
    $_SESSION['error'] = "Acesso negado. Por favor, faça login.";
    header('Location: index.php');
    exit();
}

// Opcional: Verificar token CSRF via GET (se passado pelo index.php)
if (isset($_GET['csrf_token']) && $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Token CSRF inválido.";
    header('Location: index.php');
    exit();
}

$logFile = '/var/log/xlx.log';
$exportFile = 'xlx_log_export_' . date('Ymd_His') . '.txt';

if (file_exists($logFile) && is_readable($logFile)) {
    // Escapar o nome do arquivo para evitar problemas em headers
    $exportFile = str_replace(["\r", "\n", "\"", "'"], '', $exportFile);
    
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $exportFile . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Ler arquivo em chunks para eficiência (melhor para logs grandes)
    $file = new SplFileObject($logFile, 'r');
    $lines = [];
    while (!$file->eof()) {
        $line = $file->fgets();
        if (trim($line) !== '') {
            $lines[] = trim($line);
        }
    }
    $file = null; // Fechar o arquivo
    echo implode("\n", array_reverse($lines));
} else {
    // Logar erro no servidor para debug
    error_log("Erro ao acessar log em $logFile: " . (file_exists($logFile) ? 'Permissão negada' : 'Arquivo não encontrado'));
    $_SESSION['error'] = "Erro: Não foi possível acessar o log para exportação.";
    header('Location: index.php');
    exit();
}
?>