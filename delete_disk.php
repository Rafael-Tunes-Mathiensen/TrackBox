<?php
// delete_disk.php
require_once 'includes/functions.php';
// Verificar se está logado
requireLogin();

// Conectar ao banco de dados para verificar permissões
require_once 'config/database.php';
$database = new Database();
$pdo = $database->getConnection();
$current_user = getCurrentUser($pdo); // Obter o usuário logado

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
    // Verificar se o disco pertence ao usuário logado
    $stmt = $pdo->prepare("SELECT user_id FROM disks WHERE id = ?");
    $stmt->execute([$disk_id]);
    $disk_owner = $stmt->fetchColumn();

    if (!$disk_owner) {
        echo json_encode(['success' => false, 'message' => 'Disco não encontrado.']);
        exit();
    }

    // Regra de permissão para exclusão: Apenas o dono do disco pode excluí-lo
    if ($disk_owner !== $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Você não tem permissão para excluir este disco.']);
        exit();
    }

    $pdo->beginTransaction();

    // Excluir imagem associada ao disco antes de remover os registros
    deleteDiskImage($pdo, $disk_id, $disk_owner); // Passa o ID do dono do disco

    // Excluir registros relacionados primeiro
    $stmt = $pdo->prepare("DELETE FROM disk_extras WHERE disk_id = ?");
    $stmt->execute([$disk_id]);
    $stmt = $pdo->prepare("DELETE FROM boxset_details WHERE disk_id = ?");
    $stmt->execute([$disk_id]);

    // Excluir o disco
    $stmt = $pdo->prepare("DELETE FROM disks WHERE id = ? AND user_id = ?");
    $stmt->execute([$disk_id, $disk_owner]); // Garante que apenas o dono correto pode deletar

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Disco excluído com sucesso']);

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Erro ao excluir disco: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
}
?>