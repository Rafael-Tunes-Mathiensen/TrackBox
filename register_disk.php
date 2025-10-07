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
                        condition_cover, is_sealed, observations
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $_SESSION['user_id'], $type, $artist, $album_name, $year, $label,
                    $country_id, $is_imported, $edition, $condition_disk,
                    $condition_cover, $is_sealed, $observations
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

                <!-- Origem -->
                <div class="form-section">
                    <div class="section-header">
                        <h2 class="section-subtitle">
                            <i class="fas fa-globe"></i>
                            Origem do Disco
                        </h2>
                    </div>
                    
                    <div class="origin-selector">
                        <div class="origin-option">
                            <input type="radio" id="origin_national" name="is_imported" value="0" required>
                            <label for="origin_national">
                                <div class="origin-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="origin-info">
                                    <h3>Nacional</h3>
                                    <p>Produzido no Brasil</p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="origin-option">
                            <input type="radio" id="origin_imported" name="is_imported" value="1" required>
                            <label for="origin_imported">
                                <div class="origin-icon">
                                    <i class="fas fa-plane"></i>
                                </div>
                                <div class="origin-info">
                                    <h3>Importado</h3>
                                    <p>Produzido em outro país</p>
                                </div>
                            </label>
                        </div>
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
                        <div class="edition-option">
                            <input type="radio" id="edition_first" name="edition" value="primeira_edicao" required>
                            <label for="edition_first">
                                <div class="edition-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="edition-info">
                                    <h3>Primeira Edição</h3>
                                    <p>Lançamento original</p>
                                </div>
                            </label>
                        </div>
                        
                        <div class="edition-option">
                            <input type="radio" id="edition_reissue" name="edition" value="reedicao" required>
                            <label for="edition_reissue">
                                <div class="edition-icon">
                                    <i class="fas fa-redo"></i>
                                </div>
                                <div class="edition-info">
                                    <h3>Reedição</h3>
                                    <p>Relançamento posterior</p>
                                </div>
                            </label>
                        </div>
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
            if (this.checked) {
                this.closest('.extra-item, .sealed-option, .limited-edition-option')?.classList.add('selected');
            } else {
                this.closest('.extra-item, .sealed-option, .limited-edition-option')?.classList.remove('selected');
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>