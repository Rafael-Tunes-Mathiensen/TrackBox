<?php
// dashboard.php
require_once 'includes/functions.php';

// Verificar se está logado
requireLogin();

$page_title = 'Dashboard';
$show_header = true;
$show_footer = true;

try {
    require_once 'config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Buscar estatísticas do usuário
    $stats = getUserStats($pdo, $_SESSION['user_id']);
    
    // Buscar discos recentes
    $stmt = $pdo->prepare("
        SELECT d.*, c.country_name 
        FROM disks d 
        LEFT JOIN countries c ON d.country_id = c.id 
        WHERE d.user_id = ? 
        ORDER BY d.created_at DESC 
        LIMIT 6
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_disks = $stmt->fetchAll();
    
} catch (Exception $e) {
    setFlashMessage('error', 'Erro ao carregar dados do dashboard.');
    $stats = ['total_disks' => 0, 'by_type' => [], 'sealed_disks' => 0, 'imported_disks' => 0];
    $recent_disks = [];
}

include 'includes/header.php';
?>

<main class="main">
    <div class="container">
        <!-- Header do Dashboard -->
        <div class="page-header">
            <div class="page-title-container">
                <div class="page-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div>
                    <h1 class="page-title">Dashboard</h1>
                    <p class="page-subtitle">Bem-vindo de volta, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-compact-disc"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_disks']; ?></div>
                        <div class="stat-label">Total de Discos</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['sealed_disks']; ?></div>
                        <div class="stat-label">Discos Lacrados</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['imported_disks']; ?></div>
                        <div class="stat-label">Discos Importados</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['by_type']['BoxSet'] ?? 0; ?></div>
                        <div class="stat-label">BoxSets</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribuição por Tipo -->
        <?php if ($stats['total_disks'] > 0): ?>
        <div class="type-distribution">
            <h2 class="section-title">
                <i class="fas fa-chart-pie"></i>
                Distribuição por Tipo
            </h2>
            <div class="type-cards">
                <?php foreach (['CD', 'LP', 'BoxSet'] as $type): ?>
                    <?php $count = $stats['by_type'][$type] ?? 0; ?>
                    <?php $percentage = $stats['total_disks'] > 0 ? round(($count / $stats['total_disks']) * 100, 1) : 0; ?>
                    <div class="type-card">
                        <div class="type-icon">
                            <i class="fas fa-<?php echo $type === 'CD' ? 'compact-disc' : ($type === 'LP' ? 'record-vinyl' : 'box-open'); ?>"></i>
                        </div>
                        <div class="type-info">
                            <div class="type-count"><?php echo $count; ?></div>
                            <div class="type-name"><?php echo $type; ?></div>
                            <div class="type-percentage"><?php echo $percentage; ?>%</div>
                        </div>
                        <div class="type-progress">
                            <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Discos Recentes -->
        <div class="recent-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-clock"></i>
                    Adicionados Recentemente
                </h2>
                <a href="search_disks.php" class="view-all-link">
                    Ver todos <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <?php if (empty($recent_disks)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-compact-disc"></i>
                </div>
                <h3>Nenhum disco cadastrado ainda</h3>
                <p>Comece sua coleção cadastrando seu primeiro disco!</p>
                <a href="register_disk.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i>
                    Cadastrar Primeiro Disco
                </a>
            </div>
            <?php else: ?>
            <div class="disks-grid">
                <?php foreach ($recent_disks as $disk): ?>
                <div class="disk-card">
                    <div class="disk-header">
                        <div class="disk-type">
                            <i class="fas fa-<?php echo $disk['type'] === 'CD' ? 'compact-disc' : ($disk['type'] === 'LP' ? 'record-vinyl' : 'box-open'); ?>"></i>
                            <span><?php echo $disk['type']; ?></span>
                        </div>
                        <?php if ($disk['is_sealed']): ?>
                        <div class="sealed-badge">
                            <i class="fas fa-certificate"></i>
                            Lacrado
                        </div>
                        <?php endif; ?>
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
                        </div>
                    </div>
                    
                    <div class="disk-actions">
                        <a href="disk_details.php?id=<?php echo $disk['id']; ?>" class="btn-action">
                            <i class="fas fa-eye"></i>
                            Ver Detalhes
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Ações Rápidas -->
        <div class="quick-actions">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i>
                Ações Rápidas
            </h2>
            <div class="actions-grid">
                <a href="register_disk.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="action-content">
                        <h3>Cadastrar Disco</h3>
                        <p>Adicione um novo CD, LP ou BoxSet à sua coleção</p>
                    </div>
                </a>
                
                <a href="search_disks.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="action-content">
                        <h3>Pesquisar</h3>
                        <p>Encontre discos específicos na sua coleção</p>
                    </div>
                </a>
                
                <a href="search_disks.php?filter=sealed" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="action-content">
                        <h3>Ver Lacrados</h3>
                        <p>Visualize todos os seus discos lacrados</p>
                    </div>
                </a>
                
                <a href="search_disks.php?filter=imported" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="action-content">
                        <h3>Ver Importados</h3>
                        <p>Visualize todos os seus discos importados</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</main>

<style>
/* Estilos específicos do Dashboard */
.stats-section {
    margin-bottom: 3rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--color-surface);
    border: 1px solid rgba(255, 107, 53, 0.1);
    border-radius: var(--border-radius-large);
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-5px);
    border-color: var(--color-primary);
    box-shadow: var(--shadow-large);
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: var(--gradient-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--color-white);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
}

.stat-label {
    color: var(--color-text-secondary);
    font-size: 0.9rem;
    font-weight: 500;
}

.type-distribution {
    margin-bottom: 3rem;
}

.type-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.type-card {
    background: var(--color-surface);
    border: 1px solid rgba(255, 107, 53, 0.1);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    text-align: center;
}

.type-icon {
    font-size: 2rem;
    color: var(--color-primary);
    margin-bottom: 1rem;
}

.type-count {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.type-name {
    color: var(--color-text);
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.type-percentage {
    color: var(--color-text-secondary);
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.type-progress {
    height: 4px;
    background: rgba(255, 107, 53, 0.2);
    border-radius: 2px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: var(--gradient-primary);
    border-radius: 2px;
    transition: width 0.3s ease;
}

.recent-section {
    margin-bottom: 3rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.view-all-link {
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: var(--transition);
}

.view-all-link:hover {
    color: var(--color-secondary);
    gap: 1rem;
}

.empty-state {
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

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: var(--color-text);
}

.empty-state p {
    color: var(--color-text-secondary);
    margin-bottom: 2rem;
}

.disks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.disk-card {
    background: var(--color-surface);
    border: 1px solid rgba(255, 107, 53, 0.1);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    transition: var(--transition);
}

.disk-card:hover {
    transform: translateY(-3px);
    border-color: var(--color-primary);
    box-shadow: var(--shadow-medium);
}

.disk-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
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

.sealed-badge {
    background: rgba(76, 175, 80, 0.1);
    color: var(--color-success);
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.disk-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.disk-artist {
    color: var(--color-text-secondary);
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

.disk-actions {
    text-align: center;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border: 1px solid var(--color-primary);
    border-radius: var(--border-radius);
    transition: var(--transition);
    font-size: 0.9rem;
}

.btn-action:hover {
    background: var(--color-primary);
    color: var(--color-white);
}

.quick-actions {
    margin-bottom: 3rem;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.action-card {
    background: var(--color-surface);
    border: 1px solid rgba(255, 107, 53, 0.1);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    text-decoration: none;
    color: inherit;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.action-card:hover {
    transform: translateY(-3px);
    border-color: var(--color-primary);
    box-shadow: var(--shadow-medium);
}

.action-icon {
    width: 50px;
    height: 50px;
    background: var(--gradient-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: var(--color-white);
    flex-shrink: 0;
}

.action-content h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.action-content p {
    color: var(--color-text-secondary);
    font-size: 0.9rem;
    margin: 0;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .disks-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .action-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<?php include 'includes/footer.php'; ?>