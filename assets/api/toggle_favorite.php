<?php
// TrackBox/api/toggle_favorite.php
require_once '../includes/functions.php'; // Ajuste o caminho conforme necessário

// Conectar ao banco de dados
require_once '../config/database.php';
$database = new Database();
$pdo = $database->getConnection();
header('Content-Type: application/json');

// Verificar se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Verificar se está logado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

// Verificar CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit();
}

$disk_id = (int)($_POST['disk_id'] ?? 0);
if (empty($disk_id)) {
    echo json_encode(['success' => false, 'message' => 'ID do disco não fornecido.']);
    exit();
}

try {
    // Verificar se o disco pertence ao usuário logado
    $stmt = $pdo->prepare("SELECT user_id, is_favorite FROM disks WHERE id = ?");
    $stmt->execute([$disk_id]);
    $disk_info = $stmt->fetch();

    if (!$disk_info || $disk_info['user_id'] !== $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Disco não encontrado ou acesso negado.']);
        exit();
    }

    if (toggleFavorite($pdo, $disk_id, $_SESSION['user_id'])) {
        echo json_encode(['success' => true, 'is_favorite' => !$disk_info['is_favorite'], 'message' => 'Status de favorito atualizado!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Falha ao atualizar status de favorito.']);
    }
} catch (Exception $e) {
    error_log("Erro ao alternar favorito: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
}
?>