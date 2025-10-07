<?php
// includes/functions.php
session_start();

// Verificar se usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirecionar se não estiver logado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirecionar se já estiver logado
function requireLogout() {
    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Sanitizar dados de entrada
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validar email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hash da senha
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verificar senha
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Gerar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verificar token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Exibir mensagens flash
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Buscar países
function getCountries($pdo) {
    $stmt = $pdo->query("SELECT * FROM countries ORDER BY country_name");
    return $stmt->fetchAll();
}

// Buscar países por continente
function getCountriesByContinent($pdo) {
    $stmt = $pdo->query("SELECT * FROM countries ORDER BY continent, country_name");
    $countries = $stmt->fetchAll();
    
    $grouped = [];
    foreach ($countries as $country) {
        $grouped[$country['continent']][] = $country;
    }
    
    return $grouped;
}

// Contar discos do usuário
function getUserDiskCount($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM disks WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch()['total'];
}

// Buscar estatísticas do usuário
function getUserStats($pdo, $user_id) {
    $stats = [];
    
    // Total de discos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM disks WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['total_disks'] = $stmt->fetch()['total'];
    
    // Por tipo
    $stmt = $pdo->prepare("SELECT type, COUNT(*) as count FROM disks WHERE user_id = ? GROUP BY type");
    $stmt->execute([$user_id]);
    $types = $stmt->fetchAll();
    
    $stats['by_type'] = [];
    foreach ($types as $type) {
        $stats['by_type'][$type['type']] = $type['count'];
    }
    
    // Discos lacrados
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM disks WHERE user_id = ? AND is_sealed = 1");
    $stmt->execute([$user_id]);
    $stats['sealed_disks'] = $stmt->fetch()['total'];
    
    // Discos importados
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM disks WHERE user_id = ? AND is_imported = 1");
    $stmt->execute([$user_id]);
    $stats['imported_disks'] = $stmt->fetch()['total'];
    
    return $stats;
}

// Formatar condição do disco
function formatCondition($condition) {
    $conditions = [
        'G' => 'Bom',
        'G+' => 'Bom+',
        'VG' => 'Muito Bom',
        'VG+' => 'Muito Bom+',
        'E' => 'Excelente',
        'E+' => 'Excelente+',
        'Mint' => 'Perfeito'
    ];
    
    return $conditions[$condition] ?? $condition;
}

// Formatar tipo de edição
function formatEdition($edition) {
    return $edition === 'primeira_edicao' ? 'Primeira Edição' : 'Reedição';
}
?>