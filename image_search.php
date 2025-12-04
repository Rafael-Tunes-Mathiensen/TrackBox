<?php
// image_search.php
require_once 'includes/functions.php';
require_once 'includes/image_functions.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$action = $_POST['action'] ?? '';
$artist = sanitizeInput($_POST['artist'] ?? '');
$album = sanitizeInput($_POST['album'] ?? '');

if ($action !== 'search' || empty($artist) || empty($album)) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

try {
    $cover_url = ImageHandler::searchCoverImage($artist, $album);
    
    if ($cover_url) {
        echo json_encode([
            'success' => true,
            'image_url' => $cover_url,
            'source' => 'api',
            'message' => 'Capa encontrada com sucesso!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Nenhuma capa encontrada para este álbum'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erro na busca de capa: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ]);
}
?>