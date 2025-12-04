<?php
// search_disks.php
require_once 'includes/functions.php';

// Verificar se está logado
requireLogin();

$page_title = 'Pesquisar Discos';
$show_header = true;
$show_footer = true;

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Parâmetros de busca
    $search = sanitizeInput($_GET['search'] ?? '');
    $type_filter = sanitizeInput($_GET['type'] ?? '');
    $condition_filter = sanitizeInput($_GET['condition'] ?? '');
    $filter = sanitizeInput($_GET['filter'] ?? '');
    $country_filter = sanitizeInput($_GET['country'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = 12;
    $offset = ($page - 1) * $per_page;
    
    // Construir query de busca
    $where_conditions = ['d.user_id = ?'];
    $params = [$_SESSION['user_id']];
    
    if (!empty($search)) {
        $where_conditions[] = '(d.artist LIKE ? OR d.album_name LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($type_filter)) {
        $where_conditions[] = 'd.type = ?';
        $params[] = $type_filter;
    }
    
    if (!empty($condition_filter)) {
        $where_conditions[] = 'd.condition_disk = ?';
        $params[] = $condition_filter;
    }
    
    if ($filter === 'sealed') {
        $where_conditions[] = 'd.is_sealed = 1';
    } elseif ($filter === 'imported') {
        $where_conditions[] = 'd.is_imported = 1';
    }
    
    if (!empty($country_filter)) {
        $where_conditions[] = 'd.country_id = ?';
        $params[] = $country_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Buscar discos (incluindo campos de imagem)
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
    
    // Contar total para paginação
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM disks d 
        WHERE $where_clause
    ");
    $stmt->execute($params);
    $total_disks = $stmt->fetch()['total'];
    $total_pages = ceil($total_disks / $per_page);
    
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
                    <p class="page-subtitle">Encontre, edite e gerencie sua coleção</p>
                </div>
            </div>
        </div>

        <!-- Filtros de Busca -->
        <div class="search-filters">
            <form method="GET" class="filters-form" id="searchForm">
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
                            <option value="Mint" <?php echo $condition_filter === 'Mint' ? 'selected' : ''; ?>>Mint</option>
                            <option value="E+" <?php echo $condition_filter === 'E+' ? 'selected' : ''; ?>>E+</option>
                            <option value="E" <?php echo $condition_filter === 'E' ? 'selected' : ''; ?>>E</option>
                            <option value="VG+" <?php echo $condition_filter === 'VG+' ? 'selected' : ''; ?>>VG+</option>
                            <option value="VG" <?php echo $condition_filter === 'VG' ? 'selected' : ''; ?>>VG</option>
                            <option value="G+" <?php echo $condition_filter === 'G+' ? 'selected' : ''; ?>>G+</option>
                            <option value="G" <?php echo $condition_filter === 'G' ? 'selected' : ''; ?>>G</option>
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
                    <a href="search_disks.php" class="btn btn-secondary">
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
                <a href="search_disks.php?filter=sealed" 
                   class="quick-filter <?php echo $filter === 'sealed' ? 'active' : ''; ?>">
                    <i class="fas fa-certificate"></i>
                    Lacrados
                </a>
                <a href="search_disks.php?filter=imported" 
                   class="quick-filter <?php echo $filter === 'imported' ? 'active' : ''; ?>">
                    <i class="fas fa-globe"></i>
                    Importados
                </a>
                <a href="search_disks.php?type=BoxSet" 
                   class="quick-filter <?php echo $type_filter === 'BoxSet' ? 'active' : ''; ?>">
                    <i class="fas fa-box-open"></i>
                    BoxSets
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
                    <?php if (!empty($search) || !empty($type_filter) || !empty($condition_filter) || !empty($filter)): ?>
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
                    <a href="register_disk.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i>
                        Adicionar Disco
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (empty($disks)): ?>
            <div class="empty-results">
                <div class="empty-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>Nenhum disco encontrado</h3>
                <?php if (!empty($search) || !empty($type_filter) || !empty($condition_filter) || !empty($filter)): ?>
                    <p>Tente ajustar os filtros de busca ou</p>
                    <a href="search_disks.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Limpar Filtros
                    </a>
                <?php else: ?>
                    <p>Você ainda não cadastrou nenhum disco na sua coleção.</p>
                    <a href="register_disk.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i>
                        Cadastrar Primeiro Disco
                    </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="disks-container" id="disksContainer">
                <div class="disks-grid" id="disksGrid">
                    <?php foreach ($disks as $disk): ?>
                    <div class="disk-card" data-disk-id="<?php echo $disk['id']; ?>">
                        <!-- Imagem do disco -->
                        <div class="disk-image">
                            <?php if (!empty($disk['cover_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($disk['cover_image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($disk['album_name']); ?>"
                                     loading="lazy"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                <div class="no-image" style="display: none;">
                                    <i class="fas fa-music"></i>
                                    <span>Sem capa</span>
                                </div>
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-music"></i>
                                    <span>Sem capa</span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Badge de fonte da imagem -->
                            <?php if (!empty($disk['cover_image_source'])): ?>
                                <div class="image-source-badge">
                                    <i class="fas fa-<?php 
                                        echo $disk['cover_image_source'] === 'api' ? 'search' : 
                                            ($disk['cover_image_source'] === 'upload' ? 'upload' : 
                                            ($disk['cover_image_source'] === 'camera' ? 'camera' : 'link')); 
                                    ?>"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="disk-header">
                            <div class="disk-type">
                                <i class="fas fa-<?php echo $disk['type'] === 'CD' ? 'compact-disc' : ($disk['type'] === 'LP' ? 'record-vinyl' : 'box-open'); ?>"></i>
                                <span><?php echo $disk['type']; ?></span>
                            </div>
                            <div class="disk-badges">
                                <?php if ($disk['is_sealed']): ?>
                                <div class="badge badge-sealed">
                                    <i class="fas fa-certificate"></i>
                                    Lacrado
                                </div>
                                <?php endif; ?>
                                <?php if ($disk['is_imported']): ?>
                                <div class="badge badge-imported">
                                    <i class="fas fa-globe"></i>
                                    Importado
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
                            <a href="disk_details.php?id=<?php echo $disk['id']; ?>" class="btn-action btn-view" title="Ver Detalhes">
                                <i class="fas fa-eye"></i>
                                <span>Ver</span>
                            </a>
                            <a href="edit_disk.php?id=<?php echo $disk['id']; ?>" class="btn-action btn-edit" title="Editar Disco">
                                <i class="fas fa-edit"></i>
                                <span>Editar</span>
                            </a>
                            <button class="btn-action btn-delete" onclick="confirmDelete(<?php echo $disk['id']; ?>, '<?php echo addslashes(htmlspecialchars($disk['album_name'])); ?>')" title="Excluir Disco">
                                <i class="fas fa-trash"></i>
                                <span>Excluir</span>
                            </button>
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

/* Disk Image Styles */
.disk-image {
    position: relative;
    width: 100%;
    height: 200px;
    margin-bottom: 1rem;
    border-radius: var(--border-radius);
    overflow: hidden;
    background: var(--color-background);
}

.disk-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
    animation: imageLoad 0.5s ease-out;
}

.disk-card:hover .disk-image img {
    transform: scale(1.05);
}

.no-image {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(247, 147, 30, 0.1) 100%);
    color: var(--color-text-secondary);
    border: 2px dashed rgba(255, 107, 53, 0.3);
}

.no-image i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: var(--color-primary);
    opacity: 0.7;
}

.no-image span {
    font-size: 0.9rem;
    font-weight: 500;
}

.image-source-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: rgba(0, 0, 0, 0.7);
    color: var(--color-white);
    padding: 0.25rem 0.5rem;
    border-radius: 50px;
    font-size: 0.8rem;
    backdrop-filter: blur(10px);
}

@keyframes imageLoad {
    from {
        opacity: 0;
        transform: scale(1.1);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.disk-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
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

.disk-content {
    margin-bottom: 1.5rem;
}

.disk-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.disk-artist {
    color: var(--color-primary);
    font-weight: 500;
    margin-bottom: 1rem;
}

.disk-details {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--color-text-secondary);
    font-size: 0.85rem;
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
    
    .disk-image {
        height: 150px;
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
    
    .disk-image {
        height: 120px;
    }
    
    .no-image i {
        font-size: 1.5rem;
    }
    
    .no-image span {
        font-size: 0.8rem;
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

.notification i {
    font-size: 1.2rem;
}

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
    const disksContainer = document.getElementById('disksContainer');
    const disksGrid = document.getElementById('disksGrid');
    
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
            } else {
                disksGrid.classList.remove('disks-list');
                disksGrid.classList.add('disks-grid');
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
            modeBtn.click();
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
</script>

<?php include 'includes/footer.php'; ?>