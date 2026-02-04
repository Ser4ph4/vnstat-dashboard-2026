<?php
// ============================================
// VnStat JSON Generator
// ============================================
// 
// Este script gera um arquivo JSON com os 
// dados do vnstat para o dashboard React.
// 
// Para configurar atualizacao automatica:
// Execute: crontab -e
// Adicione a linha abaixo (sem o #):
// 
// */5 * * * * /usr/bin/php /var/www/****/update_data.php
// 
// Isso atualiza a cada 5 minutos
// ============================================

// Configuracoes
$interface = 'your_INTERFACE_NAME'; // *** ALTERE PARA SUA INTERFACE (Check: vnstat in terminal )***
$outputFile = __DIR__ . '/vnstat_dump_' . $interface . '.json';
$vnstatBin = '/usr/bin/vnstat';

// Verifica se o vnstat esta instalado
if (!file_exists($vnstatBin)) {
    die("ERRO: vnstat nao encontrado em {$vnstatBin}\n");
}

// Executa o comando vnstat para gerar JSON
$command = escapeshellcmd($vnstatBin) . ' --json -i ' . escapeshellarg($interface);
$output = shell_exec($command);

if ($output === null) {
    die("ERRO: Nao foi possivel executar o comando vnstat\n");
}

// Valida o JSON
$data = json_decode($output);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("ERRO: JSON invalido retornado pelo vnstat: " . json_last_error_msg() . "\n");
}

// Salva o arquivo JSON
$result = file_put_contents($outputFile, $output);

if ($result === false) {
    die("ERRO: Nao foi possivel salvar o arquivo {$outputFile}\n");
}

// Log de sucesso
$timestamp = date('Y-m-d H:i:s');
error_log("[{$timestamp}] VnStat JSON atualizado com sucesso: {$outputFile}");

// Exibe resultado
echo "============================================\n";
echo "  JSON Atualizado com Sucesso!\n";
echo "============================================\n";
echo "Interface: {$interface}\n";
echo "Arquivo:   {$outputFile}\n";
echo "Tamanho:   " . formatBytes($result) . "\n";
echo "Data/Hora: {$timestamp}\n";
echo "============================================\n";

function formatBytes($bytes) {
    $units = array('B', 'KB', 'MB', 'GB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes = $bytes / pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>
