<?php
// disk_details.php
require_once 'includes/functions.php';
// Verificar se está logado
requireLogin();

// Conectar ao banco de dados
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

// NOTA: A lógica de coleção compartilhada foi removida.
// A função getDiskById agora é chamada sem o parâmetro shared_owner_id.
try {
    // Buscar disco com todos os detalhes
    $disk = getDiskById($pdo, $disk_id, $_SESSION['user_id']);

    if (!$disk) {
        setFlashMessage('error', 'Disco não encontrado ou acesso negado.');
        header('Location: search_disks.php');
        exit();
    }
} catch (Exception $e) {
    setFlashMessage('error', 'Erro ao carregar detalhes do disco: ' . $e->getMessage());
    header('Location: search_disks.php');
    exit();
}

$page_title = $disk['album_name'] . ' - ' . $disk['artist'];
$show_header = true;
$show_footer = true;

include 'includes/header.php';
?>
<main class="main">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="dashboard.php">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <i class="fas fa-chevron-right"></i>
            <a href="search_disks.php">
                <i class="fas fa-search"></i> Minha Coleção
            </a>
            <i class="fas fa-chevron-right"></i>
            <span><?php echo htmlspecialchars($disk['album_name']); ?></span>
        </nav>

        <!-- Header do Disco -->
        <div class="disk-header">
            <div class="disk-main-info">
                <?php if ($disk['image_path']): // Exibir imagem do disco ?>
                    <img src="<?php echo htmlspecialchars($disk['image_path']); ?>" alt="Capa do Álbum" class="disk-image-details">
                <?php else: ?>
                    <div class="disk-type-icon">
                        <i class="fas fa-<?php echo $disk['type'] === 'CD' ? 'compact-disc' : ($disk['type'] === 'LP' ? 'record-vinyl' : 'box-open'); ?>"></i>
                    </div>
                <?php endif; ?>
                <div class="disk-title-section">
                    <div class="disk-badges-header">
                        <span class="type-badge"><?php echo $disk['type']; ?></span>
                        <?php if ($disk['is_sealed']): ?>
                            <span class="sealed-badge">
                                <i class="fas fa-certificate"></i>
                                Lacrado
                            </span>
                        <?php endif; ?>
                        <?php if ($disk['is_imported']): ?>
                            <span class="imported-badge">
                                <i class="fas fa-globe"></i>
                                Importado
                            </span>
                        <?php endif; ?>
                        <?php if ($disk['is_favorite']): // Badge de favorito ?>
                            <span class="favorite-badge">
                                <i class="fas fa-star"></i>
                                Favorito
                            </span>
                        <?php endif; ?>
                    </div>
                    <h1 class="disk-title"><?php echo htmlspecialchars($disk['album_name']); ?></h1>
                    <h2 class="disk-artist"><?php echo htmlspecialchars($disk['artist']); ?></h2>
                    <div class="disk-meta">
                        <?php if ($disk['year']): ?>
                            <span class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <?php echo $disk['year']; ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($disk['label']): ?>
                            <span class="meta-item">
                                <i class="fas fa-building"></i>
                                <?php echo htmlspecialchars($disk['label']); ?>
                            </span>
                        <?php endif; ?>
                        <span class="meta-item">
                            <i class="fas fa-bookmark"></i>
                            <?php echo formatEdition($disk['edition']); ?>
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-clock"></i>
                            Adicionado em <?php echo date('d/m/Y', strtotime($disk['created_at'])); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="disk-actions">
                <a href="search_disks.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </a>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i>
                    Imprimir
                </button>
                <?php // Apenas o dono do disco pode editar ?>
                <a href="edit_disk.php?id=<?php echo $disk['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i>
                    Editar
                </a>
            </div>
        </div>

        <!-- Detalhes do Disco -->
        <div class="disk-details-container">
            <div class="details-grid">
                <!-- Informações Gerais -->
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Informações Gerais
                    </h3>
                    <div class="detail-content">
                        <div class="detail-row">
                            <span class="detail-label">Tipo:</span>
                            <span class="detail-value"><?php echo $disk['type']; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Artista:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($disk['artist']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Álbum:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($disk['album_name']); ?></span>
                        </div>
                        <?php if ($disk['year']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Ano:</span>
                                <span class="detail-value"><?php echo $disk['year']; ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($disk['label']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Gravadora:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($disk['label']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <span class="detail-label">Edição:</span>
                            <span class="detail-value"><?php echo formatEdition($disk['edition']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Origem -->
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-globe"></i>
                        Origem
                    </h3>
                    <div class="detail-content">
                        <div class="detail-row">
                            <span class="detail-label">Tipo:</span>
                            <span class="detail-value">
                                <?php echo $disk['is_imported'] ? 'Importado' : 'Nacional'; ?>
                                <?php if ($disk['is_imported']): ?>
                                    <i class="fas fa-plane" style="margin-left: 0.5rem; color: var(--color-primary);"></i>
                                <?php else: ?>
                                    <i class="fas fa-home" style="margin-left: 0.5rem; color: var(--color-primary);"></i>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if ($disk['country_name']): ?>
                            <div class="detail-row">
                                <span class="detail-label">País:</span>
                                <span class="detail-value">
                                    <?php echo htmlspecialchars($disk['country_name']); ?>
                                    <?php if ($disk['is_fake_prone']): ?>
                                        <span class="warning-badge">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Atenção: Falsificações
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Continente:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($disk['continent']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Condição -->
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-star"></i>
                        Condição
                    </h3>
                    <div class="detail-content">
                        <div class="detail-row">
                            <span class="detail-label">Disco:</span>
                            <span class="detail-value condition-value">
                                <span class="condition-badge condition-<?php echo strtolower(str_replace('+', 'plus', $disk['condition_disk'])); ?>">
                                    <?php echo formatCondition($disk['condition_disk']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Capa:</span>
                            <span class="detail-value condition-value">
                                <span class="condition-badge condition-<?php echo strtolower(str_replace('+', 'plus', $disk['condition_cover'])); ?>">
                                    <?php echo formatCondition($disk['condition_cover']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value">
                                <?php if ($disk['is_sealed']): ?>
                                    <span class="sealed-status">
                                        <i class="fas fa-certificate"></i>
                                        Lacrado
                                    </span>
                                <?php else: ?>
                                    <span class="unsealed-status">
                                        <i class="fas fa-unlock"></i>
                                        Aberto
                                    </span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <!-- Status de favorito -->
                        <div class="detail-row">
                            <span class="detail-label">Favorito:</span>
                            <span class="detail-value">
                                <?php if ($disk['is_favorite']): ?>
                                    <span class="favorite-status active">
                                        <i class="fas fa-heart"></i> Sim
                                    </span>
                                <?php else: ?>
                                    <span class="favorite-status">
                                        <i class="far fa-heart"></i> Não
                                    </span>
                                <?php endif; ?>
                                <?php // Botão para alternar favorito - sempre visível para o dono ?>
                                <button class="btn-toggle-favorite" data-disk-id="<?php echo $disk['id']; ?>">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Itens Extras -->
                <div class="detail-section">
                    <h3 class="section-title">
                        <i class="fas fa-gift"></i>
                        Itens Extras
                    </h3>
                    <div class="detail-content">
                        <div class="extras-list">
                            <?php
                            $extras = [
                                'has_booklet' => ['icon' => 'book', 'label' => 'Encarte/Livreto'],
                                'has_poster' => ['icon' => 'image', 'label' => 'Poster'],
                                'has_photos' => ['icon' => 'camera', 'label' => 'Fotos'],
                                'has_extra_disk' => ['icon' => 'plus-circle', 'label' => 'Disco Extra'],
                                'has_lyrics' => ['icon' => 'file-alt', 'label' => 'Letras']
                            ];
                            $has_extras = false;
                            foreach ($extras as $key => $extra):
                                if ($disk[$key]):
                                    $has_extras = true;
                                    ?>
                                    <div class="extra-item-detail">
                                        <i class="fas fa-<?php echo $extra['icon']; ?>"></i>
                                        <span><?php echo $extra['label']; ?></span>
                                    </div>
                                    <?php
                                endif;
                            endforeach;
                            if (!$has_extras && empty($disk['other_extras'])):
                                ?>
                                <div class="no-extras">
                                    <i class="fas fa-minus-circle"></i>
                                    <span>Nenhum item extra</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($disk['other_extras'])): ?>
                            <div class="other-extras">
                                <h4>Outros itens:</h4>
                                <p><?php echo nl2br(htmlspecialchars($disk['other_extras'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- BoxSet Específico -->
                <?php if ($disk['type'] === 'BoxSet'): ?>
                    <div class="detail-section boxset-section">
                        <h3 class="section-title">
                            <i class="fas fa-crown"></i>
                            Detalhes do BoxSet
                        </h3>
                        <div class="detail-content">
                            <div class="detail-row">
                                <span class="detail-label">Tipo:</span>
                                <span class="detail-value">
                                    <?php if ($disk['is_limited_edition']): ?>
                                        <span class="limited-badge">
                                            <i class="fas fa-gem"></i>
                                            Edição Limitada
                                        </span>
                                    <?php else: ?>
                                        BoxSet Regular
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if ($disk['is_limited_edition']): ?>
                                <?php if ($disk['edition_number']): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Número:</span>
                                        <span class="detail-value">#<?php echo $disk['edition_number']; ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($disk['total_editions']): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Total:</span>
                                        <span class="detail-value"><?php echo number_format($disk['total_editions'], 0, ',', '.'); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($disk['edition_number'] && $disk['total_editions']): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Raridade:</span>
                                        <span class="detail-value">
                                            <?php
                                            $percentage = ($disk['edition_number'] / $disk['total_editions']) * 100;
                                            if ($percentage <= 10): ?>
                                                <span class="rarity-badge rarity-ultra">Ultra Raro (Top 10%)</span>
                                            <?php elseif ($percentage <= 25): ?>
                                                <span class="rarity-badge rarity-very">Muito Raro (Top 25%)</span>
                                            <?php elseif ($percentage <= 50): ?>
                                                <span class="rarity-badge rarity-rare">Raro (Top 50%)</span>
                                            <?php else: ?>
                                                <span class="rarity-badge rarity-common">Comum</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!empty($disk['special_items'])): ?>
                                <div class="special-items">
                                    <h4>Itens Especiais:</h4>
                                    <p><?php echo nl2br(htmlspecialchars($disk['special_items'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Observações -->
                <?php if (!empty($disk['observations'])): ?>
                    <div class="detail-section observations-section">
                        <h3 class="section-title">
                            <i class="fas fa-sticky-note"></i>
                            Observações
                        </h3>
                        <div class="detail-content">
                            <div class="observations-text">
                                <?php echo nl2br(htmlspecialchars($disk['observations'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ações Inferiores -->
        <div class="disk-bottom-actions">
            <a href="search_disks.php" class="btn btn-secondary">
                <i class="fas fa-search"></i>
                Ver Mais Discos
            </a>
            <a href="register_disk.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i>
                Cadastrar Outro Disco
            </a>
        </div>
    </div>
</main>
<style>
/* Estilos específicos dos detalhes */
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
.disk-header {
    background: var(--color-surface);
    border: 1px solid rgba(255, 107, 53, 0.1);
    border-radius: var(--border-radius-large);
    padding: 2.5rem;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
    flex-wrap: wrap; /* Adicionado para responsividade */
}
.disk-main-info {
    display: flex;
    gap: 2rem;
    flex: 1;
    min-width: 280px; /* Garante que não fique muito estreito */
}

.disk-image-details {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: var(--border-radius);
    flex-shrink: 0;
    box-shadow: var(--shadow-medium);
}
.disk-type-icon {
    width: 80px;
    height: 80px;
    background: var(--gradient-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: var(--color-white);
    flex-shrink: 0;
}
.disk-title-section {
    flex: 1;
}
.disk-badges-header {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}
.type-badge {
    background: var(--gradient-primary);
    color: var(--color-white);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.9rem;
}
.sealed-badge {
    background: rgba(76, 175, 80, 0.1);
    color: var(--color-success);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 500;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.imported-badge {
    background: rgba(33, 150, 243, 0.1);
    color: #2196F3;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 500;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.favorite-badge {
    background: rgba(255, 215, 63, 0.1); /* Cor de accent */
    color: var(--color-accent);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 500;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.disk-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 0.5rem;
    line-height: 1.2;
}
.disk-artist {
    font-size: 1.5rem;
    font-weight: 500;
    color: var(--color-primary);
    margin-bottom: 1rem;
}
.disk-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
}
.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--color-text-secondary);
    font-size: 0.9rem;
}
.meta-item i {
    color: var(--color-primary);
}
.disk-actions {
    display: flex;
    gap: 1rem;
    flex-shrink: 0;
    flex-wrap: wrap; /* Adicionado para responsividade */
    align-items: flex-start;
}
.disk-actions .btn {
    min-width: 120px; /* Garante que os botões tenham largura mínima */
}

.disk-details-container {
    margin-bottom: 3rem;
}
.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); /* Ajustado para melhor responsividade */
    gap: 2rem;
}
.detail-section {
    background: var(--color-surface);
    border: 1px solid rgba(255, 107, 53, 0.1);
    border-radius: var(--border-radius);
    padding: 2rem;
}
.detail-section.boxset-section {
    border-color: rgba(255, 215, 63, 0.3);
    background: rgba(255, 215, 63, 0.05);
}
.detail-section.observations-section {
    grid-column: 1 / -1; /* Ocupa toda a largura em grid */
}
.section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--color-primary);
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(255, 107, 53, 0.1);
}
.detail-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255, 107, 53, 0.05);
}
.detail-row:last-child {
    border-bottom: none;
}
.detail-label {
    font-weight: 600;
    color: var(--color-text);
    min-width: 100px;
}
.detail-value {
    color: var(--color-text-secondary);
    text-align: right;
    flex: 1;
}
.condition-badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.9rem;
}
.condition-mint { background: rgba(76, 175, 80, 0.1); color: var(--color-success); }
.condition-eplus { background: rgba(139, 195, 74, 0.1); color: #8BC34A; }
.condition-e { background: rgba(205, 220, 57, 0.1); color: #CDDC39; }
.condition-vgplus { background: rgba(255, 193, 7, 0.1); color: #FFC107; }
.condition-vg { background: rgba(255, 152, 0, 0.1); color: var(--color-warning); }
.condition-gplus { background: rgba(255, 87, 34, 0.1); color: #FF5722; }
.condition-g { background: rgba(244, 67, 54, 0.1); color: var(--color-error); }
.sealed-status {
    color: var(--color-success);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
}
.unsealed-status {
    color: var(--color-text-secondary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.warning-badge {
    background: rgba(255, 152, 0, 0.1);
    color: var(--color-warning);
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    margin-left: 0.5rem;
}
.extras-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.extra-item-detail {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--color-success);
    font-weight: 500;
}
.extra-item-detail i {
    color: var(--color-primary);
    width: 20px;
}
.no-extras {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--color-text-secondary);
    font-style: italic;
}
.other-extras,
.special-items {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(255, 107, 53, 0.05);
    border-radius: var(--border-radius);
    border-left: 3px solid var(--color-primary);
}
.other-extras h4,
.special-items h4 {
    color: var(--color-primary);
    margin-bottom: 0.5rem;
    font-size: 1rem;
}
.other-extras p,
.special-items p {
    color: var(--color-text-secondary);
    margin: 0;
    line-height: 1.6;
}
.limited-badge {
    background: rgba(255, 215, 63, 0.1);
    color: var(--color-accent);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.rarity-badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.9rem;
}
.rarity-ultra { background: rgba(156, 39, 176, 0.1); color: #9C27B0; }
.rarity-very { background: rgba(233, 30, 99, 0.1); color: #E91E63; }
.rarity-rare { background: rgba(255, 152, 0, 0.1); color: var(--color-warning); }
.rarity-common { background: rgba(96, 125, 139, 0.1); color: #607D8B; }
.observations-text {
    background: rgba(176, 176, 176, 0.05);
    border: 1px solid rgba(176, 176, 176, 0.1);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    color: var(--color-text-secondary);
    line-height: 1.7;
    font-style: italic;
}
.disk-bottom-actions {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 3rem;
    flex-wrap: wrap; /* Adicionado para responsividade */
}

/* Status de favorito */
.favorite-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    color: var(--color-text-secondary);
}
.favorite-status.active {
    color: var(--color-accent);
    font-weight: 600;
}
.favorite-status.active i {
    color: var(--color-accent);
}
.btn-toggle-favorite {
    background: none;
    border: 1px solid var(--color-primary);
    color: var(--color-primary);
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    cursor: pointer;
    margin-left: 1rem;
    transition: var(--transition);
}
.btn-toggle-favorite:hover {
    background: var(--color-primary);
    color: var(--color-white);
}

/* Botão de editar com cor diferente */
.btn-warning {
    background: linear-gradient(135deg, var(--color-warning) 0%, #f57c00 100%);
    color: var(--color-white);
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: var(--border-radius);
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    transition: var(--transition);
    cursor: pointer;
}
.btn-warning:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(255, 152, 0, 0.4);
}

@media (max-width: 768px) {
    .disk-header {
        flex-direction: column;
        gap: 2rem;
        align-items: center; /* Centraliza itens no mobile */
    }
    .disk-main-info {
        flex-direction: column;
        text-align: center;
        align-items: center;
    }
    .disk-actions {
        width: 100%;
        justify-content: center;
    }
    .details-grid {
        grid-template-columns: 1fr;
    }
    .disk-title {
        font-size: 2rem;
    }
    .disk-artist {
        font-size: 1.2rem;
    }
    .disk-meta {
        justify-content: center;
    }
    .detail-row {
        flex-direction: column;
        gap: 0.5rem;
        text-align: left; /* Mantém alinhamento para detalhes */
        align-items: flex-start;
    }
    .detail-value {
        text-align: left;
    }
    .disk-bottom-actions {
        flex-direction: column;
        align-items: center;
    }
    .disk-image-details {
        width: 180px; /* Tamanho ajustado para mobile */
        height: 180px;
    }
}
@media (max-width: 480px) {
    .disk-header {
        padding: 1.5rem;
    }
    .disk-title {
        font-size: 1.8rem;
    }
    .disk-artist {
        font-size: 1rem;
    }
    .disk-main-info {
        gap: 1rem;
    }
    .disk-image-details {
        width: 120px;
        height: 120px;
    }
}
@media print {
    .disk-actions,
    .disk-bottom-actions,
    .breadcrumb {
        display: none;
    }
    .disk-header {
        border: 1px solid #ccc;
    }
    .detail-section {
        border: 1px solid #ccc;
        break-inside: avoid;
    }
}
</style>
<script>
// Lógica para alternar favorito via AJAX
document.addEventListener('DOMContentLoaded', function() {
    const toggleFavoriteBtn = document.querySelector('.btn-toggle-favorite');
    if (toggleFavoriteBtn) {
        toggleFavoriteBtn.addEventListener('click', function() {
            const diskId = this.dataset.diskId;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('api/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    // 'X-Requested-With': 'XMLHttpRequest' // Removido, conforme discutido
                },
                body: `disk_id=${diskId}&csrf_token=${csrfToken}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const favoriteStatusSpan = document.querySelector('.favorite-status');
                    if (favoriteStatusSpan) {
                        if (data.is_favorite) {
                            favoriteStatusSpan.classList.add('active');
                            favoriteStatusSpan.innerHTML = '<i class="fas fa-heart"></i> Sim';
                            showNotification('Disco marcado como favorito!', 'success');
                        } else {
                            favoriteStatusSpan.classList.remove('active');
                            favoriteStatusSpan.innerHTML = '<i class="far fa-heart"></i> Não';
                            showNotification('Disco removido dos favoritos!', 'success');
                        }
                    }
                } else {
                    showNotification(data.message || 'Erro ao atualizar favorito.', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showNotification('Erro de conexão ao atualizar favorito.', 'error');
            });
        });
    }

    // Função para mostrar notificações (garantindo consistência)
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.classList.add('show'), 100);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
});
</script>
<?php include 'includes/footer.php'; ?>