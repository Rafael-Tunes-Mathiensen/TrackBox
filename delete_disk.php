<?php
// delete_disk.php
require_once 'includes/functions.php';

// Verificar se está logado
requireLogin();

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Verificar CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido']);
    exit();
}

$disk_id = (int)($_POST['disk_id'] ?? 0);

if (empty($disk_id)) {
    echo json_encode(['success' => false, 'message' => 'ID do disco não fornecido']);
    exit();
}

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Verificar se o disco pertence ao usuário
    $stmt = $pdo->prepare("SELECT id FROM disks WHERE id = ? AND user_id = ?");
    $stmt->execute([$disk_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Disco não encontrado ou acesso negado']);
        exit();
    }
    
    $pdo->beginTransaction();
    
    // Excluir registros relacionados primeiro
    $stmt = $pdo->prepare("DELETE FROM disk_extras WHERE disk_id = ?");
    $stmt->execute([$disk_id]);
    
    $stmt = $pdo->prepare("DELETE FROM boxset_details WHERE disk_id = ?");
    $stmt->execute([$disk_id]);
    
    // Excluir o disco
    $stmt = $pdo->prepare("DELETE FROM disks WHERE id = ? AND user_id = ?");
    $stmt->execute([$disk_id, $_SESSION['user_id']]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Disco excluído com sucesso']);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    error_log("Erro ao excluir disco: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>