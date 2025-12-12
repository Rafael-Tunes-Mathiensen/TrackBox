<?php
// ajax/discogs_release.php
require_once '../includes/functions.php';
require_once '../config/discogs_config.php';

// Verificar se está logado
requireLogin();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido');
    }
    
    $releaseId = (int)($input['release_id'] ?? 0);
    
    if (empty($releaseId)) {
        throw new Exception('ID do release é obrigatório');
    }
    
    $discogs = new DiscogsAPI();
    $release = $discogs->getRelease($releaseId);
    $formattedData = $discogs->formatReleaseData($release);
    
    echo json_encode([
        'success' => true,
        'data' => $formattedData,
        'raw_data' => $release // Para debug se necessário
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>