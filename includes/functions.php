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

// NOVAS FUNÇÕES PARA EDIÇÃO E EXCLUSÃO

// Buscar disco por ID
function getDiskById($pdo, $disk_id, $user_id) {
    $stmt = $pdo->prepare("
        SELECT d.*, c.country_name, c.continent, c.is_fake_prone,
               de.has_booklet, de.has_poster, de.has_photos, 
               de.has_extra_disk, de.has_lyrics, de.other_extras,
               bd.is_limited_edition, bd.edition_number, 
               bd.total_editions, bd.special_items
        FROM disks d 
        LEFT JOIN countries c ON d.country_id = c.id 
        LEFT JOIN disk_extras de ON d.id = de.disk_id
        LEFT JOIN boxset_details bd ON d.id = bd.disk_id
        WHERE d.id = ? AND d.user_id = ?
    ");
    $stmt->execute([$disk_id, $user_id]);
    return $stmt->fetch();
}

// Atualizar disco
function updateDisk($pdo, $disk_id, $user_id, $data) {
    try {
        $pdo->beginTransaction();
        
        // Atualizar disco principal
        $stmt = $pdo->prepare("
            UPDATE disks SET 
                type = ?, artist = ?, album_name = ?, year = ?, label = ?, 
                country_id = ?, is_imported = ?, edition = ?, condition_disk = ?, 
                condition_cover = ?, is_sealed = ?, observations = ?, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([
            $data['type'], $data['artist'], $data['album_name'], $data['year'], $data['label'],
            $data['country_id'], $data['is_imported'], $data['edition'], $data['condition_disk'],
            $data['condition_cover'], $data['is_sealed'], $data['observations'], $disk_id, $user_id
        ]);
        
        // Atualizar extras
        $stmt = $pdo->prepare("
            INSERT INTO disk_extras (
                disk_id, has_booklet, has_poster, has_photos, 
                has_extra_disk, has_lyrics, other_extras
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                has_booklet = VALUES(has_booklet),
                has_poster = VALUES(has_poster),
                has_photos = VALUES(has_photos),
                has_extra_disk = VALUES(has_extra_disk),
                has_lyrics = VALUES(has_lyrics),
                other_extras = VALUES(other_extras)
        ");
        
        $stmt->execute([
            $disk_id, $data['has_booklet'], $data['has_poster'], $data['has_photos'],
            $data['has_extra_disk'], $data['has_lyrics'], $data['other_extras']
        ]);
        
        // Atualizar BoxSet se necessário
        if ($data['type'] === 'BoxSet') {
            $stmt = $pdo->prepare("
                INSERT INTO boxset_details (
                    disk_id, is_limited_edition, edition_number, 
                    total_editions, special_items
                ) VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    is_limited_edition = VALUES(is_limited_edition),
                    edition_number = VALUES(edition_number),
                    total_editions = VALUES(total_editions),
                    special_items = VALUES(special_items)
            ");
            
            $stmt->execute([
                $disk_id, $data['is_limited_edition'], $data['edition_number'],
                $data['total_editions'], $data['special_items']
            ]);
        } else {
            // Remover detalhes de BoxSet se não for mais BoxSet
            $stmt = $pdo->prepare("DELETE FROM boxset_details WHERE disk_id = ?");
            $stmt->execute([$disk_id]);
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao atualizar disco: " . $e->getMessage());
        return false;
    }
}

// Excluir disco
function deleteDisk($pdo, $disk_id, $user_id) {
    try {
        $pdo->beginTransaction();
        
        // Verificar se o disco pertence ao usuário
        $stmt = $pdo->prepare("SELECT id FROM disks WHERE id = ? AND user_id = ?");
        $stmt->execute([$disk_id, $user_id]);
        
        if (!$stmt->fetch()) {
            return false;
        }
        
        // Excluir registros relacionados primeiro
        $stmt = $pdo->prepare("DELETE FROM disk_extras WHERE disk_id = ?");
        $stmt->execute([$disk_id]);
        
        $stmt = $pdo->prepare("DELETE FROM boxset_details WHERE disk_id = ?");
        $stmt->execute([$disk_id]);
        
        // Excluir o disco
        $stmt = $pdo->prepare("DELETE FROM disks WHERE id = ? AND user_id = ?");
        $stmt->execute([$disk_id, $user_id]);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao excluir disco: " . $e->getMessage());
        return false;
    }
}

// Buscar discos com filtros avançados
function searchDisks($pdo, $user_id, $filters = [], $page = 1, $per_page = 12) {
    $where_conditions = ['d.user_id = ?'];
    $params = [$user_id];
    
    // Aplicar filtros
    if (!empty($filters['search'])) {
        $where_conditions[] = '(d.artist LIKE ? OR d.album_name LIKE ?)';
        $params[] = "%{$filters['search']}%";
        $params[] = "%{$filters['search']}%";
    }
    
    if (!empty($filters['type'])) {
        $where_conditions[] = 'd.type = ?';
        $params[] = $filters['type'];
    }
    
    if (!empty($filters['condition'])) {
        $where_conditions[] = 'd.condition_disk = ?';
        $params[] = $filters['condition'];
    }
    
    if (!empty($filters['country_id'])) {
        $where_conditions[] = 'd.country_id = ?';
        $params[] = $filters['country_id'];
    }
    
    if (isset($filters['is_sealed']) && $filters['is_sealed']) {
        $where_conditions[] = 'd.is_sealed = 1';
    }
    
    if (isset($filters['is_imported']) && $filters['is_imported']) {
        $where_conditions[] = 'd.is_imported = 1';
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    $offset = ($page - 1) * $per_page;
    
    // Buscar discos
    $stmt = $pdo->prepare("
        SELECT d.*, c.country_name 
        FROM disks d 
        LEFT JOIN countries c ON d.country_id = c.id 
        WHERE $where_clause 
        ORDER BY d.created_at DESC 
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $disks = $stmt->fetchAll();
    
    // Contar total
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM disks d 
        WHERE $where_clause
    ");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    return [
        'disks' => $disks,
        'total' => $total,
        'total_pages' => ceil($total / $per_page)
    ];
}

// Validar dados do disco
function validateDiskData($data) {
    $errors = [];
    
    if (empty($data['type']) || !in_array($data['type'], ['CD', 'LP', 'BoxSet'])) {
        $errors[] = 'Tipo de mídia inválido';
    }
    
    if (empty($data['artist'])) {
        $errors[] = 'Artista é obrigatório';
    }
    
    if (empty($data['album_name'])) {
        $errors[] = 'Nome do álbum é obrigatório';
    }
    
    if (empty($data['condition_disk']) || !in_array($data['condition_disk'], ['G', 'G+', 'VG', 'VG+', 'E', 'E+', 'Mint'])) {
        $errors[] = 'Condição do disco inválida';
    }
    
    if (empty($data['condition_cover']) || !in_array($data['condition_cover'], ['G', 'G+', 'VG', 'VG+', 'E', 'E+', 'Mint'])) {
        $errors[] = 'Condição da capa inválida';
    }
    
    if ($data['is_imported'] && empty($data['country_id'])) {
        $errors[] = 'Para discos importados, selecione o país de origem';
    }
    
    return $errors;
}

// Log de atividades (opcional)
function logActivity($pdo, $user_id, $action, $details = '') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, details, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $action, $details]);
    } catch (Exception $e) {
        error_log("Erro ao registrar atividade: " . $e->getMessage());
    }
}

// Backup de disco antes da exclusão
function backupDisk($pdo, $disk_id) {
    try {
        $disk = getDiskById($pdo, $disk_id, $_SESSION['user_id']);
        if ($disk) {
            $backup_data = json_encode($disk);
            $stmt = $pdo->prepare("
                INSERT INTO deleted_disks_backup (user_id, disk_data, deleted_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], $backup_data]);
        }
    } catch (Exception $e) {
        error_log("Erro ao fazer backup do disco: " . $e->getMessage());
    }
}
?>