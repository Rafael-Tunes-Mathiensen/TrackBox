<?php
// register_disk.php
require_once 'includes/functions.php';

// Verificar se está logado
requireLogin();

$page_title = 'Cadastrar Disco';
$css_file = 'register_disk.css';
$show_header = true;
$show_footer = false;

$error_message = '';
$success_message = '';

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Buscar países
    $countries = getCountriesByContinent($pdo);
    
} catch (Exception $e) {
    $error_message = 'Erro ao carregar dados. Tente novamente.';
    $countries = [];
}

// Processar formulário
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
        
        // Processar imagem se fornecida
        $cover_image_url = null;
        $cover_image_source = null;

        if (!empty($_POST['cover_image_url']) && !empty($_POST['cover_image_source'])) {
            $cover_image_url = sanitizeInput($_POST['cover_image_url']);
            $cover_image_source = sanitizeInput($_POST['cover_image_source']);
            
            // Validar fonte da imagem
            if (!in_array($cover_image_source, ['upload', 'camera', 'api', 'url'])) {
                $cover_image_source = 'url';
            }
        }
        
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
                
                // Inserir disco
                $stmt = $pdo->prepare("
                    INSERT INTO disks (
                        user_id, type, artist, album_name, year, label, 
                        country_id, is_imported, edition, condition_disk, 
                        condition_cover, is_sealed, observations,
                        cover_image_url, cover_image_source
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $_SESSION['user_id'], $type, $artist, $album_name, $year, $label,
                    $country_id, $is_imported, $edition, $condition_disk,
                    $condition_cover, $is_sealed, $observations,
                    $cover_image_url, $cover_image_source
                ]);
                
                $disk_id = $pdo->lastInsertId();
                
                // Inserir extras
                $stmt = $pdo->prepare("
                    INSERT INTO disk_extras (
                        disk_id, has_booklet, has_poster, has_photos, 
                        has_extra_disk, has_lyrics, other_extras
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $disk_id, $has_booklet, $has_poster, $has_photos,
                    $has_extra_disk, $has_lyrics, $other_extras
                ]);
                
                // Se for BoxSet, inserir detalhes específicos
                if ($type === 'BoxSet') {
                    $stmt = $pdo->prepare("
                        INSERT INTO boxset_details (
                            disk_id, is_limited_edition, edition_number, 
                            total_editions, special_items
                        ) VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $disk_id, $is_limited_edition, $edition_number,
                        $total_editions, $special_items
                    ]);
                }
                
                $pdo->commit();
                
                setFlashMessage('success', 'Disco cadastrado com sucesso!');
                header('Location: disk_details.php?id=' . $disk_id);
                exit();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = 'Erro ao cadastrar disco. Tente novamente.';
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
        <!-- Header da Página -->
        <div class="page-header">
            <div class="page-title-container">
                <div class="page-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div>
                    <h1 class="page-title">Cadastrar Disco</h1>
                    <p class="page-subtitle">Adicione um novo item à sua coleção</p>
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
                        <div class="media-option">
                            <input type="radio" id="type_cd" name="type" value="CD" required>
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
                        
                        <div class="media-option">
                            <input type="radio" id="type_lp" name="type" value="LP" required>
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
                        
                        <div class="media-option">
                            <input type="radio" id="type_boxset" name="type" value="BoxSet" required>
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
                                   value="<?php echo htmlspecialchars($_POST['artist'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="album_name">
                                <i class="fas fa-music"></i>
                                Nome do Álbum *
                            </label>
                            <input type="text" id="album_name" name="album_name" class="form-input" 
                                   placeholder="Título do álbum" required
                                   value="<?php echo htmlspecialchars($_POST['album_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="year">
                                <i class="fas fa-calendar"></i>
                                Ano de Lançamento
                            </label>
                            <input type="number" id="year" name="year" class="form-input" 
                                   placeholder="Ex: 1975" min="1900" max="<?php echo date('Y'); ?>"
                                   value="<?php echo htmlspecialchars($_POST['year'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="label">
                                <i class="fas fa-building"></i>
                                Gravadora/Selo
                            </label>
                            <input type="text" id="label" name="label" class="form-input" 
                                   placeholder="Nome da gravadora"
                                   value="<?php echo htmlspecialchars($_POST['label'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Seção de Imagem -->
                <div class="form-section image-section">
                    <div class="section-header">
                        <h2 class="section-subtitle">
                            <i class="fas fa-image"></i>
                            Imagem do Disco
                        </h2>
                        <p class="section-description">Adicione uma capa para o seu disco</p>
                    </div>
                    
                    <div class="image-upload-container">
                        <!-- Preview da Imagem -->
                        <div class="image-preview" id="imagePreview">
                            <div class="image-placeholder">
                                <i class="fas fa-image"></i>
                                <p>Nenhuma imagem selecionada</p>
                            </div>
                        </div>
                        
                        <!-- Opções de Upload -->
                        <div class="upload-options">
                            <div class="upload-tabs">
                                <button type="button" class="upload-tab active" data-tab="search">
                                    <i class="fas fa-search"></i>
                                    Buscar Online
                                </button>
                                <button type="button" class="upload-tab" data-tab="upload">
                                    <i class="fas fa-upload"></i>
                                    Enviar Arquivo
                                </button>
                                <button type="button" class="upload-tab" data-tab="camera">
                                    <i class="fas fa-camera"></i>
                                    Tirar Foto
                                </button>
                                <button type="button" class="upload-tab" data-tab="url">
                                    <i class="fas fa-link"></i>
                                    URL da Imagem
                                </button>
                            </div>
                            
                            <!-- Busca Online -->
                            <div class="upload-content active" id="searchTab">
                                <div class="search-form">
                                    <p class="tab-description">
                                        <i class="fas fa-info-circle"></i>
                                        Buscaremos automaticamente a capa baseada no artista e álbum informados
                                    </p>
                                    <button type="button" class="btn btn-primary" id="searchCoverBtn">
                                        <i class="fas fa-search"></i>
                                        Buscar Capa Automaticamente
                                    </button>
                                    <div class="search-results" id="searchResults"></div>
                                </div>
                            </div>
                            
                            <!-- Upload de Arquivo -->
                            <div class="upload-content" id="uploadTab">
                                <div class="file-upload-area" id="fileUploadArea">
                                    <input type="file" id="imageFile" accept="image/*" style="display: none;">
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <p class="upload-text">
                                        <strong>Clique para selecionar</strong> ou arraste uma imagem aqui
                                    </p>
                                    <p class="upload-info">
                                        Formatos: JPEG, PNG, WebP • Máximo: 5MB
                                    </p>
                                </div>
                                <div class="upload-progress" id="uploadProgress" style="display: none;">
                                    <div class="progress-bar">
                                        <div class="progress-fill" id="progressFill"></div>
                                    </div>
                                    <p class="progress-text" id="progressText">Enviando...</p>
                                </div>
                            </div>
                            
                            <!-- Câmera -->
                            <div class="upload-content" id="cameraTab">
                                <div class="camera-container">
                                    <video id="cameraVideo" autoplay style="display: none;"></video>
                                    <canvas id="cameraCanvas" style="display: none;"></canvas>
                                    <div class="camera-placeholder" id="cameraPlaceholder">
                                        <i class="fas fa-camera"></i>
                                        <p>Clique para ativar a câmera</p>
                                    </div>
                                    <div class="camera-controls" id="cameraControls" style="display: none;">
                                        <button type="button" class="btn btn-primary" id="captureBtn">
                                            <i class="fas fa-camera"></i>
                                            Capturar Foto
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="stopCameraBtn">
                                            <i class="fas fa-stop"></i>
                                            Parar Câmera
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- URL da Imagem -->
                            <div class="upload-content" id="urlTab">
                                <div class="url-form">
                                    <div class="form-group">
                                        <label class="form-label" for="imageUrl">
                                            <i class="fas fa-link"></i>
                                            URL da Imagem
                                        </label>
                                        <input type="url" id="imageUrl" class="form-input" 
                                               placeholder="https://exemplo.com/imagem.jpg">
                                    </div>
                                    <button type="button" class="btn btn-primary" id="loadUrlBtn">
                                        <i class="fas fa-download"></i>
                                        Carregar Imagem
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campo hidden para armazenar URL da imagem -->
                    <input type="hidden" id="selectedImageUrl" name="cover_image_url">
                    <input type="hidden" id="selectedImageSource" name="cover_image_source">
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
                        <label class="origin-option">
                            <input type="radio" id="origin_national" name="is_imported" value="0" required>
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
                        
                        <label class="origin-option">
                            <input type="radio" id="origin_imported" name="is_imported" value="1" required>
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
                    
                    <div class="import-fields" id="import_fields" style="display: none;">
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
                                                    <?php echo $country['is_fake_prone'] ? 'data-fake-prone="true"' : ''; ?>>
                                                <?php echo htmlspecialchars($country['country_name']); ?>
                                                <?php echo $country['is_fake_prone'] ? ' ⚠️' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <div id="fake_warning" class="alert alert-warning" style="display: none; margin-top: 1rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>
                                    <strong>Atenção!</strong>
                                    <p>Este país é conhecido por produzir discos falsificados. Verifique a autenticidade do seu disco.</p>
                                </div>
                            </div>
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
                        <label class="edition-option">
                            <input type="radio" id="edition_first" name="edition" value="primeira_edicao" required>
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
                        
                        <label class="edition-option">
                            <input type="radio" id="edition_reissue" name="edition" value="reedicao" required>
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
                                <option value="Mint">Mint - Perfeito</option>
                                <option value="E+">E+ - Excelente+</option>
                                <option value="E">E - Excelente</option>
                                <option value="VG+">VG+ - Muito Bom+</option>
                                <option value="VG">VG - Muito Bom</option>
                                <option value="G+">G+ - Bom+</option>
                                <option value="G">G - Bom</option>
                            </select>
                            <div class="condition-info">
                                <p><strong>Mint:</strong> Estado perfeito, sem uso aparente</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="condition_cover">
                                <i class="fas fa-image"></i>
                                Condição da Capa *
                            </label>
                            <select id="condition_cover" name="condition_cover" class="form-select" required>
                                <option value="">Selecione a condição</option>
                                <option value="Mint">Mint - Perfeito</option>
                                <option value="E+">E+ - Excelente+</option>
                                <option value="E">E - Excelente</option>
                                <option value="VG+">VG+ - Muito Bom+</option>
                                <option value="VG">VG - Muito Bom</option>
                                <option value="G+">G+ - Bom+</option>
                                <option value="G">G - Bom</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="sealed-option">
                        <input type="checkbox" id="is_sealed" name="is_sealed">
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
                        <div class="extra-item">
                            <input type="checkbox" id="has_booklet" name="has_booklet">
                            <label for="has_booklet" class="extra-content">
                                <i class="fas fa-book"></i>
                                <span>Encarte/Livreto</span>
                            </label>
                        </div>
                        
                        <div class="extra-item">
                            <input type="checkbox" id="has_poster" name="has_poster">
                            <label for="has_poster" class="extra-content">
                                <i class="fas fa-image"></i>
                                <span>Poster</span>
                            </label>
                        </div>
                        
                        <div class="extra-item">
                            <input type="checkbox" id="has_photos" name="has_photos">
                            <label for="has_photos" class="extra-content">
                                <i class="fas fa-camera"></i>
                                <span>Fotos</span>
                            </label>
                        </div>
                        
                        <div class="extra-item">
                            <input type="checkbox" id="has_extra_disk" name="has_extra_disk">
                            <label for="has_extra_disk" class="extra-content">
                                <i class="fas fa-plus-circle"></i>
                                <span>Disco Extra</span>
                            </label>
                        </div>
                        
                        <div class="extra-item">
                            <input type="checkbox" id="has_lyrics" name="has_lyrics">
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
                                  placeholder="Descreva outros itens extras que acompanham o disco..."><?php echo htmlspecialchars($_POST['other_extras'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- BoxSet Específico -->
                <div class="boxset-section" id="boxset_section" style="display: none;">
                    <div class="section-header">
                        <h2 class="section-subtitle">
                            <i class="fas fa-crown"></i>
                            Detalhes do BoxSet
                        </h2>
                    </div>
                    
                    <div class="limited-edition-option">
                        <input type="checkbox" id="is_limited_edition" name="is_limited_edition">
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
                    
                    <div class="limited-fields" id="limited_fields" style="display: none;">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="edition_number">
                                    <i class="fas fa-hashtag"></i>
                                    Número da Edição
                                </label>
                                <input type="number" id="edition_number" name="edition_number" class="form-input" 
                                       placeholder="Ex: 1234" min="1">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="total_editions">
                                    <i class="fas fa-list-ol"></i>
                                    Total de Edições
                                </label>
                                <input type="number" id="total_editions" name="total_editions" class="form-input" 
                                       placeholder="Ex: 5000" min="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="special_items">
                            <i class="fas fa-star"></i>
                            Itens Especiais do BoxSet
                        </label>
                        <textarea id="special_items" name="special_items" class="form-textarea" 
                                  placeholder="Descreva os itens especiais incluídos no BoxSet..."><?php echo htmlspecialchars($_POST['special_items'] ?? ''); ?></textarea>
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
                                  placeholder="Adicione observações sobre raridade, características especiais, histórico, etc..."><?php echo htmlspecialchars($_POST['observations'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Navegação do Formulário -->
                <div class="form-navigation">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Cancelar
                    </a>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        Cadastrar Disco
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
// JavaScript mínimo apenas para UX (sem dependências externas)
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/ocultar campos de importação
    const originRadios = document.querySelectorAll('input[name="is_imported"]');
    const importFields = document.getElementById('import_fields');
    const countrySelect = document.getElementById('country_id');
    const fakeWarning = document.getElementById('fake_warning');
    
    originRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === '1') {
                importFields.style.display = 'block';
                countrySelect.required = true;
            } else {
                importFields.style.display = 'none';
                countrySelect.required = false;
                countrySelect.value = '';
                fakeWarning.style.display = 'none';
            }
        });
    });
    
    // Aviso para países propensos a falsificação
    countrySelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.dataset.fakeProne === 'true') {
            fakeWarning.style.display = 'block';
        } else {
            fakeWarning.style.display = 'none';
        }
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

    // Image Upload Functionality
    const uploadTabs = document.querySelectorAll('.upload-tab');
    const uploadContents = document.querySelectorAll('.upload-content');
    const imagePreview = document.getElementById('imagePreview');
    const fileUploadArea = document.getElementById('fileUploadArea');
    const imageFile = document.getElementById('imageFile');
    const searchCoverBtn = document.getElementById('searchCoverBtn');
    const cameraPlaceholder = document.getElementById('cameraPlaceholder');
    const cameraVideo = document.getElementById('cameraVideo');
    const cameraCanvas = document.getElementById('cameraCanvas');
    const captureBtn = document.getElementById('captureBtn');
    const stopCameraBtn = document.getElementById('stopCameraBtn');
    const cameraControls = document.getElementById('cameraControls');
    const loadUrlBtn = document.getElementById('loadUrlBtn');
    const imageUrl = document.getElementById('imageUrl');
    
    let currentStream = null;
    let selectedImage = null;
    
    // Tabs functionality
    uploadTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // Update active tab
            uploadTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Update active content
            uploadContents.forEach(content => {
                content.classList.remove('active');
                if (content.id === targetTab + 'Tab') {
                    content.classList.add('active');
                }
            });
            
            // Stop camera if switching away
            if (targetTab !== 'camera' && currentStream) {
                stopCamera();
            }
        });
    });
    
    // File upload
    if (fileUploadArea && imageFile) {
        fileUploadArea.addEventListener('click', () => imageFile.click());
        
        // Drag and drop
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        });
        
        imageFile.addEventListener('change', function() {
            if (this.files.length > 0) {
                handleFileSelect(this.files[0]);
            }
        });
    }
    
    // Search cover
    if (searchCoverBtn) {
        searchCoverBtn.addEventListener('click', function() {
            const artist = document.getElementById('artist').value.trim();
            const album = document.getElementById('album_name').value.trim();
            
            if (!artist || !album) {
                showNotification('Preencha o artista e nome do álbum primeiro', 'error');
                return;
            }
            
            searchCover(artist, album);
        });
    }
    
    // Camera functionality
    if (cameraPlaceholder) cameraPlaceholder.addEventListener('click', startCamera);
    if (captureBtn) captureBtn.addEventListener('click', capturePhoto);
    if (stopCameraBtn) stopCameraBtn.addEventListener('click', stopCamera);
    
    // URL functionality
    if (loadUrlBtn) loadUrlBtn.addEventListener('click', loadImageFromUrl);
    
    function handleFileSelect(file) {
        if (!validateFile(file)) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            showImagePreview(e.target.result);
            selectedImage = {
                file: file,
                source: 'upload',
                url: e.target.result
            };
        };
        reader.readAsDataURL(file);
    }
    
    function validateFile(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!allowedTypes.includes(file.type)) {
            showNotification('Tipo de arquivo não permitido. Use JPEG, PNG ou WebP', 'error');
            return false;
        }
        
        if (file.size > maxSize) {
            showNotification('Arquivo muito grande. Máximo: 5MB', 'error');
            return false;
        }
        
        return true;
    }
    
    function showImagePreview(src) {
        if (imagePreview) {
            imagePreview.innerHTML = `<img src="${src}" alt="Preview">`;
        }
    }
    
    function searchCover(artist, album) {
        const btn = searchCoverBtn;
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
        btn.disabled = true;
        
        // Fazer chamada para buscar capa
        fetch('image_search.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=search&artist=${encodeURIComponent(artist)}&album=${encodeURIComponent(album)}`
        })
        .then(response => response.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            if (data.success && data.image_url) {
                showImagePreview(data.image_url);
                selectedImage = {
                    url: data.image_url,
                    source: 'api'
                };
                showNotification('Capa encontrada!', 'success');
            } else {
                showNotification('Nenhuma capa encontrada. Tente outro método.', 'warning');
            }
        })
        .catch(error => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            showNotification('Erro ao buscar capa', 'error');
            console.error('Erro:', error);
        });
    }
    
    async function startCamera() {
        try {
            currentStream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                } 
            });
            
            if (cameraVideo) {
                cameraVideo.srcObject = currentStream;
                cameraPlaceholder.style.display = 'none';
                cameraVideo.style.display = 'block';
                if (cameraControls) cameraControls.style.display = 'flex';
            }
            
        } catch (error) {
            showNotification('Erro ao acessar câmera: ' + error.message, 'error');
        }
    }
    
    function capturePhoto() {
        if (!cameraVideo || !cameraCanvas) return;
        
        const context = cameraCanvas.getContext('2d');
        cameraCanvas.width = cameraVideo.videoWidth;
        cameraCanvas.height = cameraVideo.videoHeight;
        
        context.drawImage(cameraVideo, 0, 0);
        
        cameraCanvas.toBlob(function(blob) {
            const reader = new FileReader();
            reader.onload = function(e) {
                showImagePreview(e.target.result);
                selectedImage = {
                    blob: blob,
                    source: 'camera',
                    url: e.target.result
                };
                showNotification('Foto capturada!', 'success');
            };
            reader.readAsDataURL(blob);
        }, 'image/jpeg', 0.8);
        
        stopCamera();
    }
    
    function stopCamera() {
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
            currentStream = null;
        }
        
        if (cameraVideo && cameraControls && cameraPlaceholder) {
            cameraVideo.style.display = 'none';
            cameraControls.style.display = 'none';
            cameraPlaceholder.style.display = 'block';
        }
    }
    
    function loadImageFromUrl() {
        if (!imageUrl) return;
        
        const url = imageUrl.value.trim();
        
        if (!url) {
            showNotification('Digite uma URL válida', 'error');
            return;
        }
        
        if (!isValidImageUrl(url)) {
            showNotification('URL deve ser uma imagem válida', 'error');
            return;
        }
        
        const btn = loadUrlBtn;
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Carregando...';
        btn.disabled = true;
        
        const img = new Image();
        img.onload = function() {
            showImagePreview(url);
            selectedImage = {
                url: url,
                source: 'url'
            };
            
            btn.innerHTML = originalText;
            btn.disabled = false;
            showNotification('Imagem carregada!', 'success');
        };
        
        img.onerror = function() {
            btn.innerHTML = originalText;
            btn.disabled = false;
            showNotification('Erro ao carregar imagem da URL', 'error');
        };
        
        img.src = url;
    }
    
    function isValidImageUrl(url) {
        return /\.(jpg|jpeg|png|webp|gif)(\?.*)?$/i.test(url) || 
               url.includes('cloudinary.com') || 
               url.includes('imgur.com') ||
               url.includes('lastfm.freetls.fastly.net');
    }
    
    // Update hidden fields when form is submitted
    const diskForm = document.querySelector('.disk-form');
    if (diskForm) {
        diskForm.addEventListener('submit', function(e) {
            if (selectedImage) {
                const urlField = document.getElementById('selectedImageUrl');
                const sourceField = document.getElementById('selectedImageSource');
                
                if (urlField) urlField.value = selectedImage.url || '';
                if (sourceField) sourceField.value = selectedImage.source || '';
            }
        });
    }
    
    function showNotification(message, type) {
        // Criar notificação
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        // Adicionar ao body
        document.body.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Remover após 3 segundos
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
});
</script>

<style>
/* Image Upload Section */
.image-section {
    background: linear-gradient(135deg, rgba(33, 150, 243, 0.05) 0%, rgba(103, 58, 183, 0.05) 100%);
    border: 1px solid rgba(33, 150, 243, 0.2);
}

.image-upload-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    align-items: start;
}

.image-preview {
    background: var(--color-background);
    border: 2px dashed rgba(33, 150, 243, 0.3);
    border-radius: var(--border-radius);
    padding: 2rem;
    text-align: center;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.image-preview img {
    max-width: 100%;
    max-height: 100%;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-medium);
}

.image-placeholder {
    color: var(--color-text-secondary);
}

.image-placeholder i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: rgba(33, 150, 243, 0.5);
}

.upload-options {
    background: var(--color-background);
    border-radius: var(--border-radius);
    overflow: hidden;
    border: 1px solid rgba(33, 150, 243, 0.2);
}

.upload-tabs {
    display: flex;
    background: var(--color-surface);
    border-bottom: 1px solid rgba(33, 150, 243, 0.2);
}

.upload-tab {
    flex: 1;
    padding: 1rem;
    background: none;
    border: none;
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.upload-tab:hover,
.upload-tab.active {
    background: rgba(33, 150, 243, 0.1);
    color: #2196F3;
}

.upload-tab i {
    font-size: 1.2rem;
}

.upload-content {
    display: none;
    padding: 2rem;
}

.upload-content.active {
    display: block;
}

.tab-description {
    background: rgba(33, 150, 243, 0.1);
    border: 1px solid rgba(33, 150, 243, 0.2);
    border-radius: var(--border-radius);
    padding: 1rem;
    margin-bottom: 1.5rem;
    color: #2196F3;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9rem;
}

.file-upload-area {
    border: 2px dashed rgba(33, 150, 243, 0.3);
    border-radius: var(--border-radius);
    padding: 3rem 2rem;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
}

.file-upload-area:hover {
    border-color: #2196F3;
    background: rgba(33, 150, 243, 0.05);
}

.file-upload-area.dragover {
    border-color: #2196F3;
    background: rgba(33, 150, 243, 0.1);
    transform: scale(1.02);
}

.upload-icon {
    font-size: 3rem;
    color: #2196F3;
    margin-bottom: 1rem;
}

.upload-text {
    color: var(--color-text);
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
}

.upload-info {
    color: var(--color-text-secondary);
    font-size: 0.9rem;
}

.upload-progress {
    text-align: center;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: rgba(33, 150, 243, 0.2);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2196F3 0%, #03A9F4 100%);
    width: 0%;
    transition: width 0.3s ease;
}

.progress-text {
    color: #2196F3;
    font-weight: 500;
}

.camera-container {
    text-align: center;
}

.camera-placeholder {
    border: 2px dashed rgba(33, 150, 243, 0.3);
    border-radius: var(--border-radius);
    padding: 3rem 2rem;
    cursor: pointer;
    transition: var(--transition);
}

.camera-placeholder:hover {
    border-color: #2196F3;
    background: rgba(33, 150, 243, 0.05);
}

.camera-placeholder i {
    font-size: 3rem;
    color: #2196F3;
    margin-bottom: 1rem;
}

#cameraVideo {
    width: 100%;
    max-width: 400px;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
}

.camera-controls {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.search-results {
    margin-top: 1.5rem;
}

.search-result-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid rgba(33, 150, 243, 0.2);
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    cursor: pointer;
    transition: var(--transition);
}

.search-result-item:hover {
    background: rgba(33, 150, 243, 0.05);
    border-color: #2196F3;
}

.search-result-item img {
    width: 60px;
    height: 60px;
    border-radius: var(--border-radius);
    object-fit: cover;
}

.search-result-info {
    flex: 1;
}

.search-result-info h4 {
    color: var(--color-text);
    margin-bottom: 0.25rem;
}

.search-result-info p {
    color: var(--color-text-secondary);
    font-size: 0.9rem;
}

/* Notification styles */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--color-surface);
    border: 1px solid rgba(255, 107, 53, 0.2);
    border-radius: var(--border-radius);
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    z-index: 10001;
    transform: translateX(400px);
    transition: transform 0.3s ease;
    box-shadow: var(--shadow-large);
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    border-color: var(--color-success);
    color: var(--color-success);
}

.notification-error {
    border-color: var(--color-error);
    color: var(--color-error);
}

.notification-warning {
    border-color: var(--color-warning);
    color: var(--color-warning);
}

.notification i {
    font-size: 1.2rem;
}

/* Responsive */
@media (max-width: 768px) {
    .image-upload-container {
        grid-template-columns: 1fr;
    }
    
    .upload-tabs {
        flex-wrap: wrap;
    }
    
    .upload-tab {
        flex: 1 1 50%;
    }
    
    .camera-controls {
        flex-direction: column;
        align-items: center;
    }
}

/* Loading states */
.loading .upload-tab {
    pointer-events: none;
    opacity: 0.6;
}

.btn.loading {
    pointer-events: none;
    opacity: 0.6;
}

.btn.loading i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<?php include 'includes/footer.php'; ?>