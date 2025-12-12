<?php
// ajax/discogs_search.php
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
    
    $query = trim($input['query'] ?? '');
    $type = trim($input['type'] ?? '');
    $page = max(1, (int)($input['page'] ?? 1));
    
    if (empty($query)) {
        throw new Exception('Termo de busca é obrigatório');
    }
    
    if (strlen($query) < 2) {
        throw new Exception('Termo de busca deve ter pelo menos 2 caracteres');
    }
    
    $discogs = new DiscogsAPI();
    
    // Testar conexão primeiro
    try {
        $discogs->testConnection();
    } catch (Exception $e) {
        throw new Exception('Erro de conexão com Discogs: ' . $e->getMessage());
    }
    
    $results = $discogs->searchReleases($query, $type, 20, $page);
    
    // Formatar resultados
    $formattedResults = [];
    if (isset($results['results']) && is_array($results['results'])) {
        foreach ($results['results'] as $release) {
            try {
                $formatted = $discogs->formatReleaseData($release);
                $formattedResults[] = $formatted;
            } catch (Exception $e) {
                // Log do erro mas continua processando outros resultados
                error_log('Erro ao formatar resultado Discogs: ' . $e->getMessage());
                continue;
            }
        }
    }
    
    $response = [
        'success' => true,
        'results' => $formattedResults,
        'pagination' => [
            'page' => $results['pagination']['page'] ?? 1,
            'pages' => $results['pagination']['pages'] ?? 1,
            'per_page' => $results['pagination']['per_page'] ?? 20,
            'items' => $results['pagination']['items'] ?? 0
        ],
        'query' => $query,
        'total_results' => count($formattedResults)
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
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