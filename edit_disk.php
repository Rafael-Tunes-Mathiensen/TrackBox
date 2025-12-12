<?php
// edit_disk.php
require_once 'includes/functions.php';
require_once 'config/discogs_config.php';

// Verificar se está logado
requireLogin();

// Conectar ao banco para buscar países
require_once 'config/database.php';
$database = new Database();
$pdo = $database->getConnection();
$current_user = getCurrentUser($pdo);

$disk_id = (int)($_GET['id'] ?? 0);
if (empty($disk_id)) {
    setFlashMessage('error', 'Disco não encontrado.');
    header('Location: search_disks.php');
    exit();
}

// Obter dados do disco, incluindo image_path e is_favorite
$disk = getDiskById($pdo, $disk_id, $_SESSION['user_id']);
if (!$disk) {
    setFlashMessage('error', 'Disco não encontrado ou acesso negado.');
    header('Location: search_disks.php');
    exit();
}

// Verificar permissão: Somente o dono do disco pode editar
if ($disk['user_id'] !== $_SESSION['user_id']) {
    setFlashMessage('error', 'Você não tem permissão para editar este disco.');
    header('Location: disk_details.php?id=' . $disk_id);
    exit();
}

$page_title = 'Editar Disco';
$css_file = 'register_disk.css'; // Usa o mesmo CSS de registro
$show_header = true;
$show_footer = false;

$error_message = '';
$success_message = '';

try {
    // Buscar países
    $countries = getCountriesByContinent($pdo);
} catch (Exception $e) {
    setFlashMessage('error', 'Erro ao carregar dados do disco: ' . $e->getMessage());
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
        $is_favorite = isset($_POST['is_favorite']);

        // Extras
        $has_booklet = isset($_POST['has_booklet']);
        $has_poster = isset($_POST['has_poster']);
        $has_photos = isset($_POST['has_photos']);
        $has_extra_disk = isset($_POST['has_extra_disk']);
        $has_lyrics = isset($_POST['has_lyrics']);
        $has_obi = isset($_POST['has_obi']); // NOVO: Captura do campo OBI
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
            $new_image_path = null;
            $keep_existing_image = $_POST['keep_existing_image'] ?? 'true';

            try {
                // Lidar com upload de imagem
                if (isset($_FILES['disk_image']) && $_FILES['disk_image']['error'] === UPLOAD_ERR_OK) {
                    $new_image_path = uploadImage($_FILES['disk_image']);
                }

                // Preparar dados para updateDisk
                $update_data = [
                    'type' => $type,
                    'artist' => $artist,
                    'album_name' => $album_name,
                    'year' => $year,
                    'label' => $label,
                    'country_id' => $country_id,
                    'is_imported' => $is_imported,
                    'edition' => $edition,
                    'condition_disk' => $condition_disk,
                    'condition_cover' => $condition_cover,
                    'is_sealed' => $is_sealed,
                    'is_favorite' => $is_favorite,
                    'observations' => $observations,
                    'new_image_path' => $new_image_path,
                    'keep_existing_image' => $keep_existing_image,
                    'has_booklet' => $has_booklet,
                    'has_poster' => $has_poster,
                    'has_photos' => $has_photos,
                    'has_extra_disk' => $has_extra_disk,
                    'has_lyrics' => $has_lyrics,
                    'has_obi' => $has_obi, // NOVO: Campo OBI para update
                    'other_extras' => $other_extras,
                    'is_limited_edition' => $is_limited_edition,
                    'edition_number' => $edition_number,
                    'total_editions' => $total_editions,
                    'special_items' => $special_items
                ];

                if (updateDisk($pdo, $disk_id, $_SESSION['user_id'], $update_data)) {
                    setFlashMessage('success', 'Disco atualizado com sucesso!');
                    header('Location: disk_details.php?id=' . $disk_id);
                    exit();
                } else {
                    $error_message = 'Erro ao atualizar disco. Tente novamente.';
                    if ($new_image_path && file_exists($new_image_path)) {
                        unlink($new_image_path);
                    }
                }
            } catch (Exception $e) {
                error_log("Erro ao processar edição: " . $e->getMessage());
                if (empty($error_message)) {
                    $error_message = 'Erro ao atualizar disco. Tente novamente. Detalhes: ' . $e->getMessage();
                }
                if ($new_image_path && file_exists($new_image_path)) {
                    unlink($new_image_path);
                }
            }
        }
    }
}

// Recarrega os dados do disco após uma tentativa de POST falha ou para exibição inicial
$disk = getDiskById($pdo, $disk_id, $_SESSION['user_id']);

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
            <a href="dashboard.php"> <i class="fas fa-home"></i> Dashboard </a>
            <i class="fas fa-chevron-right"></i>
            <a href="search_disks.php">Pesquisar</a>
            <i class="fas fa-chevron-right"></i>
            <a href="disk_details.php?id=<?php echo $disk_id; ?>"> <?php echo htmlspecialchars($disk['album_name']); ?> </a>
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
            <form method="POST" class="disk-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <!-- Tipo de Mídia -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-title"> <i class="fas fa-compact-disc"></i> Tipo de Mídia </h2>
                        <p class="section-description">Selecione o tipo do seu disco</p>
                    </div>
                    <div class="media-type-selector">
                        <div class="media-option <?php echo $disk['type'] === 'CD' ? 'selected' : ''; ?>">
                            <input type="radio" id="type_cd" name="type" value="CD"
                                required <?php echo $disk['type'] === 'CD' ? 'checked' : ''; ?>>
                            <label for="type_cd">
                                <div class="media-icon"> <i class="fas fa-compact-disc"></i> </div>
                                <div class="media-info">
                                    <h3>CD</h3>
                                    <p>Compact Disc tradicional</p>
                                </div>
                            </label>
                        </div>
                        <div class="media-option <?php echo $disk['type'] === 'LP' ? 'selected' : ''; ?>">
                            <input type="radio" id="type_lp" name="type" value="LP"
                                required <?php echo $disk['type'] === 'LP' ? 'checked' : ''; ?>>
                            <label for="type_lp">
                                <div class="media-icon"> <i class="fas fa-record-vinyl"></i> </div>
                                <div class="media-info">
                                    <h3>LP</h3>
                                    <p>Long Play em vinil</p>
                                </div>
                            </label>
                        </div>
                        <div class="media-option <?php echo $disk['type'] === 'BoxSet' ? 'selected' : ''; ?>">
                            <input type="radio" id="type_boxset" name="type" value="BoxSet"
                                required <?php echo $disk['type'] === 'BoxSet' ? 'checked' : ''; ?>>
                            <label for="type_boxset">
                                <div class="media-icon"> <i class="fas fa-box-open"></i> </div>
                                <div class="media-info">
                                    <h3>BoxSet</h3>
                                    <p>Coleção especial com múltiplos itens</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Busca Discogs -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-title"> <i class="fas fa-search"></i> Buscar no Discogs </h2>
                        <p class="section-description">Encontre automaticamente as informações atualizadas do seu disco</p>
                    </div>
                    <div class="discogs-search-container">
                        <div class="search-input-group">
                            <input type="text" id="discogs_search" class="form-input"
                                placeholder="Digite o nome do artista e álbum..." value="<?php echo htmlspecialchars($disk['artist'] . ' ' . $disk['album_name']); ?>">
                            <button type="button" id="search_discogs_btn" class="btn btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                        <div id="discogs_loading" class="loading-spinner" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Buscando no Discogs...
                        </div>
                        <div id="discogs_results" class="discogs-results" style="display: none;">
                            <h3>Resultados encontrados:</h3>
                            <div id="discogs_results_list" class="results-list"></div>
                            <div id="discogs_pagination" class="pagination-controls"></div>
                        </div>
                    </div>
                </div>

                <!-- Informações Básicas -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-subtitle"> <i class="fas fa-info-circle"></i> Informações Básicas </h2>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="artist"> <i class="fas fa-microphone"></i> Artista/Banda * </label>
                            <div class="autocomplete-container">
                                <input type="text" id="artist" name="artist"
                                    class="form-input autocomplete-input" placeholder="Nome do artista ou banda" required
                                    value="<?php echo htmlspecialchars($disk['artist']); ?>" autocomplete="off">
                                <div id="artist_suggestions" class="autocomplete-suggestions"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="album_name"> <i class="fas fa-music"></i> Nome do Álbum * </label>
                            <input type="text" id="album_name" name="album_name" class="form-input"
                                placeholder="Título do álbum" required value="<?php echo htmlspecialchars($disk['album_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="year"> <i class="fas fa-calendar"></i> Ano de Lançamento </label>
                            <input type="number" id="year" name="year" class="form-input"
                                placeholder="Ex: 1975" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo $disk['year']; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="label"> <i class="fas fa-building"></i> Gravadora/Selo </label>
                            <div class="autocomplete-container">
                                <input type="text" id="label" name="label"
                                    class="form-input autocomplete-input" placeholder="Nome da gravadora"
                                    value="<?php echo htmlspecialchars($disk['label']); ?>" autocomplete="off">
                                <div id="label_suggestions" class="autocomplete-suggestions"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campo para upload de imagem e preview -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-subtitle"> <i class="fas fa-image"></i> Capa do Disco </h2>
                        <p class="section-description">Atualize a imagem para a capa do seu disco.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="disk_image"> <i class="fas fa-upload"></i> Selecionar Nova Imagem </label>
                        <input type="file" id="disk_image" name="disk_image" class="form-input" accept="image/*">
                        <div id="image_preview" class="image-preview" style="display: <?php echo $disk['image_path'] ? 'flex' : 'none'; ?>;">
                            <img src="<?php echo $disk['image_path'] ? htmlspecialchars($disk['image_path']) : '#'; ?>" alt="Prévia da imagem" />
                            <input type="hidden" name="keep_existing_image" id="keep_existing_image" value="true">
                            <button type="button" class="remove-image-btn"><i class="fas fa-times"></i> Remover Imagem</button>
                        </div>
                    </div>
                </div>

                <!-- Origem -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-subtitle"> <i class="fas fa-globe"></i> Origem do Disco </h2>
                    </div>
                    <div class="origin-selector">
                        <label class="origin-option <?php echo !$disk['is_imported'] ? 'selected' : ''; ?>">
                            <input type="radio" id="origin_national" name="is_imported" value="0" required <?php echo !$disk['is_imported'] ? 'checked' : ''; ?>>
                            <div class="origin-content">
                                <div class="origin-icon"> <i class="fas fa-home"></i> </div>
                                <div class="origin-info">
                                    <h3>Nacional</h3>
                                    <p>Produzido no Brasil</p>
                                </div>
                            </div>
                        </label>
                        <label class="origin-option <?php echo $disk['is_imported'] ? 'selected' : ''; ?>">
                            <input type="radio" id="origin_imported" name="is_imported" value="1" required <?php echo $disk['is_imported'] ? 'checked' : ''; ?>>
                            <div class="origin-content">
                                <div class="origin-icon"> <i class="fas fa-plane"></i> </div>
                                <div class="origin-info">
                                    <h3>Importado</h3>
                                    <p>Produzido em outro país</p>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div class="import-fields" id="import_fields" style="display: <?php echo $disk['is_imported'] ? 'block' : 'none'; ?>;">
                        <div class="form-group">
                            <label class="form-label" for="country_id"> <i class="fas fa-flag"></i> País de Origem * </label>
                            <select id="country_id" name="country_id" class="form-select">
                                <option value="">Selecione o país</option>
                                <?php foreach ($countries as $continent => $continent_countries): ?>
                                    <optgroup label="<?php echo htmlspecialchars($continent); ?>">
                                        <?php foreach ($continent_countries as $country): ?>
                                            <option value="<?php echo $country['id']; ?>" <?php echo $country['is_fake_prone'] ? 'data-fake-prone="true"' : ''; ?> <?php echo $disk['country_id'] == $country['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($country['country_name']); ?> <?php echo $country['is_fake_prone'] ? ' ■■' : ''; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <div id="fake_warning" class="alert alert-warning" style="display: <?php echo ($disk['country_id'] && $disk['is_fake_prone']) ? 'block' : 'none'; ?>;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>
                                    <strong>Atenção!</strong>
                                    <p>Este país é conhecido por produzir discos falsificados. Verifique a autenticidade.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edição -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-subtitle"> <i class="fas fa-certificate"></i> Tipo de Edição </h2>
                    </div>
                    <div class="edition-selector">
                        <label class="edition-option <?php echo $disk['edition'] === 'primeira_edicao' ? 'selected' : ''; ?>">
                            <input type="radio" id="edition_first" name="edition" value="primeira_edicao" required <?php echo $disk['edition'] === 'primeira_edicao' ? 'checked' : ''; ?>>
                            <div class="edition-content">
                                <div class="edition-icon"> <i class="fas fa-star"></i> </div>
                                <div class="edition-info">
                                    <h3>Primeira Edição</h3>
                                    <p>Lançamento original</p>
                                </div>
                            </div>
                        </label>
                        <label class="edition-option <?php echo $disk['edition'] === 'reedicao' ? 'selected' : ''; ?>">
                            <input type="radio" id="edition_reissue" name="edition" value="reedicao" required <?php echo $disk['edition'] === 'reedicao' ? 'checked' : ''; ?>>
                            <div class="edition-content">
                                <div class="edition-icon"> <i class="fas fa-redo"></i> </div>
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
                        <h2 class="section-subtitle"> <i class="fas fa-star-half-alt"></i> Condição do Disco </h2>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="condition_disk"> <i class="fas fa-compact-disc"></i> Condição do Disco * </label>
                            <select id="condition_disk" name="condition_disk" class="form-select" required>
                                <option value="">Selecione a condição</option>
                                <option value="Mint" <?php echo $disk['condition_disk'] === 'Mint' ? 'selected' : ''; ?>>Mint</option>
                                <option value="E+" <?php echo $disk['condition_disk'] === 'E+' ? 'selected' : ''; ?>>E+</option>
                                <option value="E" <?php echo $disk['condition_disk'] === 'E' ? 'selected' : ''; ?>>E</option>
                                <option value="VG+" <?php echo $disk['condition_disk'] === 'VG+' ? 'selected' : ''; ?>>VG+</option>
                                <option value="VG" <?php echo $disk['condition_disk'] === 'VG' ? 'selected' : ''; ?>>VG</option>
                                <option value="G+" <?php echo $disk['condition_disk'] === 'G+' ? 'selected' : ''; ?>>G+</option>
                                <option value="G" <?php echo $disk['condition_disk'] === 'G' ? 'selected' : ''; ?>>G</option>
                            </select>
                            <div class="condition-info">
                                <p><strong>Mint:</strong> Estado perfeito, sem uso aparente</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="condition_cover"> <i class="fas fa-image"></i> Condição da Capa * </label>
                            <select id="condition_cover" name="condition_cover" class="form-select" required>
                                <option value="">Selecione a condição</option>
                                <option value="Mint" <?php echo $disk['condition_cover'] === 'Mint' ? 'selected' : ''; ?>>Mint</option>
                                <option value="E+" <?php echo $disk['condition_cover'] === 'E+' ? 'selected' : ''; ?>>E+</option>
                                <option value="E" <?php echo $disk['condition_cover'] === 'E' ? 'selected' : ''; ?>>E</option>
                                <option value="VG+" <?php echo $disk['condition_cover'] === 'VG+' ? 'selected' : ''; ?>>VG+</option>
                                <option value="VG" <?php echo $disk['condition_cover'] === 'VG' ? 'selected' : ''; ?>>VG</option>
                                <option value="G+" <?php echo $disk['condition_cover'] === 'G+' ? 'selected' : ''; ?>>G+</option>
                                <option value="G" <?php echo $disk['condition_cover'] === 'G' ? 'selected' : ''; ?>>G</option>
                            </select>
                        </div>
                    </div>
                    <div class="sealed-option <?php echo $disk['is_sealed'] ? 'selected' : ''; ?>">
                        <input type="checkbox" id="is_sealed" name="is_sealed" <?php echo $disk['is_sealed'] ? 'checked' : ''; ?>>
                        <label for="is_sealed" class="sealed-label">
                            <div class="sealed-icon"> <i class="fas fa-lock"></i> </div>
                            <div class="sealed-info">
                                <h3>Disco Lacrado</h3>
                                <p>O disco ainda está em sua embalagem original lacrada</p>
                            </div>
                        </label>
                    </div>
                    <!-- Opção para marcar como favorito -->
                    <div class="favorite-option <?php echo $disk['is_favorite'] ? 'selected' : ''; ?>">
                        <input type="checkbox" id="is_favorite" name="is_favorite" <?php echo $disk['is_favorite'] ? 'checked' : ''; ?>>
                        <label for="is_favorite" class="favorite-label">
                            <div class="favorite-icon"> <i class="fas fa-star"></i> </div>
                            <div class="favorite-info">
                                <h3>Marcar como Favorito</h3>
                                <p>Destaque este disco na sua coleção</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Itens Extras -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-subtitle"> <i class="fas fa-gift"></i> Itens Extras </h2>
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
                        <!-- NOVO CAMPO: OBI -->
                        <div class="extra-item <?php echo $disk['has_obi'] ? 'selected' : ''; ?>">
                            <input type="checkbox" id="has_obi" name="has_obi" <?php echo $disk['has_obi'] ? 'checked' : ''; ?>>
                            <label for="has_obi" class="extra-content">
                                <i class="fas fa-book-reader"></i> <!-- Ícone de sua preferência -->
                                <span>OBI (Faixa)</span>
                            </label>
                        </div>
                        <!-- FIM NOVO CAMPO OBI -->
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="other_extras"> <i class="fas fa-plus"></i> Outros Itens Extras </label>
                        <textarea id="other_extras" name="other_extras" class="form-textarea"
                            placeholder="Descreva outros itens extras que acompanham o disco..."><?php echo htmlspecialchars($disk['other_extras']); ?></textarea>
                    </div>
                </div>

                <!-- BoxSet Específico -->
                <div class="boxset-section" id="boxset_section" style="display: <?php echo $disk['type'] === 'BoxSet' ? 'block' : 'none'; ?>;">
                    <div class="section-header">
                        <h2 class="section-subtitle"> <i class="fas fa-crown"></i> Detalhes do BoxSet </h2>
                    </div>
                    <div class="limited-edition-option <?php echo $disk['is_limited_edition'] ? 'selected' : ''; ?>">
                        <input type="checkbox" id="is_limited_edition" name="is_limited_edition" <?php echo $disk['is_limited_edition'] ? 'checked' : ''; ?>>
                        <label for="is_limited_edition" class="limited-label">
                            <div class="limited-icon"> <i class="fas fa-gem"></i> </div>
                            <div class="limited-info">
                                <h3>Edição Limitada</h3>
                                <p>Este BoxSet é uma edição limitada numerada</p>
                            </div>
                        </label>
                    </div>
                    <div class="limited-fields" id="limited_fields" style="display: <?php echo $disk['is_limited_edition'] ? 'block' : 'none'; ?>;">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="edition_number"> <i class="fas fa-hashtag"></i> Número da Edição </label>
                                <input type="number" id="edition_number" name="edition_number" class="form-input"
                                    placeholder="Ex: 1234" min="1" value="<?php echo $disk['edition_number']; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="total_editions"> <i class="fas fa-list-ol"></i> Total de Edições </label>
                                <input type="number" id="total_editions" name="total_editions" class="form-input"
                                    placeholder="Ex: 5000" min="1" value="<?php echo $disk['total_editions']; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="special_items"> <i class="fas fa-star"></i> Itens Especiais do BoxSet </label>
                        <textarea id="special_items" name="special_items" class="form-textarea"
                            placeholder="Descreva os itens especiais incluídos no BoxSet..."><?php echo htmlspecialchars($disk['special_items']); ?></textarea>
                    </div>
                </div>

                <!-- Observações -->
                <div class="observations-section">
                    <div class="section-header">
                        <h2 class="section-subtitle"> <i class="fas fa-sticky-note"></i> Observações Adicionais </h2>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="observations"> <i class="fas fa-comment"></i> Observações </label>
                        <textarea id="observations" name="observations" class="form-textarea"
                            placeholder="Adicione observações sobre raridade, características especiais, etc."><?php echo htmlspecialchars($disk['observations']); ?></textarea>
                    </div>
                </div>

                <!-- Navegação do Formulário -->
                <div class="form-navigation">
                    <a href="disk_details.php?id=<?php echo $disk_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variáveis para busca Discogs
        let currentDiscogsPage = 1;
        let currentDiscogsQuery = '';
        let selectedDiscogsData = null;
        let isSearching = false;

        // Elementos da busca Discogs
        const discogsSearch = document.getElementById('discogs_search');
        const searchDiscogsBtn = document.getElementById('search_discogs_btn');
        const discogsLoading = document.getElementById('discogs_loading');
        const discogsResults = document.getElementById('discogs_results');
        const discogsResultsList = document.getElementById('discogs_results_list');
        const discogsPagination = document.getElementById('discogs_pagination');

        // Variáveis para autocomplete
        let autocompleteTimeout = null;
        let currentFocus = -1;

        // Event listeners para Discogs
        if (searchDiscogsBtn) {
            searchDiscogsBtn.addEventListener('click', function() {
                const query = discogsSearch.value.trim();
                if (query && !isSearching) {
                    searchDiscogs(query, 1);
                }
            });
        }
        if (discogsSearch) {
            discogsSearch.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const query = this.value.trim();
                    if (query && !isSearching) {
                        searchDiscogs(query, 1);
                    }
                }
            });
        }

        // Configurar autocomplete para artista
        setupAutocomplete('artist', 'artist_suggestions',
            'ajax/autocomplete_artists.php');
        // Configurar autocomplete para gravadora
        setupAutocomplete('label', 'label_suggestions', 'ajax/autocomplete_labels.php');

        // Função para configurar autocomplete
        function setupAutocomplete(inputId, suggestionsId, endpoint) {
            const input = document.getElementById(inputId);
            const suggestionsDiv = document.getElementById(suggestionsId);
            if (!input || !suggestionsDiv) return;

            input.addEventListener('input', function() {
                const query = this.value.trim();
                // Limpar timeout anterior
                if (autocompleteTimeout) {
                    clearTimeout(autocompleteTimeout);
                }
                // Ocultar sugestões se query muito curta
                if (query.length < 2) {
                    suggestionsDiv.style.display = 'none';
                    return;
                }
                // Debounce para evitar muitas requisições
                autocompleteTimeout = setTimeout(() => {
                    fetchSuggestions(query, endpoint, suggestionsDiv, input);
                }, 300);
            });

            // Ocultar sugestões ao clicar fora
            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                    suggestionsDiv.style.display = 'none';
                    currentFocus = -1;
                }
            });

            // Navegação por teclado
            input.addEventListener('keydown', function(e) {
                const suggestions = suggestionsDiv.querySelectorAll('.suggestion-item');
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    currentFocus++;
                    if (currentFocus >= suggestions.length) currentFocus = 0;
                    setActiveSuggestion(suggestions);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    currentFocus--;
                    if (currentFocus < 0) currentFocus = suggestions.length - 1;
                    setActiveSuggestion(suggestions);
                } else if (e.key === 'Enter') {
                    if (currentFocus > -1 && suggestions[currentFocus]) {
                        e.preventDefault();
                        suggestions[currentFocus].click();
                    }
                } else if (e.key === 'Escape') {
                    suggestionsDiv.style.display = 'none';
                    currentFocus = -1;
                }
            });
        }

        // Função para buscar sugestões
        async function fetchSuggestions(query, endpoint, suggestionsDiv,
            input) {
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ query: query })
                });
                if (!response.ok) {
                    throw new Error('Erro na requisição');
                }
                const data = await response.json();
                if (data.success && data.suggestions.length > 0) {
                    displaySuggestions(data.suggestions, suggestionsDiv, input);
                } else {
                    suggestionsDiv.style.display = 'none';
                }
            } catch (error) {
                console.error('Erro ao buscar sugestões:', error);
                suggestionsDiv.style.display = 'none';
            }
        }

        // Função para exibir sugestões
        function displaySuggestions(suggestions, suggestionsDiv, input) {
            suggestionsDiv.innerHTML = '';
            currentFocus = -1;
            suggestions.forEach((suggestion,
                index) => {
                const div = document.createElement('div');
                div.className =
                    'suggestion-item';
                div.textContent = suggestion;
                div.addEventListener('click',
                    function() {
                        input.value = suggestion;
                        suggestionsDiv.style.display = 'none';
                        input.focus();
                        // Adicionar classe de auto-preenchido
                        input.classList.add('auto-filled');
                    });
                suggestionsDiv.appendChild(div);
            });
            suggestionsDiv.style.display = 'block';
        }

        // Função para definir sugestão ativa
        function setActiveSuggestion(suggestions) {
            suggestions.forEach((suggestion, index) => {
                suggestion.classList.toggle('active', index === currentFocus);
            });
        }

        // Função para buscar no Discogs
        async function searchDiscogs(query, page = 1) {
            if (isSearching)
                return;
            currentDiscogsQuery = query;
            currentDiscogsPage = page;
            isSearching = true;

            // Mostrar loading
            discogsLoading.style.display = 'block';
            discogsResults.style.display = 'none';
            searchDiscogsBtn.disabled = true;
            searchDiscogsBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';

            try {
                const response = await fetch('ajax/discogs_search.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        query: query,
                        type: getSelectedMediaType(),
                        page: page
                    })
                });
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const data = await response.json();
                if (data.success) {
                    displayDiscogsResults(data.results, data.pagination);
                } else {
                    throw new Error(data.error || 'Erro desconhecido ao buscar no Discogs');
                }
            } catch (error) {
                console.error('Erro na busca Discogs:', error);
                // Mostrar erro específico
                let errorMessage = 'Erro ao buscar no Discogs: ';
                if (error.message.includes('401')) {
                    errorMessage += 'Problema de autenticação. Verifique as credenciais da API.';
                } else if (error.message.includes('429')) {
                    errorMessage += 'Muitas requisições. Aguarde alguns segundos e tente novamente.';
                } else if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
                    errorMessage += 'Problema de conexão. Verifique sua internet.';
                } else {
                    errorMessage += error.message;
                }
                showNotification(errorMessage, 'error');
            } finally {
                discogsLoading.style.display = 'none';
                searchDiscogsBtn.disabled = false;
                searchDiscogsBtn.innerHTML = '<i class="fas fa-search"></i> Buscar';
                isSearching = false;
            }
        }

        // Função para obter o tipo de mídia selecionado
        function getSelectedMediaType() {
            const typeRadios = document.querySelectorAll('input[name="type"]');
            for (const radio of typeRadios) {
                if (radio.checked) {
                    return radio.value;
                }
            }
            return '';
        }

        // Função para exibir resultados do Discogs
        function displayDiscogsResults(results, pagination) {
            discogsResultsList.innerHTML = '';
            if (results.length === 0) {
                discogsResultsList.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <p>Nenhum resultado encontrado para "${currentDiscogsQuery}"</p>
                        <small>Tente termos de busca diferentes ou verifique a ortografia</small>
                    </div>
                `;
                discogsResults.style.display = 'block';
                return;
            }

            results.forEach(result => {
                const resultElement = createDiscogsResultElement(result);
                discogsResultsList.appendChild(resultElement);
            });

            // Criar paginação
            createDiscogsPagination(pagination);
            discogsResults.style.display = 'block';
        }

        // Função para criar elemento de resultado
        function createDiscogsResultElement(result) {
            const div = document.createElement('div');
            div.className = 'discogs-result-item';

            const formats = result.formats && result.formats.length > 0 ? result.formats.map(f => f.name).join(', ') : result.type;
            const year = result.year ? ` (${result.year})` : '';
            const label = result.label ? ` • ${result.label}` : '';
            const country = result.country_name ? ` • ${result.country_name}` : '';
            const catno = result.catno ? ` • Cat: ${result.catno}` : '';

            // Escapar dados para JSON
            const escapedResult = JSON.stringify(result).replace(/"/g, '&quot;').replace(/'/g, '&#39;');

            div.innerHTML = `
                <div class="result-content">
                    <img src="${result.image_url || 'assets/images/no-cover.png'}" alt="Capa" class="result-image"
                        onerror="this.src='assets/images/no-cover.png'" loading="lazy">
                    <div class="result-info">
                        <div class="result-artist">${escapeHtml(result.artist)}</div>
                        <div class="result-title">${escapeHtml(result.album_name)}</div>
                        <div class="result-details">
                            <span>${escapeHtml(formats)}${year}${escapeHtml(label)}${escapeHtml(country)}${escapeHtml(catno)}</span>
                        </div>
                        <div class="result-formats">
                            ${result.genres ? result.genres.map(g => `<span
class="format-tag">${escapeHtml(g)}</span>`).join('') : ''} ${result.styles ?
result.styles.slice(0, 3).map(s => `<span class="format-tag
style-tag">${escapeHtml(s)}</span>`).join('') : ''}
                        </div>
                    </div>
                </div>
                <button type="button" class="use-discogs-btn" onclick="useDiscogsData('${escapedResult}')">
                    <i class="fas fa-check"></i> Atualizar com estes dados
                </button>
            `;
            return div;
        }

        // Função para escapar HTML
        function escapeHtml(text) {
            if (!text)
                return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Função para criar paginação
        function createDiscogsPagination(pagination) {
            const {
                page,
                pages,
                items
            } = pagination;
            if (pages <= 1) {
                discogsPagination.innerHTML = '';
                return;
            }
            discogsPagination.innerHTML = `
                <button type="button" class="pagination-btn" ${page <= 1 ? 'disabled' : ''} onclick="searchDiscogs('${currentDiscogsQuery}', ${page
                - 1})">
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
                <span class="pagination-info">Página ${page} de ${pages} (${items} resultados)</span>
                <button type="button" class="pagination-btn" ${page >= pages ? 'disabled' : ''} onclick="searchDiscogs('${currentDiscogsQuery}', ${page + 1})">
                    Próxima <i class="fas fa-chevron-right"></i>
                </button>
            `;
        }

        // Função global para usar dados do Discogs
        window.useDiscogsData = async function(resultString) {
            try {
                const result =
                    JSON.parse(resultString.replace(/&quot;/g, '"').replace(/&#39;/g, "'"));
                selectedDiscogsData = result;

                // Confirmar antes de sobrescrever dados existentes
                const confirmMessage = 'Tem certeza de que deseja atualizar os dados do disco com as informações do Discogs? Isso sobrescreverá os dados atuais.';
                if (!confirm(confirmMessage)) {
                    return;
                }

                // Preencher campos do formulário
                fillFormWithDiscogsData(result);

                // Baixar e definir imagem se disponível
                if (result.image_url) {
                    await downloadAndSetImage(result.image_url);
                }

                // Marcar campos como preenchidos automaticamente
                markAutoFilledFields();

                // Ocultar resultados
                discogsResults.style.display = 'none';

                // Scroll para o formulário
                document.querySelector('.form-section').scrollIntoView({
                    behavior: 'smooth'
                });

                // Mostrar notificação de sucesso
                showNotification('■ Dados atualizados automaticamente! Verifique e ajuste conforme necessário antes de salvar.', 'success');

            } catch (error) {
                console.error('Erro ao processar dados do Discogs:', error);
                showNotification('Erro ao processar dados do Discogs: ' + error.message, 'error');
            }
        };

        // Função para mostrar notificações
        function showNotification(message, type = 'info') {
            // Remover notificação existente
            const existing = document.querySelector('.notification');
            if (existing) {
                existing.remove();
            }

            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span>${message}</span>
                    <button type="button" class="notification-close" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            document.body.appendChild(notification);

            // Auto remover após 7 segundos (mais tempo para edição)
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 7000);
        }

        // Função para preencher formulário com dados do Discogs
        function fillFormWithDiscogsData(data) {
            // Tipo de mídia
            if (data.type) {
                const typeRadio = document.querySelector(`input[name="type"][value="${data.type}"]`);
                if (typeRadio) {
                    typeRadio.checked = true;
                    typeRadio.dispatchEvent(new Event('change'));
                }
            }

            // Informações básicas
            if (data.artist) {
                const artistField = document.getElementById('artist');
                artistField.value = data.artist;
                artistField.dispatchEvent(new Event('input'));
            }
            if (data.album_name) {
                const albumField = document.getElementById('album_name');
                albumField.value = data.album_name;
                albumField.dispatchEvent(new Event('input'));
            }
            if (data.year) {
                const yearField = document.getElementById('year');
                yearField.value = data.year;
                yearField.dispatchEvent(new Event('input'));
            }
            if (data.label) {
                const labelField = document.getElementById('label');
                labelField.value = data.label;
                labelField.dispatchEvent(new Event('input'));
            }

            // Origem
            if (data.is_imported) {
                const importedRadio = document.getElementById('origin_imported');
                importedRadio.checked = true;
                importedRadio.dispatchEvent(new Event('change'));
                if (data.country_id) {
                    setTimeout(() => {
                        const countrySelect = document.getElementById('country_id');
                        countrySelect.value = data.country_id;
                        countrySelect.dispatchEvent(new Event('change'));
                    }, 100);
                }
            } else {
                const nationalRadio = document.getElementById('origin_national');
                nationalRadio.checked = true;
                nationalRadio.dispatchEvent(new Event('change'));
            }

            // Observações com informações adicionais do Discogs
            const observations = document.getElementById('observations');
            let obsText = observations.value;
            const additionalInfo = [];
            if (data.genres && data.genres.length > 0) {
                additionalInfo.push('Gêneros: ' + data.genres.join(', '));
            }
            if (data.styles && data.styles.length > 0) {
                additionalInfo.push('Estilos: ' + data.styles.join(', '));
            }
            if (data.catno) {
                additionalInfo.push('Catálogo: ' + data.catno);
            }
            if (data.barcode) {
                additionalInfo.push('Código de barras: ' + data.barcode);
            }
            if (data.discogs_url) {
                additionalInfo.push('Discogs: ' + data.discogs_url);
            }

            if (additionalInfo.length > 0) {
                const newInfo = additionalInfo.join('\n');
                // Verificar se já existem informações do Discogs
                const discogsPattern = /(Gêneros:|Estilos:|Catálogo:|Código de barras:|Discogs:)/;
                if (discogsPattern.test(obsText)) {
                    // Substituir informações existentes do Discogs
                    const lines = obsText.split('\n');
                    const filteredLines = lines.filter(line => !discogsPattern.test(line));
                    obsText = filteredLines.join('\n').trim();
                }
                obsText = obsText ? obsText + '\n\n' + newInfo : newInfo;
                observations.value = obsText;
                observations.dispatchEvent(new Event('input'));
            }
        }

        // Função para baixar e definir imagem
        async function downloadAndSetImage(imageUrl) {
            try {
                const imagePreview = document.getElementById('image_preview');
                const previewImg = imagePreview.querySelector('img');

                // Mostrar preview da imagem
                previewImg.src = imageUrl;
                imagePreview.style.display = 'flex';

                // Remover indicador existente do Discogs
                let existingIndicator = imagePreview.querySelector('.auto-filled-indicator');
                if (existingIndicator) {
                    existingIndicator.remove();
                }

                // Adicionar novo indicador de que a imagem veio do Discogs
                const indicator = document.createElement('div');
                indicator.className = 'auto-filled-indicator';
                indicator.innerHTML = '<i class="fas fa-robot"></i> Do Discogs';
                imagePreview.appendChild(indicator);

                // Marcar para não manter a imagem existente (usar a nova do Discogs)
                const keepExistingInput = document.getElementById('keep_existing_image');
                if (keepExistingInput) {
                    keepExistingInput.value = 'false';
                }

            } catch (error) {
                console.error('Erro ao processar imagem do Discogs:', error);
            }
        }

        // Função para marcar campos preenchidos automaticamente
        function markAutoFilledFields() {
            const autoFilledFields = [
                'artist',
                'album_name',
                'year',
                'label',
                'observations'
            ];
            autoFilledFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && field.value) {
                    field.classList.add('auto-filled');
                    // Remover indicador existente
                    let existingIndicator = field.parentNode.querySelector('.auto-filled-indicator');
                    if (existingIndicator) {
                        existingIndicator.remove();
                    }
                    // Adicionar novo indicador visual
                    const indicator = document.createElement('div');
                    indicator.className = 'auto-filled-indicator';
                    indicator.innerHTML = '<i class="fas fa-robot"></i> Auto';
                    field.parentNode.style.position = 'relative';
                    field.parentNode.appendChild(indicator);
                }
            });
        }


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
                    // Dispara a verificação de aviso ao selecionar importado, caso o país já esteja preenchido
                    const selectedOption = countrySelect.options[countrySelect.selectedIndex];
                    if (selectedOption && selectedOption.dataset.fakeProne === 'true') {
                        fakeWarning.style.display = 'block';
                    }
                } else {
                    importFields.style.display = 'none';
                    countrySelect.required = false;
                    // Não limpamos o valor para manter o estado, mas escondemos o aviso
                    fakeWarning.style.display = 'none';
                }
                originRadios.forEach(r =>
                    r.closest('.origin-option').classList.remove('selected'));
                this.closest('.origin-option').classList.add('selected');
            });
        });

        // Aviso para países propensos a falsificação
        countrySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.fakeProne === 'true') {
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
                typeRadios.forEach(r => r.closest('.media-option').classList.remove('selected'));
                this.closest('.media-option').classList.add('selected');
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
                this.closest('.limited-edition-option').classList.toggle('selected', this.checked);
            });
            // Inicializar estado do limited_fields
            if (!limitedCheckbox.checked) {
                limitedFields.style.display = 'none';
            }
        }

        // Adicionar classes visuais aos radio buttons de edição
        document.querySelectorAll('input[name="edition"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('input[name="edition"]').forEach(r => {
                    r.closest('.edition-option').classList.remove('selected');
                });
                this.closest('.edition-option').classList.add('selected');
            });
        });

        // Adicionar classes visuais aos checkboxes (sealed, extra-item, favorite-option)
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const container = this.closest(
                    '.extra-item, .sealed-option, .limited-edition-option, .favorite-option');
                if (container) {
                    if (this.checked) {
                        container.classList.add('selected');
                    } else {
                        container.classList.remove('selected');
                    }
                }
            });
        });


        // Preview de imagem para edição
        const diskImageInput = document.getElementById('disk_image');
        const imagePreviewContainer = document.getElementById('image_preview');
        const imagePreview = imagePreviewContainer.querySelector('img');
        const removeImageBtn = imagePreviewContainer.querySelector('.remove-image-btn');
        const keepExistingImageHiddenInput = document.getElementById('keep_existing_image');

        // Se já existe uma imagem, exibi-la
        if (imagePreview.src && imagePreview.src !== window.location.href + '#') {
            imagePreviewContainer.style.display = 'flex';
        }

        diskImageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src =
                        e.target.result;
                    imagePreviewContainer.style.display = 'flex';
                    keepExistingImageHiddenInput.value =
                        'true'; // Reset para true se uma nova imagem foi selecionada
                    // Remover indicador do Discogs se existir
                    const discogsIndicator = imagePreviewContainer.querySelector('.auto-filled-indicator');
                    if (discogsIndicator) {
                        discogsIndicator.remove();
                    }
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                // Se o input de arquivo é limpo, mas a imagem anterior existia, a mantemos.
                // O valor de keep_existing_image será definido para 'false' apenas se o botão de remover for clicado.
                if (!imagePreview.src || imagePreview.src === window.location.href + '#') {
                    imagePreviewContainer.style.display = 'none';
                }
            }
        });

        removeImageBtn.addEventListener('click', function() {
            diskImageInput.value = ''; // Limpa o input de arquivo
            imagePreviewContainer.style.display = 'none';
            imagePreview.src = '#';
            keepExistingImageHiddenInput.value = 'false'; // Indica que a imagem existente deve ser removida
            // Remover indicador do Discogs se existir
            const discogsIndicator = imagePreviewContainer.querySelector('.auto-filled-indicator');
            if (discogsIndicator) {
                discogsIndicator.remove();
            }
        });

        // Lógica de descrição de condição para select condition_disk
        const conditionDiskSelect = document.getElementById('condition_disk');
        const conditionInfoDiv = document.querySelector('.condition-info p');
        const conditionDescriptions = {
            'Mint': 'Estado perfeito, como novo, sem nenhum defeito visível',
            'E+': 'Excelente estado com sinais mínimos de uso',
            'E': 'Excelente estado com pequenos sinais de uso',
            'VG+': 'Muito bom estado com alguns sinais de uso',
            'VG': 'Muito bom estado com sinais moderados de uso',
            'G+': 'Bom estado com sinais evidentes de uso',
            'G': 'Bom estado com sinais consideráveis de uso'
        };

        function updateConditionDescription() {
            const selectedCondition = conditionDiskSelect.value;
            if (selectedCondition && conditionDescriptions[selectedCondition]) {
                conditionInfoDiv.innerHTML = `<strong>${selectedCondition}:</strong> ${conditionDescriptions[selectedCondition]}`;
            } else {
                conditionInfoDiv.innerHTML = `<strong>Mint:</strong> Estado perfeito, sem uso aparente`;
            }
        }
        conditionDiskSelect.addEventListener('change', updateConditionDescription);
        updateConditionDescription(); // Chama na carga inicial para garantir que a descrição esteja correta
    });
</script>
<style>
    /* Estilos existentes */
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

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        border-color: var(--color-warning);
        box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.1);
    }

    .btn-success {
        background: linear-gradient(135deg, var(--color-warning) 0%, #f57c00 100%);
        box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
    }

    .btn-success:hover {
        box-shadow: 0 8px 25px rgba(255, 152, 0, 0.4);
    }

    /* Estilos para busca Discogs */
    .discogs-search-container {
        background: var(--color-background);
        border: 1px solid rgba(255, 107, 53, 0.2);
        border-radius: var(--border-radius);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .search-input-group {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .search-input-group .form-input {
        flex: 1;
    }

    .loading-spinner {
        text-align: center;
        padding: 2rem;
        color: var(--color-text-secondary);
    }

    .loading-spinner i {
        font-size: 1.5rem;
        margin-right: 0.5rem;
    }

    .discogs-results {
        margin-top: 1.5rem;
    }

    .discogs-results h3 {
        color: var(--color-text);
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }

    .results-list {
        display: grid;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .discogs-result-item {
        background: var(--color-white);
        border: 1px solid rgba(255, 107, 53, 0.1);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        cursor: pointer;
        transition: var(--transition);
        position: relative;
    }

    .discogs-result-item:hover {
        border-color: var(--color-primary);
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
    }

    .discogs-result-item.selected {
        border-color: var(--color-primary);
        background: rgba(255, 107, 53, 0.05);
    }

    .result-content {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .result-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: var(--border-radius);
        background: var(--color-background);
        flex-shrink: 0;
    }

    .result-info {
        flex: 1;
    }

    .result-title {
        font-weight: 600;
        color: var(--color-text);
        margin-bottom: 0.5rem;
        font-size: 1rem;
    }

    .result-artist {
        color: var(--color-primary);
        margin-bottom: 0.25rem;
        font-size: 0.9rem;
    }

    .result-details {
        display: flex;
        gap: 1rem;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
        color: var(--color-text-secondary);
    }

    .result-formats {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .format-tag {
        background: var(--color-background);
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        color: var(--color-text-secondary);
    }

    .style-tag {
        background: rgba(255, 107, 53, 0.1);
        color: var(--color-primary);
    }

    .use-discogs-btn {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: var(--color-success);
        color: var(--color-white);
        border: none;
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius);
        font-size: 0.8rem;
        cursor: pointer;
        transition: var(--transition);
    }

    .use-discogs-btn:hover {
        background: #27ae60;
        transform: translateY(-1px);
    }

    .pagination-controls {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        align-items: center;
    }

    .pagination-btn {
        background: var(--color-background);
        border: 1px solid rgba(255, 107, 53, 0.2);
        color: var(--color-text);
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: var(--transition);
    }

    .pagination-btn:hover:not(:disabled) {
        background: var(--color-primary);
        color: var(--color-white);
    }

    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .pagination-info {
        color: var(--color-text-secondary);
        font-size: 0.9rem;
    }

    /* Estilos para autocomplete */
    .autocomplete-container {
        position: relative;
    }

    .autocomplete-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--color-white);
        border: 1px solid rgba(255, 107, 53, 0.2);
        border-top: none;
        border-radius: 0 0 var(--border-radius) var(--border-radius);
        box-shadow: var(--shadow-medium);
        z-index: 1000;
        max-height: 200px;
        overflow-y: auto;
        display: none;
    }

    .suggestion-item {
        padding: 0.75rem 1rem;
        cursor: pointer;
        transition: var(--transition);
        border-bottom: 1px solid rgba(255, 107, 53, 0.1);
    }

    .suggestion-item:last-child {
        border-bottom: none;
    }

    .suggestion-item:hover,
    .suggestion-item.active {
        background: rgba(255, 107, 53, 0.1);
        color: var(--color-primary);
    }

    /* Estilos para preview de imagem */
    .image-preview {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-top: 1.5rem;
        border: 1px dashed rgba(255, 107, 53, 0.3);
        border-radius: var(--border-radius);
        padding: 1rem;
        background: var(--color-background);
        position: relative;
    }

    .image-preview img {
        max-width: 100%;
        height: 200px;
        object-fit: contain;
        border-radius: var(--border-radius);
        margin-bottom: 1rem;
    }

    .image-preview .remove-image-btn {
        background: var(--color-error);
        color: var(--color-white);
        border: none;
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius);
        cursor: pointer;
        font-size: 0.9rem;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .image-preview .remove-image-btn:hover {
        background: #d32f2f;
        transform: translateY(-2px);
    }

    /* Estilos para opção de favorito */
    .favorite-option {
        background: var(--color-background);
        border: 2px solid rgba(255, 215, 63, 0.2);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        cursor: pointer;
        transition: var(--transition);
        position: relative;
        margin-top: 1.5rem;
    }

    .favorite-option:hover {
        border-color: var(--color-accent);
        transform: translateY(-3px);
        box-shadow: var(--shadow-medium);
    }

    .favorite-option input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .favorite-option.selected {
        border-color: var(--color-accent);
        background: rgba(255, 215, 63, 0.1);
    }

    .favorite-option.selected .favorite-info h3 {
        color: var(--color-accent);
    }

    .favorite-label {
        display: flex;
        align-items: center;
        gap: 1rem;
        cursor: pointer;
    }

    .favorite-icon {
        font-size: 1.5rem;
        color: var(--color-accent);
    }

    .favorite-info h3 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--color-text);
        margin-bottom: 0.25rem;
        transition: var(--transition);
    }

    .favorite-info p {
        color: var(--color-text-secondary);
        font-size: 0.85rem;
    }

    /* Indicador de dados preenchidos automaticamente */
    .auto-filled {
        background: rgba(46, 204, 113, 0.1);
        border-color: var(--color-success);
    }

    .auto-filled-indicator {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: var(--color-success);
        color: var(--color-white);
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
    }

    /* Notificações */
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        max-width: 400px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-large);
        animation: slideIn 0.3s ease-out;
    }

    .notification-success {
        background: var(--color-success);
        color: var(--color-white);
    }

    .notification-error {
        background: var(--color-error);
        color: var(--color-white);
    }

    .notification-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.5rem;
        gap: 1rem;
    }

    .notification-close {
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 50%;
        transition: var(--transition);
    }

    .notification-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .search-input-group {
            flex-direction: column;
        }

        .result-content {
            flex-direction: column;
            text-align: center;
        }

        .result-image {
            align-self: center;
        }

        .use-discogs-btn {
            position: static;
            margin-top: 1rem;
            width: 100%;
        }

        .pagination-controls {
            flex-direction: column;
            gap: 1rem;
        }
    }
</style>
<?php include 'includes/footer.php'; ?>