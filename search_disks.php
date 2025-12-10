<?php
// search_disks.php
require_once 'includes/functions.php';

// Verificar se está logado. Se não estiver, mas tiver um collection_code, permitir visualização.
// Se não estiver logado E não tiver collection_code, redirecionar para o login.
if (!isLoggedIn()) {
    if (!isset($_GET['collection_code'])) {
        requireLogin(); // Redireciona para o login se não estiver logado e não for coleção compartilhada
    }
}

// Conectar ao banco para verificar permissões
require_once 'config/database.php';
$database = new Database();
$pdo = $database->getConnection();
$current_user = getCurrentUser($pdo);

// Lógica para visualizar coleções compartilhadas
$shared_collection_owner_id = null;
$viewing_shared_collection = false;
$shared_code = $_GET['collection_code'] ?? null;
$shared_owner_username = '';

if ($shared_code) {
    $shared_owner_id_from_code = getSharedCollectionOwnerId($pdo, $shared_code);
    if ($shared_owner_id_from_code) {
        $shared_collection_owner_id = $shared_owner_id_from_code;
        $viewing_shared_collection = true;
        // Obter nome do dono da coleção compartilhada para exibir na interface
        $stmt = $pdo->prepare("SELECT username, full_name FROM users WHERE id = ?");
        $stmt->execute([$shared_owner_id_from_code]);
        $owner_info = $stmt->fetch();
        if ($owner_info) {
            $shared_owner_username = htmlspecialchars($owner_info['username']);
        }
    } else {
        setFlashMessage('error', 'Código de coleção compartilhada inválido.');
        header('Location: search_disks.php');
        exit();
    }
}

// Define o user_id para a busca: user logado ou dono da coleção compartilhada
$target_user_id = $shared_collection_owner_id ?? ($_SESSION['user_id'] ?? null);

// Se não há target_user_id (nem logado nem código válido), redirecionar
if (!$target_user_id) {
    setFlashMessage('error', 'Acesso não autorizado ou coleção não encontrada.');
    header('Location: login.php');
    exit();
}

$page_title = 'Pesquisar Discos';
$show_header = true;
$show_footer = true;

try {
    // Parâmetros de busca
    $search = sanitizeInput($_GET['search'] ?? '');
    $type_filter = sanitizeInput($_GET['type'] ?? '');
    $condition_filter = sanitizeInput($_GET['condition'] ?? '');
    $filter = sanitizeInput($_GET['filter'] ?? ''); // sealed, imported, favorite
    $country_filter = sanitizeInput($_GET['country'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = 12;

    $filters = [
        'search' => $search,
        'type' => $type_filter,
        'condition' => $condition_filter,
        'country_id' => $country_filter,
        'is_sealed' => ($filter === 'sealed'),
        'is_imported' => ($filter === 'imported'),
        'is_favorite' => ($filter === 'favorite')
    ];

    // Buscar discos usando a função searchDisks atualizada
    $search_results = searchDisks($pdo, $_SESSION['user_id'] ?? null, $filters, $page, $per_page, $shared_collection_owner_id);
    $disks = $search_results['disks'];
    $total_disks = $search_results['total'];
    $total_pages = $search_results['total_pages'];

    // Buscar países para filtro
    $countries = getCountries($pdo);
} catch (Exception $e) {
    setFlashMessage('error', 'Erro ao buscar discos.');
    $disks = [];
    $total_disks = 0;
    $total_pages = 0;
    $countries = [];
}

include 'includes/header.php';
?>
<main class="main">
    <div class="container">
        <!-- Header da Página -->
        <div class="page-header">
            <div class="page-title-container">
                <div class="page-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div>
                    <h1 class="page-title">Pesquisar Discos</h1>
                    <?php if ($viewing_shared_collection): ?>
                        <p class="page-subtitle">Você está visualizando a coleção de **<?php echo $shared_owner_username; ?>**</p>
                    <?php else: ?>
                        <p class="page-subtitle">Encontre, edite e gerencie sua coleção</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Filtros de Busca -->
        <div class="search-filters">
            <form method="GET" class="filters-form" id="searchForm">
                <?php if ($viewing_shared_collection): // Manter o collection_code na URL para filtros ?>
                    <input type="hidden" name="collection_code" value="<?php echo htmlspecialchars($shared_code); ?>">
                <?php endif; ?>
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label" for="search">
                            <i class="fas fa-search"></i>
                            Buscar
                        </label>
                        <input type="text" id="search" name="search" class="filter-input"
                            placeholder="Artista ou álbum..."
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label" for="type">
                            <i class="fas fa-compact-disc"></i>
                            Tipo
                        </label>
                        <select id="type" name="type" class="filter-select">
                            <option value="">Todos os tipos</option>
                            <option value="CD" <?php echo $type_filter === 'CD' ? 'selected' : ''; ?>>CD</option>
                            <option value="LP" <?php echo $type_filter === 'LP' ? 'selected' : ''; ?>>LP</option>
                            <option value="BoxSet" <?php echo $type_filter === 'BoxSet' ? 'selected' : ''; ?>>BoxSet</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label" for="condition">
                            <i class="fas fa-star"></i>
                            Condição
                        </label>
                        <select id="condition" name="condition" class="filter-select">
                            <option value="">Todas as condições</option>
                            <option value="Mint" <?php echo $condition_filter === 'Mint' ? 'selected' : ''; ?>>Mint - Perfeito</option>
                            <option value="E+" <?php echo $condition_filter === 'E+' ? 'selected' : ''; ?>>E+ - Excelente+</option>
                            <option value="E" <?php echo $condition_filter === 'E' ? 'selected' : ''; ?>>E - Excelente</option>
                            <option value="VG+" <?php echo $condition_filter === 'VG+' ? 'selected' : ''; ?>>VG+ - Muito Bom+</option>
                            <option value="VG" <?php echo $condition_filter === 'VG' ? 'selected' : ''; ?>>VG - Muito Bom</option>
                            <option value="G+" <?php echo $condition_filter === 'G+' ? 'selected' : ''; ?>>G+ - Bom+</option>
                            <option value="G" <?php echo $condition_filter === 'G' ? 'selected' : ''; ?>>G - Bom</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label" for="country">
                            <i class="fas fa-globe"></i>
                            País
                        </label>
                        <select id="country" name="country" class="filter-select">
                            <option value="">Todos os países</option>
                            <?php foreach ($countries as $country): ?>
                                <option value="<?php echo $country['id']; ?>"
                                    <?php echo $country_filter == $country['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($country['country_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filters-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Buscar
                    </button>
                    <a href="search_disks.php<?php echo $viewing_shared_collection ? '?collection_code=' . $shared_code : ''; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Limpar
                    </a>
                    <button type="button" class="btn btn-accent" id="liveSearchToggle">
                        <i class="fas fa-bolt"></i>
                        <span id="liveSearchText">Ativar Busca Instantânea</span>
                    </button>
                </div>
            </form>
            <!-- Filtros Rápidos -->
            <div class="quick-filters">
                <a href="search_disks.php?filter=sealed<?php echo $viewing_shared_collection ? '&collection_code=' . $shared_code : ''; ?>"
                    class="quick-filter <?php echo $filter === 'sealed' ? 'active' : ''; ?>">
                    <i class="fas fa-certificate"></i> Lacrados
                </a>
                <a href="search_disks.php?filter=imported<?php echo $viewing_shared_collection ? '&collection_code=' . $shared_code : ''; ?>"
                    class="quick-filter <?php echo $filter === 'imported' ? 'active' : ''; ?>">
                    <i class="fas fa-globe"></i> Importados
                </a>
                <a href="search_disks.php?type=BoxSet<?php echo $viewing_shared_collection ? '&collection_code=' . $shared_code : ''; ?>"
                    class="quick-filter <?php echo $type_filter === 'BoxSet' ? 'active' : ''; ?>">
                    <i class="fas fa-box-open"></i> BoxSets
                </a>
                <!-- NOVO: Filtro rápido para favoritos -->
                <a href="search_disks.php?filter=favorite<?php echo $viewing_shared_collection ? '&collection_code=' . $shared_code : ''; ?>"
                    class="quick-filter <?php echo $filter === 'favorite' ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i> Favoritos
                </a>
            </div>
        </div>
        <!-- Resultados -->
        <div class="search-results">
            <div class="results-header">
                <div class="results-info">
                    <h2>
                        <?php if ($total_disks > 0): ?>
                            <?php echo $total_disks; ?> disco<?php echo $total_disks > 1 ? 's' : ''; ?> encontrado<?php echo $total_disks > 1 ? 's' : ''; ?>
                        <?php else: ?>
                            Nenhum disco encontrado
                        <?php endif; ?>
                    </h2>
                    <?php if (!empty($search) || !empty($type_filter) || !empty($condition_filter) || !empty($filter) || !empty($country_filter)): ?>
                        <p class="search-terms">
                            <?php if (!empty($search)): ?>
                                Busca: <strong><?php echo htmlspecialchars($search); ?></strong>
                            <?php endif; ?>
                            <?php if (!empty($type_filter)): ?>
                                | Tipo: <strong><?php echo $type_filter; ?></strong>
                            <?php endif; ?>
                            <?php if (!empty($condition_filter)): ?>
                                | Condição: <strong><?php echo formatCondition($condition_filter); ?></strong>
                            <?php endif; ?>
                            <?php if ($filter === 'sealed'): ?>
                                | <strong>Lacrados</strong>
                            <?php elseif ($filter === 'imported'): ?>
                                | <strong>Importados</strong>
                            <?php elseif ($filter === 'favorite'): ?>
                                | <strong>Favoritos</strong>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php if ($total_disks > 0): ?>
                    <div class="results-actions">
                        <div class="view-mode-toggle">
                            <button class="view-mode-btn active" data-mode="grid">
                                <i class="fas fa-th"></i>
                            </button>
                            <button class="view-mode-btn" data-mode="list">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                        <?php if (!$viewing_shared_collection): // Botão de adicionar disco só para sua própria coleção ?>
                            <a href="register_disk.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i>
                                Adicionar Disco
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (empty($disks)): ?>
                <div class="empty-results">
                    <div class="empty-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Nenhum disco encontrado</h3>
                    <?php if (!empty($search) || !empty($type_filter) || !empty($condition_filter) || !empty($filter) || !empty($country_filter)): ?>
                        <p>Tente ajustar os filtros de busca ou</p>
                        <a href="search_disks.php<?php echo $viewing_shared_collection ? '?collection_code=' . $shared_code : ''; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Limpar Filtros
                        </a>
                    <?php else: ?>
                        <?php if (!$viewing_shared_collection): ?>
                            <p>Você ainda não cadastrou nenhum disco na sua coleção.</p>
                            <a href="register_disk.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i>
                                Cadastrar Primeiro Disco
                            </a>
                        <?php else: ?>
                            <p>A coleção compartilhada de <?php echo $shared_owner_username; ?> ainda não possui discos.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="disks-container" id="disksContainer">
                    <div class="disks-grid" id="disksGrid">
                        <?php foreach ($disks as $disk): ?>
                            <div class="disk-card" data-disk-id="<?php echo $disk['id']; ?>">
                                <?php if ($disk['image_path']): // NOVO: Exibir imagem do disco ?>
                                    <img src="<?php echo htmlspecialchars($disk['image_path']); ?>" alt="Capa do Álbum" class="disk-image">
                                <?php else: ?>
                                    <div class="disk-image-placeholder">
                                        <i class="fas fa-compact-disc"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="disk-header">
                                    <div class="disk-type">
                                        <i class="fas fa-<?php echo $disk['type'] === 'CD' ? 'compact-disc' : ($disk['type'] === 'LP' ? 'record-vinyl' : 'box-open'); ?>"></i>
                                        <span><?php echo $disk['type']; ?></span>
                                    </div>
                                    <div class="disk-badges">
                                        <?php if ($disk['is_sealed']): ?>
                                            <div class="badge badge-sealed">
                                                <i class="fas fa-certificate"></i> Lacrado
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($disk['is_imported']): ?>
                                            <div class="badge badge-imported">
                                                <i class="fas fa-globe"></i> Importado
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($disk['is_favorite']): // NOVO: Badge de favorito ?>
                                            <div class="badge badge-favorite">
                                                <i class="fas fa-star"></i> Favorito
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="disk-content">
                                    <h3 class="disk-title"><?php echo htmlspecialchars($disk['album_name']); ?></h3>
                                    <p class="disk-artist"><?php echo htmlspecialchars($disk['artist']); ?></p>
                                    <div class="disk-details">
                                        <?php if ($disk['year']): ?>
                                            <span class="detail-item">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo $disk['year']; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($disk['country_name']): ?>
                                            <span class="detail-item">
                                                <i class="fas fa-globe"></i>
                                                <?php echo htmlspecialchars($disk['country_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="detail-item">
                                            <i class="fas fa-star"></i>
                                            <?php echo formatCondition($disk['condition_disk']); ?>
                                        </span>
                                        <span class="detail-item">
                                            <i class="fas fa-bookmark"></i>
                                            <?php echo formatEdition($disk['edition']); ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($disk['observations'])): ?>
                                        <div class="disk-observations">
                                            <p><?php echo htmlspecialchars(substr($disk['observations'], 0, 100)); ?><?php echo strlen($disk['observations']) > 100 ? '...' : ''; ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="disk-actions">
                                    <a href="disk_details.php?id=<?php echo $disk['id']; ?><?php echo $viewing_shared_collection ? '&collection_code=' . $shared_code : ''; ?>" class="btn-action btn-view">
                                        <i class="fas fa-eye"></i>
                                        <span>Ver</span>
                                    </a>
                                    <?php if (!$viewing_shared_collection && ($disk['user_id'] === ($_SESSION['user_id'] ?? null) || isCurrentUserAdmin($pdo))): // NOVO: Botões de edição/exclusão só para o dono ou admin, e não em coleção compartilhada ?>
                                        <a href="edit_disk.php?id=<?php echo $disk['id']; ?>" class="btn-action btn-edit" title="Editar Disco">
                                            <i class="fas fa-edit"></i>
                                            <span>Editar</span>
                                        </a>
                                        <button class="btn-action btn-delete" onclick="confirmDelete(<?php echo $disk['id']; ?>, '<?php echo htmlspecialchars(addslashes($disk['album_name'])); ?>')" title="Excluir Disco">
                                            <i class="fas fa-trash"></i>
                                            <span>Excluir</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Paginação -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="pagination-btn">
                                <i class="fas fa-chevron-left"></i>
                                Anterior
                            </a>
                        <?php endif; ?>
                        <div class="pagination-info">
                            Página <?php echo $page; ?> de <?php echo $total_pages; ?>
                        </div>
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="pagination-btn">
                                Próxima
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
<!-- Modal de Confirmação de Exclusão -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <div class="modal-header">
            <h3>
                <i class="fas fa-exclamation-triangle"></i>
                Confirmar Exclusão
            </h3>
            <button class="modal-close" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Tem certeza que deseja excluir o disco:</p>
            <div class="delete-disk-info">
                <strong id="deleteDiskName"></strong>
            </div>
            <p class="warning-text">
                <i class="fas fa-exclamation-triangle"></i>
                Esta ação não pode ser desfeita!
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i>
                Cancelar
            </button>
            <button class="btn btn-danger" id="confirmDeleteBtn">
                <i class="fas fa-trash"></i>
                Excluir Disco
            </button>
        </div>
    </div>
</div>
<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Carregando...</p>
    </div>
</div>
<style>
    /* Estilos específicos da busca */
    .search-filters {
        background: var(--color-surface);
        border: 1px solid rgba(255, 107, 53, 0.1);
        border-radius: var(--border-radius-large);
        padding: 2rem;
        margin-bottom: 2rem;
    }
    .filters-form {
        margin-bottom: 1.5rem;
    }
    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .filter-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--color-text);
        font-weight: 500;
        font-size: 0.9rem;
    }
    .filter-label i {
        color: var(--color-primary);
    }
    .filter-input,
    .filter-select {
        padding: 0.75rem;
        background: var(--color-background);
        border: 1px solid rgba(255, 107, 53, 0.2);
        border-radius: var(--border-radius);
        color: var(--color-text);
        font-family: inherit;
        transition: var(--transition);
    }
    .filter-input:focus,
    .filter-select:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
    }
    .filters-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    .btn-accent {
        background: var(--gradient-accent);
        color: var(--color-dark);
        border: none;
    }
    .btn-accent:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(255, 215, 63, 0.4);
    }
    .quick-filters {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    .quick-filter {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: var(--color-background);
        border: 1px solid rgba(255, 107, 53, 0.2);
        border-radius: 50px;
        color: var(--color-text);
        text-decoration: none;
        font-size: 0.9rem;
        transition: var(--transition);
    }
    .quick-filter:hover,
    .quick-filter.active {
        background: var(--color-primary);
        color: var(--color-white);
        border-color: var(--color-primary);
    }
    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 2rem;
        gap: 2rem;
    }
    .results-info h2 {
        color: var(--color-text);
        margin-bottom: 0.5rem;
    }
    .search-terms {
        color: var(--color-text-secondary);
        font-size: 0.9rem;
    }
    .results-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .view-mode-toggle {
        display: flex;
        background: var(--color-surface);
        border: 1px solid rgba(255, 107, 53, 0.2);
        border-radius: var(--border-radius);
        overflow: hidden;
    }
    .view-mode-btn {
        padding: 0.75rem;
        background: transparent;
        border: none;
        color: var(--color-text-secondary);
        cursor: pointer;
        transition: var(--transition);
        font-size: 1rem;
    }
    .view-mode-btn.active,
    .view-mode-btn:hover {
        background: var(--color-primary);
        color: var(--color-white);
    }
    .empty-results {
        text-align: center;
        padding: 4rem 2rem;
        background: var(--color-surface);
        border-radius: var(--border-radius-large);
        border: 1px solid rgba(255, 107, 53, 0.1);
    }
    .empty-icon {
        font-size: 4rem;
        color: var(--color-primary);
        margin-bottom: 1.5rem;
        opacity: 0.7;
    }
    .empty-results h3 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
        color: var(--color-text);
    }
    .empty-results p {
        color: var(--color-text-secondary);
        margin-bottom: 2rem;
    }
    .disks-container {
        position: relative;
    }
    .disks-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.5rem;
        transition: var(--transition);
    }
    .disks-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .disk-card {
        background: var(--color-surface);
        border: 1px solid rgba(255, 107, 53, 0.1);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        display: flex; /* NOVO: Para layout flexível com imagem */
        flex-direction: column; /* NOVO: Para empilhar elementos com imagem */
    }
    .disk-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background: var(--gradient-primary);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    .disk-card:hover {
        transform: translateY(-5px);
        border-color: var(--color-primary);
        box-shadow: var(--shadow-large);
    }
    .disk-card:hover::before {
        transform: scaleX(1);
    }
    /* NOVO: Estilos para imagem do disco */
    .disk-image, .disk-image-placeholder {
        width: 100%;
        height: 200px; /* Altura fixa para as imagens */
        object-fit: cover; /* Ajusta a imagem para cobrir a área */
        border-radius: var(--border-radius);
        margin-bottom: 1rem;
        background-color: var(--color-darker); /* Fundo para placeholders */
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--color-text-secondary);
        font-size: 3rem;
    }
    .disk-card.list {
        flex-direction: row; /* Em modo lista, layout lado a lado */
        align-items: center;
    }
    .disk-card.list .disk-image, .disk-card.list .disk-image-placeholder {
        width: 100px; /* Menor em modo lista */
        height: 100px;
        margin-right: 1rem;
        margin-bottom: 0;
    }
    .disk-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    .disk-card.list .disk-header {
        flex-direction: column; /* Em modo lista, empilha tipo e badges */
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }
    .disk-type {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--color-primary);
        font-weight: 500;
        font-size: 0.9rem;
    }
    .disk-badges {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .badge {
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    .badge-sealed {
        background: rgba(76, 175, 80, 0.1);
        color: var(--color-success);
    }
    .badge-imported {
        background: rgba(33, 150, 243, 0.1);
        color: #2196F3;
    }
    /* NOVO: Badge para favorito */
    .badge-favorite {
        background: rgba(255, 215, 63, 0.1); /* Cor de accent */
        color: var(--color-accent);
    }
    .disk-content {
        margin-bottom: 1.5rem;
        flex-grow: 1; /* NOVO: Ocupar espaço restante */
    }
    .disk-card.list .disk-content {
        margin-bottom: 0.5rem;
    }
    .disk-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--color-text);
        margin-bottom: 0.5rem;
        line-height: 1.3;
    }
    .disk-card.list .disk-title {
        font-size: 1.1rem;
        margin-bottom: 0.25rem;
    }
    .disk-artist {
        color: var(--color-primary);
        font-weight: 500;
        margin-bottom: 1rem;
    }
    .disk-card.list .disk-artist {
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    .disk-details {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .disk-card.list .disk-details {
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
    }
    .detail-item {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        color: var(--color-text-secondary);
        font-size: 0.85rem;
    }
    .disk-card.list .detail-item {
        font-size: 0.8rem;
    }
    .detail-item i {
        color: var(--color-primary);
    }
    .disk-observations {
        margin-top: 1rem;
        padding: 0.75rem;
        background: rgba(255, 107, 53, 0.05);
        border-radius: var(--border-radius);
        border-left: 3px solid var(--color-primary);
    }
    .disk-card.list .disk-observations {
        margin-top: 0.5rem;
        padding: 0.5rem;
        font-size: 0.8rem;
    }
    .disk-observations p {
        color: var(--color-text-secondary);
        font-size: 0.9rem;
        margin: 0;
        font-style: italic;
    }
    .disk-actions {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
        margin-top: auto; /* NOVO: Empurra os botões para baixo */
    }
    .disk-card.list .disk-actions {
        justify-content: flex-start; /* Em modo lista, alinha à esquerda */
        margin-top: 0;
    }
    .btn-action {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        border: none;
        border-radius: var(--border-radius);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9rem;
        transition: var(--transition);
        cursor: pointer;
        flex: 1;
        justify-content: center;
        min-width: 80px;
    }
    .disk-card.list .btn-action {
        padding: 0.5rem 0.8rem;
        font-size: 0.85rem;
        min-width: auto;
    }
    .btn-view {
        background: rgba(33, 150, 243, 0.1);
        color: #2196F3;
        border: 1px solid rgba(33, 150, 243, 0.2);
    }
    .btn-view:hover {
        background: #2196F3;
        color: var(--color-white);
        transform: translateY(-2px);
    }
    .btn-edit {
        background: rgba(255, 152, 0, 0.1);
        color: var(--color-warning);
        border: 1px solid rgba(255, 152, 0, 0.2);
    }
    .btn-edit:hover {
        background: var(--color-warning);
        color: var(--color-white);
        transform: translateY(-2px);
    }
    .btn-delete {
        background: rgba(244, 67, 54, 0.1);
        color: var(--color-error);
        border: 1px solid rgba(244, 67, 54, 0.2);
    }
    .btn-delete:hover {
        background: var(--color-error);
        color: var(--color-white);
        transform: translateY(-2px);
    }
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 2rem;
        margin-top: 3rem;
    }
    .pagination-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: var(--color-surface);
        border: 1px solid rgba(255, 107, 53, 0.2);
        border-radius: var(--border-radius);
        color: var(--color-text);
        text-decoration: none;
        transition: var(--transition);
    }
    .pagination-btn:hover {
        background: var(--color-primary);
        color: var(--color-white);
        border-color: var(--color-primary);
    }
    .pagination-info {
        color: var(--color-text-secondary);
        font-weight: 500;
    }
    /* Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
    }
    .modal-overlay.active {
        display: flex;
    }
    .modal {
        background: var(--color-surface);
        border-radius: var(--border-radius-large);
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        border: 1px solid rgba(255, 107, 53, 0.2);
        animation: modalSlideIn 0.3s ease-out;
    }
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255, 107, 53, 0.1);
    }
    .modal-header h3 {
        color: var(--color-error);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }
    .modal-close {
        background: none;
        border: none;
        color: var(--color-text-secondary);
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 50%;
        transition: var(--transition);
    }
    .modal-close:hover {
        background: rgba(244, 67, 54, 0.1);
        color: var(--color-error);
    }
    .modal-body {
        padding: 1.5rem;
    }
    .delete-disk-info {
        background: rgba(244, 67, 54, 0.1);
        border: 1px solid rgba(244, 67, 54, 0.2);
        border-radius: var(--border-radius);
        padding: 1rem;
        margin: 1rem 0;
        text-align: center;
    }
    .delete-disk-info strong {
        color: var(--color-error);
        font-size: 1.1rem;
    }
    .warning-text {
        color: var(--color-warning);
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    .modal-footer {
        display: flex;
        gap: 1rem;
        padding: 1.5rem;
        border-top: 1px solid rgba(255, 107, 53, 0.1);
        justify-content: flex-end;
    }
    .btn-danger {
        background: var(--color-error);
        color: var(--color-white);
    }
    .btn-danger:hover {
        background: #d32f2f;
        transform: translateY(-2px);
    }
    /* Loading Overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        backdrop-filter: blur(5px);
    }
    .loading-overlay.active {
        display: flex;
    }
    .loading-spinner {
        text-align: center;
        color: var(--color-white);
    }
    .loading-spinner i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: var(--color-primary);
    }
    .loading-spinner p {
        font-size: 1.1rem;
        margin: 0;
    }
    /* Responsive Design */
    @media (max-width: 768px) {
        .filters-grid {
            grid-template-columns: 1fr;
        }
        .filters-actions {
            flex-direction: column;
        }
        .results-header {
            flex-direction: column;
            gap: 1rem;
        }
        .results-actions {
            width: 100%;
            justify-content: space-between;
        }
        .quick-filters {
            justify-content: flex-start;
        }
        .pagination {
            flex-direction: column;
            gap: 1rem;
        }
        .disks-grid {
            grid-template-columns: 1fr;
        }
        .disk-actions {
            flex-direction: column;
        }
        .modal {
            width: 95%;
            margin: 1rem;
        }
        .modal-footer {
            flex-direction: column;
        }
    }
    @media (max-width: 480px) {
        .disk-card {
            padding: 1rem;
        }
        .disk-title {
            font-size: 1.1rem;
        }
        .disk-details {
            flex-direction: column;
            gap: 0.5rem;
        }
        .btn-action span {
            display: none;
        }
        .disk-actions {
            flex-direction: row;
        }
        .disk-card.list { /* Adapta o modo lista para mobile */
            flex-direction: column;
            align-items: center;
        }
        .disk-card.list .disk-image, .disk-card.list .disk-image-placeholder {
            width: 150px;
            height: 150px;
            margin-right: 0;
            margin-bottom: 1rem;
        }
        .disk-card.list .disk-header,
        .disk-card.list .disk-content,
        .disk-card.list .disk-actions {
            width: 100%;
            text-align: center;
            align-items: center;
            justify-content: center;
        }
        .disk-card.list .disk-badges {
            justify-content: center;
        }
        .disk-card.list .disk-artist,
        .disk-card.list .disk-details {
             text-align: center;
             justify-content: center;
        }
    }
    /* Animações */
    .disk-card {
        animation: fadeInUp 0.3s ease-out;
    }
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    /* Estados de hover melhorados */
    .disk-card:hover .disk-title {
        color: var(--color-primary);
    }
    .disk-card:hover .disk-type i {
        transform: scale(1.2);
    }
    /* Smooth transitions */
    * {
        transition: var(--transition);
    }
</style>
<script>
    // JavaScript para funcionalidades dinâmicas
    document.addEventListener('DOMContentLoaded', function() {
        // Busca instantânea
        let liveSearchEnabled = false;
        let searchTimeout;
        const liveSearchToggle = document.getElementById('liveSearchToggle');
        const liveSearchText = document.getElementById('liveSearchText');
        const searchForm = document.getElementById('searchForm');
        const searchInputs = searchForm.querySelectorAll('input, select');

        // Toggle busca instantânea
        liveSearchToggle.addEventListener('click', function() {
            liveSearchEnabled = !liveSearchEnabled;
            if (liveSearchEnabled) {
                liveSearchText.textContent = 'Busca Instantânea Ativa';
                liveSearchToggle.classList.add('active');
                // Adicionar listeners para busca instantânea
                searchInputs.forEach(input => {
                    input.addEventListener('input', handleLiveSearch);
                    input.addEventListener('change', handleLiveSearch);
                });
            } else {
                liveSearchText.textContent = 'Ativar Busca Instantânea';
                liveSearchToggle.classList.remove('active');
                // Remover listeners
                searchInputs.forEach(input => {
                    input.removeEventListener('input', handleLiveSearch);
                    input.removeEventListener('change', handleLiveSearch);
                });
            }
        });

        function handleLiveSearch() {
            if (!liveSearchEnabled) return;
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                showLoading();
                searchForm.submit();
            }, 500);
        }

        // Toggle de visualização
        const viewModeButtons = document.querySelectorAll('.view-mode-btn');
        const disksGrid = document.getElementById('disksGrid'); // Elemento onde a classe é aplicada

        viewModeButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const mode = this.dataset.mode;
                // Atualizar botões ativos
                viewModeButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Alterar visualização
                if (mode === 'list') {
                    disksGrid.classList.remove('disks-grid');
                    disksGrid.classList.add('disks-list');
                    // Adiciona classe 'list' a cada card para estilização específica
                    document.querySelectorAll('.disk-card').forEach(card => card.classList.add('list'));
                } else {
                    disksGrid.classList.remove('disks-list');
                    disksGrid.classList.add('disks-grid');
                    // Remove classe 'list' de cada card
                    document.querySelectorAll('.disk-card').forEach(card => card.classList.remove('list'));
                }
                // Salvar preferência
                localStorage.setItem('viewMode', mode);
            });
        });

        // Carregar preferência de visualização
        const savedViewMode = localStorage.getItem('viewMode');
        if (savedViewMode) {
            const modeBtn = document.querySelector(`[data-mode="${savedViewMode}"]`);
            if (modeBtn) {
                modeBtn.click(); // Simula o clique para aplicar o modo salvo
            }
        }
    });

    // Funções para modal de exclusão
    let diskToDelete = null;
    function confirmDelete(diskId, diskName) {
        diskToDelete = diskId;
        document.getElementById('deleteDiskName').textContent = diskName;
        document.getElementById('deleteModal').classList.add('active');
        // Configurar botão de confirmação
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.onclick = function() {
            deleteDisk(diskId);
        };
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
        diskToDelete = null;
    }

    function deleteDisk(diskId) {
        showLoading();
        // Fazer requisição AJAX para excluir
        fetch('delete_disk.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest' // Para identificar requisições AJAX no backend
            },
            body: `disk_id=${diskId}&csrf_token=${getCSRFToken()}`
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                // Remover card do DOM
                const diskCard = document.querySelector(`[data-disk-id="${diskId}"]`);
                if (diskCard) {
                    diskCard.style.animation = 'fadeOut 0.3s ease-out';
                    setTimeout(() => {
                        diskCard.remove();
                        updateResultsCount();
                    }, 300);
                }
                showNotification('Disco excluído com sucesso!', 'success');
                closeDeleteModal();
            } else {
                showNotification(data.message || 'Erro ao excluir disco.', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showNotification('Erro de conexão. Tente novamente.', 'error');
            console.error('Error:', error);
        });
    }

    function updateResultsCount() {
        const remainingCards = document.querySelectorAll('.disk-card').length;
        const resultsInfo = document.querySelector('.results-info h2');
        if (remainingCards === 0) {
            location.reload(); // Recarregar para mostrar estado vazio
        } else {
            resultsInfo.textContent = `${remainingCards} disco${remainingCards > 1 ? 's' : ''} encontrado${remainingCards > 1 ? 's' : ''}`;
        }
    }

    function showLoading() {
        document.getElementById('loadingOverlay').classList.add('active');
    }

    function hideLoading() {
        document.getElementById('loadingOverlay').classList.remove('active');
    }

    function showNotification(message, type) {
        // Criar notificação
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
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

    function getCSRFToken() {
        // Buscar token CSRF do meta tag ou input hidden
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        if (tokenMeta) {
            return tokenMeta.getAttribute('content');
        } else if (tokenInput) {
            return tokenInput.value;
        }
        return '';
    }

    // Fechar modal com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });

    // Fechar modal clicando fora
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Animação de fade out
    const fadeOutStyle = document.createElement('style');
    fadeOutStyle.textContent = `
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-20px);
            }
        }
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
        .notification i {
            font-size: 1.2rem;
        }
    `;
    document.head.appendChild(fadeOutStyle);
</script>
<?php include 'includes/footer.php'; ?>