<?php
// TrackBox/api/admin_actions.php
require_once '../includes/functions.php';

// Conectar ao banco de dados
require_once '../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

header('Content-Type: application/json');

// Verificar se a requisição é AJAX e POST
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso direto não permitido.']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Verificar se está logado e é administrador
if (!isLoggedIn() || !isCurrentUserAdmin($pdo)) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem realizar esta ação.']);
    exit();
}

// Verificar CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit();
}

$action = $_POST['action'] ?? '';
$user_id_target = (int)($_POST['user_id'] ?? 0);
$current_admin_id = $_SESSION['user_id'];

if (empty($user_id_target)) {
    echo json_encode(['success' => false, 'message' => 'ID de usuário inválido.']);
    exit();
}

// Não permitir que um administrador altere as próprias permissões ou se exclua
if ($user_id_target === $current_admin_id) {
    echo json_encode(['success' => false, 'message' => 'Você não pode alterar suas próprias permissões ou excluir sua própria conta através deste painel.']);
    exit();
}

try {
    switch ($action) {
        case 'toggle_admin':
            $result = toggleUserAdminStatus($pdo, $user_id_target, $current_admin_id);
            if ($result['success']) {
                $status_text = $result['new_status'] ? 'administrador' : 'usuário comum';
                echo json_encode(['success' => true, 'message' => 'Permissão alterada para ' . $status_text . '.', 'new_status' => $result['new_status']]);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
            break;

        case 'reset_share_code':
            $result = resetUserShareCode($pdo, $user_id_target, $current_admin_id);
            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'Novo código de compartilhamento gerado com sucesso.', 'new_share_code' => $result['new_share_code']]);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
            break;

        case 'delete_user':
            $result = deleteUser($pdo, $user_id_target, $current_admin_id);
            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso.']);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
            break;
    }
} catch (Exception $e) {
    error_log("Erro na ação administrativa: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
}