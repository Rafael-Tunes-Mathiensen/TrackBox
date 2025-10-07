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
                    <p class="page-subtitle">Encontre discos na sua coleção</p>
                </div>
            </div>
        </div>

        <!-- Filtros de Busca -->
        <div class="search-filters">
            <form method="GET" class="filters-form">
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
            <div class="disks-grid">
                <?php foreach ($disks as $disk): ?>
                <div class="disk-card">
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
                        <a href="disk_details.php?id=<?php echo $disk['id']; ?>" class="btn-action btn-primary">
                            <i class="fas fa-eye"></i>
                            Ver Detalhes
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
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

.disk-badges {
    display: flex;
    gap: 0.5rem;
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
    
    .quick-filters {
        justify-content: flex-start;
    }
    
    .pagination {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>