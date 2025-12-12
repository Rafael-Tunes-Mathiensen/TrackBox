<?php
require_once '../includes/functions.php';

// Verificar se está logado
requireLogin();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $query = trim($input['query'] ?? '');
    
    if (strlen($query) < 2) {
        throw new Exception('Query muito curta');
    }
    
    require_once '../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Buscar artistas únicos do usuário atual
    $stmt = $pdo->prepare("
        SELECT DISTINCT artist 
        FROM disks 
        WHERE user_id = ? AND artist LIKE ? 
        ORDER BY artist ASC 
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id'], '%' . $query . '%']);
    
    $suggestions = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $suggestions[] = $row['artist'];
    }
    
    echo json_encode([
        'success' => true,
        'suggestions' => $suggestions
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>