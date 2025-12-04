<?php
// edit_disk.php
require_once 'includes/functions.php';

// Verificar se está logado
requireLogin();

$disk_id = (int)($_GET['id'] ?? 0);

if (empty($disk_id)) {
    setFlashMessage('error', 'Disco não encontrado.');
    header('Location: search_disks.php');
    exit();
}

$page_title = 'Editar Disco';
$css_file = 'register_disk.css';
$show_header = true;
$show_footer = false;

$error_message = '';
$success_message = '';

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Buscar disco com todos os detalhes
    $stmt = $pdo->prepare("
        SELECT d.*, 
               de.has_booklet, de.has_poster, de.has_photos, 
               de.has_extra_disk, de.has_lyrics, de.other_extras,
               bd.is_limited_edition, bd.edition_number, 
               bd.total_editions, bd.special_items
        FROM disks d 
        LEFT JOIN disk_extras de ON d.id = de.disk_id
        LEFT JOIN boxset_details bd ON d.id = bd.disk_id
        WHERE d.id = ? AND d.user_id = ?
    ");
    $stmt->execute([$disk_id, $_SESSION['user_id']]);
    $disk = $stmt->fetch();
    
    if (!$disk) {
        setFlashMessage('error', 'Disco não encontrado.');
        header('Location: search_disks.php');
        exit();
    }
    
    // Buscar países
    $countries = getCountriesByContinent($pdo);
    
} catch (Exception $e) {
    setFlashMessage('error', 'Erro ao carregar dados do disco.');
    header('Location: search_disks.php');
    exit();
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $error_message = 'Token de segurança inválido!';
    } else {
        // Dados básicos
        $type = sanitizeInput($_POST['type'] ?? '');
        $artist = sanitizeInput($_POST['artist'] ?? '');
        $album_name = sanitizeInput($_POST['album_name'] ?? '');
        $year = !empty($_POST['year']) ? (int)$_POST['year'] : null;
        $label = sanitizeInput($_POST['label'] ?? '');
        
        // Origem
        $is_imported = isset($_POST['is_imported']) && $_POST['is_imported'] === '1';
        $country_id = !empty($_POST['country_id']) ? (int)$_POST['country_id'] : null;
        
        // Edição e condição
        $edition = sanitizeInput($_POST['edition'] ?? 'primeira_edicao');
        $condition_disk = sanitizeInput($_POST['condition_disk'] ?? '');
        $condition_cover = sanitizeInput($_POST['condition_cover'] ?? '');
        $is_sealed = isset($_POST['is_sealed']);
        
        // Extras
        $has_booklet = isset($_POST['has_booklet']);
        $has_poster = isset($_POST['has_poster']);
        $has_photos = isset($_POST['has_photos']);
        $has_extra_disk = isset($_POST['has_extra_disk']);
        $has_lyrics = isset($_POST['has_lyrics']);
        $other_extras = sanitizeInput($_POST['other_extras'] ?? '');
        
        // BoxSet específico
        $is_limited_edition = isset($_POST['is_limited_edition']);
        $edition_number = !empty($_POST['edition_number']) ? (int)$_POST['edition_number'] : null;
        $total_editions = !empty($_POST['total_editions']) ? (int)$_POST['total_editions'] : null;
        $special_items = sanitizeInput($_POST['special_items'] ?? '');
        
        // Observações
        $observations = sanitizeInput($_POST['observations'] ?? '');
        
        // Validações
        if (empty($type) || empty($artist) || empty($album_name) || empty($condition_disk) || empty($condition_cover)) {
            $error_message = 'Por favor, preencha todos os campos obrigatórios!';
        } elseif (!in_array($type, ['CD', 'LP', 'BoxSet'])) {
            $error_message = 'Tipo de mídia inválido!';
        } elseif (!in_array($condition_disk, ['G', 'G+', 'VG', 'VG+', 'E', 'E+', 'Mint'])) {
            $error_message = 'Condição do disco inválida!';
        } elseif (!in_array($condition_cover, ['G', 'G+', 'VG', 'VG+', 'E', 'E+', 'Mint'])) {
            $error_message = 'Condição da capa inválida!';
        } elseif ($is_imported && empty($country_id)) {
            $error_message = 'Para discos importados, selecione o país de origem!';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Atualizar disco
                $stmt = $pdo->prepare("
                    UPDATE disks SET 
                        type = ?, artist = ?, album_name = ?, year = ?, label = ?, 
                        country_id = ?, is_imported = ?, edition = ?, condition_disk = ?, 
                        condition_cover = ?, is_sealed = ?, observations = ?, updated_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                
                $stmt->execute([
                    $type, $artist, $album_name, $year, $label,
                    $country_id, $is_imported, $edition, $condition_disk,
                    $condition_cover, $is_sealed, $observations, $disk_id, $_SESSION['user_id']
                ]);
                
                // Atualizar ou inserir extras
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
                    $disk_id, $has_booklet, $has_poster, $has_photos,
                    $has_extra_disk, $has_lyrics, $other_extras
                ]);
                
                // Se for BoxSet, atualizar detalhes específicos
                if ($type === 'BoxSet') {
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
                        $disk_id, $is_limited_edition, $edition_number,
                        $total_editions, $special_items
                    ]);
                } else {
                    // Remover detalhes de BoxSet se não for mais BoxSet
                    $stmt = $pdo->prepare("DELETE FROM boxset_details WHERE disk_id = ?");
                    $stmt->execute([$disk_id]);
                }
                
                $pdo->commit();
                
                setFlashMessage('success', 'Disco atualizado com sucesso!');
                header('Location: disk_details.php?id=' . $disk_id);
                exit();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = 'Erro ao atualizar disco. Tente novamente.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="page-background">
    <div class="vinyl-animation">
        <div class="vinyl vinyl-1"></div>
        <div class="vinyl vinyl-2"></div>
        <div class="vinyl vinyl-3"></div>
    </div>
</div>

<main class="main">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="dashboard.php">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <i class="fas fa-chevron-right"></i>
            <a href="search_disks.php">Pesquisar</a>
            <i class="fas fa-chevron-right"></i>
            <a href="disk_details.php?id=<?php echo $disk_id; ?>">
                <?php echo htmlspecialchars($disk['album_name']); ?>
            </a>
            <i class="fas fa-chevron-right"></i>
            <span>Editar</span>
        </nav>

        <!-- Header da Página -->
        <div class="page-header">
            <div class="page-title-container">
                <div class="page-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div>
                    <h1 class="page-title">Editar Disco</h1>
                    <p class="page-subtitle">Atualize as informações de "<?php echo htmlspecialchars($disk['album_name']); ?>"</p>
                </div>
            </div>
        </div>

        <!-- Formulário -->
        <div class="form-container">
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Erro!</strong>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" class="disk-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <!-- Tipo de Mídia -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-compact-disc"></i>
                            Tipo de Mídia
                        </h2>
                        <p class="section-description">Selecione o tipo do seu disco</p>
                    </div>
                    
                    <div class="media-type-selector">
                        <div class="media-option <?php echo $disk['type'] === 'CD' ? 'selected' : ''; ?>">
                            <input type="radio" id="type_cd" name="type" value="CD" required <?php echo $disk['type'] === 'CD' ? 'checked' : ''; ?>>
                            <label for="type_cd">
                                <div class="media-icon">
                                    <i class="fas fa-compact-disc"></i>
                                </div>
                                <div class="media-info">
                                    <h3>CD</h3>
                                    <p>Compact Disc tradicional</p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="media-option <?php echo $disk['type'] === 'LP' ? 'selected' : ''; ?>">
                            <input type="radio" id="type_lp" name="type" value="LP" required <?php echo $disk['type'] === 'LP' ? 'checked' : ''; ?>>
                            <label for="type_lp">
                                <div class="media-icon">
                                    <i class="fas fa-record-vinyl"></i>
                                </div>
                                <div class="media-info">
                                    <h3>LP</h3>
                                    <p>Long Play em vinil</p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="media-option <?php echo $disk['type'] === 'BoxSet' ? 'selected' : ''; ?>">
                            <input type="radio" id="type_boxset" name="type" value="BoxSet" required <?php echo $disk['type'] === 'BoxSet' ? 'checked' : ''; ?>>
                            <label for="type_boxset">
                                <div class="media-icon">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <div class="media-info">
                                    <h3>BoxSet</h3>
                                    <p>Coleção especial com múltiplos itens</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Informações Básicas -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-subtitle">
                            <i class="fas fa-info-circle"></i>
                            Informações Básicas
                        </h2>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="artist">
                                <i class="fas fa-microphone"></i>
                                Artista/Banda *
                            </label>
                            <input type="text" id="artist" name="artist" class="form-input" 
                                   placeholder="Nome do artista ou banda" required
                                   value="<?php echo htmlspecialchars($disk['artist']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="album_name">
                                <i class="fas fa-music"></i>
                                Nome do Álbum *
                            </label>
                            <input type="text" id="album_name" name="album_name" class="form-input" 
                                   placeholder="Título do álbum" required
                                   value="<?php echo htmlspecialchars($disk['album_name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="year">
                                <i class="fas fa-calendar"></i>
                                Ano de Lançamento
                            </label>
                            <input type="number" id="year" name="year" class="form-input" 
                                   placeholder="Ex: 1975" min="1900" max="<?php echo date('Y'); ?>"
                                   value="<?php echo $disk['year']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="label">
                                <i class="fas fa-building"></i>
                                Gravadora/Selo
                            </label>
                            <input type="text" id="label" name="label" class="form-input" 
                                   placeholder="Nome da gravadora"
                                   value="<?php echo htmlspecialchars($disk['label']); ?>">
                        </div>
                    </div>
                </div>

                <!-- Origem -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-subtitle">
                            <i class="fas fa-globe"></i>
                            Origem do Disco
                        </h2>
                    </div>
                    
                    <div class="origin-selector">
                        <label class="origin-option <?php echo !$disk['is_imported'] ? 'selected' : ''; ?>">
                            <input type="radio" id="origin_national" name="is_imported" value="0" required <?php echo !$disk['is_imported'] ? 'checked' : ''; ?>>
                            <div class="origin-content">
                                <div class="origin-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="origin-info">
                                    <h3>Nacional</h3>
                                    <p>Produzido no Brasil</p>
                                </div>
                            </div>
                        </label>
                        
                        <label class="origin-option <?php echo $disk['is_imported'] ? 'selected' : ''; ?>">
                            <input type="radio" id="origin_imported" name="is_imported" value="1" required <?php echo $disk['is_imported'] ? 'checked' : ''; ?>>
                            <div class="origin-content">
                                <div class="origin-icon">
                                    <i class="fas fa-plane"></i>
                                </div>
                                <div class="origin-info">
                                    <h3>Importado</h3>
                                    <p>Produzido em outro país</p>
                                </div>
                            </div>
                        </label>
                    </div>
                    
                    <div class="import-fields" id="import_fields" style="display: <?php echo $disk['is_imported'] ? 'block' : 'none'; ?>;">
                        <div class="form-group">
                            <label class="form-label" for="country_id">
                                <i class="fas fa-flag"></i>
                                País de Origem *
                            </label>
                            <select id="country_id" name="country_id" class="form-select">
                                <option value="">Selecione o país</option>
                                <?php foreach ($countries as $continent => $continent_countries): ?>
                                    <optgroup label="<?php echo htmlspecialchars($continent); ?>">
                                        <?php foreach ($continent_countries as $country): ?>
                                            <option value="<?php echo $country['id']; ?>" 
                                                    <?php echo $country['is_fake_prone'] ? 'data-fake-prone="true"' : ''; ?>
                                                    <?php echo $disk['country_id'] == $country['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($country['country_name']); ?>
                                                <?php echo $country['is_fake_prone'] ? ' ⚠️' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Edição -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-subtitle">
                            <i class="fas fa-certificate"></i>
                            Tipo de Edição
                        </h2>
                    </div>
                    
                    <div class="edition-selector">
                        <label class="edition-option <?php echo $disk['edition'] === 'primeira_edicao' ? 'selected' : ''; ?>">
                            <input type="radio" id="edition_first" name="edition" value="primeira_edicao" required <?php echo $disk['edition'] === 'primeira_edicao' ? 'checked' : ''; ?>>
                            <div class="edition-content">
                                <div class="edition-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="edition-info">
                                    <h3>Primeira Edição</h3>
                                    <p>Lançamento original</p>
                                </div>
                            </div>
                        </label>
                        
                        <label class="edition-option <?php echo $disk['edition'] === 'reedicao' ? 'selected' : ''; ?>">
                            <input type="radio" id="edition_reissue" name="edition" value="reedicao" required <?php echo $disk['edition'] === 'reedicao' ? 'checked' : ''; ?>>
                            <div class="edition-content">
                                <div class="edition-icon">
                                    <i class="fas fa-redo"></i>
                                </div>
                                <div class="edition-info">
                                    <h3>Reedição</h3>
                                    <p>Relançamento posterior</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Condição -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-subtitle">
                            <i class="fas fa-star-half-alt"></i>
                            Condição do Disco
                        </h2>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="condition_disk">
                                <i class="fas fa-compact-disc"></i>
                                Condição do Disco *
                            </label>
                            <select id="condition_disk" name="condition_disk" class="form-select" required>
                                <option value="">Selecione a condição</option>
                                <option value="Mint" <?php echo $disk['condition_disk'] === 'Mint' ? 'selected' : ''; ?>>Mint - Perfeito</option>
                                <option value="E+" <?php echo $disk['condition_disk'] === 'E+' ? 'selected' : ''; ?>>E+ - Excelente+</option>
                                <option value="E" <?php echo $disk['condition_disk'] === 'E' ? 'selected' : ''; ?>>E - Excelente</option>
                                <option value="VG+" <?php echo $disk['condition_disk'] === 'VG+' ? 'selected' : ''; ?>>VG+ - Muito Bom+</option>
                                <option value="VG" <?php echo $disk['condition_disk'] === 'VG' ? 'selected' : ''; ?>>VG - Muito Bom</option>
                                <option value="G+" <?php echo $disk['condition_disk'] === 'G+' ? 'selected' : ''; ?>>G+ - Bom+</option>
                                <option value="G" <?php echo $disk['condition_disk'] === 'G' ? 'selected' : ''; ?>>G - Bom</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="condition_cover">
                                <i class="fas fa-image"></i>
                                Condição da Capa *
                            </label>
                            <select id="condition_cover" name="condition_cover" class="form-select" required>
                                <option value="">Selecione a condição</option>
                                <option value="Mint" <?php echo $disk['condition_cover'] === 'Mint' ? 'selected' : ''; ?>>Mint - Perfeito</option>
                                <option value="E+" <?php echo $disk['condition_cover'] === 'E+' ? 'selected' : ''; ?>>E+ - Excelente+</option>
                                <option value="E" <?php echo $disk['condition_cover'] === 'E' ? 'selected' : ''; ?>>E - Excelente</option>
                                <option value="VG+" <?php echo $disk['condition_cover'] === 'VG+' ? 'selected' : ''; ?>>VG+ - Muito Bom+</option>
                                <option value="VG" <?php echo $disk['condition_cover'] === 'VG' ? 'selected' : ''; ?>>VG - Muito Bom</option>
                                <option value="G+" <?php echo $disk['condition_cover'] === 'G+' ? 'selected' : ''; ?>>G+ - Bom+</option>
                                <option value="G" <?php echo $disk['condition_cover'] === 'G' ? 'selected' : ''; ?>>G - Bom</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="sealed-option <?php echo $disk['is_sealed'] ? 'selected' : ''; ?>">
                        <input type="checkbox" id="is_sealed" name="is_sealed" <?php echo $disk['is_sealed'] ? 'checked' : ''; ?>>
                        <label for="is_sealed" class="sealed-label">
                            <div class="sealed-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div class="sealed-info">
                                <h3>Disco Lacrado</h3>
                                <p>O disco ainda está em sua embalagem original lacrada</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Itens Extras -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-subtitle">
                            <i class="fas fa-gift"></i>
                            Itens Extras
                        </h2>
                        <p class="section-description">Marque os itens extras que acompanham o disco</p>
                    </div>
                    
                    <div class="extras-grid">
                        <div class="extra-item <?php echo $disk['has_booklet'] ? 'selected' : ''; ?>">
                            <input type="checkbox" id="has_booklet" name="has_booklet" <?php echo $disk['has_booklet'] ? 'checked' : ''; ?>>
                            <label for="has_booklet" class="extra-content">
                                <i class="fas fa-book"></i>
                                <span>Encarte/Livreto</span>
                            </label>
                        </div>
                        
                        <div class="extra-item <?php echo $disk['has_poster'] ? 'selected' : ''; ?>">
                            <input type="checkbox" id="has_poster" name="has_poster" <?php echo $disk['has_poster'] ? 'checked' : ''; ?>>
                            <label for="has_poster" class="extra-content">
                                <i class="fas fa-image"></i>
                                <span>Poster</span>
                            </label>
                        </div>
                        
                        <div class="extra-item <?php echo $disk['has_photos'] ? 'selected' : ''; ?>">
                            <input type="checkbox" id="has_photos" name="has_photos" <?php echo $disk['has_photos'] ? 'checked' : ''; ?>>
                            <label for="has_photos" class="extra-content">
                                <i class="fas fa-camera"></i>
                                <span>Fotos</span>
                            </label>
                        </div>
                        
                        <div class="extra-item <?php echo $disk['has_extra_disk'] ? 'selected' : ''; ?>">
                            <input type="checkbox" id="has_extra_disk" name="has_extra_disk" <?php echo $disk['has_extra_disk'] ? 'checked' : ''; ?>>
                            <label for="has_extra_disk" class="extra-content">
                                <i class="fas fa-plus-circle"></i>
                                <span>Disco Extra</span>
                            </label>
                        </div>
                        
                        <div class="extra-item <?php echo $disk['has_lyrics'] ? 'selected' : ''; ?>">
                            <input type="checkbox" id="has_lyrics" name="has_lyrics" <?php echo $disk['has_lyrics'] ? 'checked' : ''; ?>>
                            <label for="has_lyrics" class="extra-content">
                                <i class="fas fa-file-alt"></i>
                                <span>Letras</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="other_extras">
                            <i class="fas fa-plus"></i>
                            Outros Itens Extras
                        </label>
                        <textarea id="other_extras" name="other_extras" class="form-textarea" 
                                  placeholder="Descreva outros itens extras que acompanham o disco..."><?php echo htmlspecialchars($disk['other_extras']); ?></textarea>
                    </div>
                </div>

                <!-- BoxSet Específico -->
                <div class="boxset-section" id="boxset_section" style="display: <?php echo $disk['type'] === 'BoxSet' ? 'block' : 'none'; ?>;">
                    <div class="section-header">
                        <h2 class="section-subtitle">
                            <i class="fas fa-crown"></i>
                            Detalhes do BoxSet
                        </h2>
                    </div>
                    
                    <div class="limited-edition-option <?php echo $disk['is_limited_edition'] ? 'selected' : ''; ?>">
                        <input type="checkbox" id="is_limited_edition" name="is_limited_edition" <?php echo $disk['is_limited_edition'] ? 'checked' : ''; ?>>
                        <label for="is_limited_edition" class="limited-label">
                            <div class="limited-icon">
                                <i class="fas fa-gem"></i>
                            </div>
                            <div class="limited-info">
                                <h3>Edição Limitada</h3>
                                <p>Este BoxSet é uma edição limitada numerada</p>
                            </div>
                        </label>
                    </div>
                    
                    <div class="limited-fields" id="limited_fields" style="display: <?php echo $disk['is_limited_edition'] ? 'block' : 'none'; ?>;">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="edition_number">
                                    <i class="fas fa-hashtag"></i>
                                    Número da Edição
                                </label>
                                <input type="number" id="edition_number" name="edition_number" class="form-input" 
                                       placeholder="Ex: 1234" min="1" value="<?php echo $disk['edition_number']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="total_editions">
                                    <i class="fas fa-list-ol"></i>
                                    Total de Edições
                                </label>
                                <input type="number" id="total_editions" name="total_editions" class="form-input" 
                                       placeholder="Ex: 5000" min="1" value="<?php echo $disk['total_editions']; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="special_items">
                            <i class="fas fa-star"></i>
                            Itens Especiais do BoxSet
                        </label>
                        <textarea id="special_items" name="special_items" class="form-textarea" 
                                  placeholder="Descreva os itens especiais incluídos no BoxSet..."><?php echo htmlspecialchars($disk['special_items']); ?></textarea>
                    </div>
                </div>

                <!-- Observações -->
                <div class="observations-section">
                    <div class="section-header">
                        <h2 class="section-subtitle">
                            <i class="fas fa-sticky-note"></i>
                            Observações Adicionais
                        </h2>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="observations">
                            <i class="fas fa-comment"></i>
                            Observações
                        </label>
                        <textarea id="observations" name="observations" class="form-textarea" 
                                  placeholder="Adicione observações sobre raridade, características especiais, histórico, etc..."><?php echo htmlspecialchars($disk['observations']); ?></textarea>
                    </div>
                </div>

                <!-- Navegação do Formulário -->
                <div class="form-navigation">
                    <a href="disk_details.php?id=<?php echo $disk_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Cancelar
                    </a>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
// Usar o mesmo JavaScript do register_disk.php
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/ocultar campos de importação
    const originRadios = document.querySelectorAll('input[name="is_imported"]');
    const importFields = document.getElementById('import_fields');
    const countrySelect = document.getElementById('country_id');
    
    originRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === '1') {
                importFields.style.display = 'block';
                countrySelect.required = true;
            } else {
                importFields.style.display = 'none';
                countrySelect.required = false;
                countrySelect.value = '';
            }
        });
    });
    
    // Mostrar/ocultar seção BoxSet
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const boxsetSection = document.getElementById('boxset_section');
    
    typeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'BoxSet') {
                boxsetSection.style.display = 'block';
            } else {
                boxsetSection.style.display = 'none';
            }
        });
    });
    
    // Mostrar/ocultar campos de edição limitada
    const limitedCheckbox = document.getElementById('is_limited_edition');
    const limitedFields = document.getElementById('limited_fields');
    
    if (limitedCheckbox) {
        limitedCheckbox.addEventListener('change', function() {
            if (this.checked) {
                limitedFields.style.display = 'block';
            } else {
                limitedFields.style.display = 'none';
            }
        });
    }
    
    // Adicionar classes visuais aos radio buttons
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Remover classe selected de todos os elementos do mesmo grupo
            document.querySelectorAll(`input[name="${this.name}"]`).forEach(r => {
                r.closest('.media-option, .origin-option, .edition-option')?.classList.remove('selected');
            });
            // Adicionar classe selected ao elemento atual
            this.closest('.media-option, .origin-option, .edition-option')?.classList.add('selected');
        });
    });
    
    // Adicionar classes visuais aos checkboxes
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const container = this.closest('.extra-item, .sealed-option, .limited-edition-option');
            if (container) {
                if (this.checked) {
                    container.classList.add('selected');
                } else {
                    container.classList.remove('selected');
                }
            }
        });
    });
});
</script>

<style>
/* Breadcrumb styles */
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 2rem;
    font-size: 0.9rem;
}

.breadcrumb a {
    color: var(--color-primary);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    transition: var(--transition);
}

.breadcrumb a:hover {
    color: var(--color-secondary);
}

.breadcrumb i {
    color: var(--color-text-secondary);
    font-size: 0.8rem;
}

.breadcrumb span {
    color: var(--color-text-secondary);
}

/* Adicionar indicador de edição */
.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, var(--color-warning) 0%, var(--color-secondary) 100%);
    border-radius: var(--border-radius-large) var(--border-radius-large) 0 0;
}

.page-header {
    position: relative;
}

.page-icon {
    background: linear-gradient(135deg, var(--color-warning) 0%, var(--color-secondary) 100%);
}

/* Destacar campos modificados */
.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    border-color: var(--color-warning);
    box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
}

/* Botão de salvar com destaque */
.btn-success {
    background: linear-gradient(135deg, var(--color-warning) 0%, #f57c00 100%);
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
}

.btn-success:hover {
    box-shadow: 0 8px 25px rgba(255, 152, 0, 0.4);
}
</style>

<?php include 'includes/footer.php'; ?>