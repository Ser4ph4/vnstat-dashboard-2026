<?php
/**
 * API para retornar informações do sistema e banco de dados vnStat
 * Acesse via: /api_stats.php
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$response = [
    'success' => false,
    'data' => []
];

try {
    // Diretório do banco de dados vnStat
    $vnstatDir = '/var/lib/vnstat';
    
    // Tamanho do banco de dados
    $dbSize = 0;
    if (is_dir($vnstatDir)) {
        $files = glob($vnstatDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $dbSize += filesize($file);
            }
        }
    }
    
    // Informações do sistema
    $uptime = '';
    if (file_exists('/proc/uptime')) {
        $uptimeSeconds = (int)file_get_contents('/proc/uptime');
        $uptime = $uptimeSeconds;
    }
    
    // Uso de memória
    $memInfo = [];
    if (file_exists('/proc/meminfo')) {
        $memContent = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+)/', $memContent, $total);
        preg_match('/MemAvailable:\s+(\d+)/', $memContent, $available);
        
        if (isset($total[1]) && isset($available[1])) {
            $memInfo = [
                'total' => (int)$total[1] * 1024,
                'available' => (int)$available[1] * 1024,
                'used' => ((int)$total[1] - (int)$available[1]) * 1024
            ];
        }
    }
    
    // Carga do sistema
    $loadAvg = sys_getloadavg();
    
    $response['success'] = true;
    $response['data'] = [
        'database' => [
            'size' => $dbSize,
            'path' => $vnstatDir,
            'readable' => is_readable($vnstatDir)
        ],
        'system' => [
            'uptime' => $uptime,
            'load_average' => $loadAvg,
            'memory' => $memInfo,
            'timestamp' => time()
        ]
    ];
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);