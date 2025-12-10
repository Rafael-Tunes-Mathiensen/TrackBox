<?php
// dashboard.php
require_once 'includes/functions.php';
// Verificar se está logado
requireLogin();

// Conectar ao banco de dados
require_once 'config/database.php';
$database = new Database();
$pdo = $database->getConnection();
$current_user = getCurrentUser($pdo);

// NOTA: Toda a lógica relacionada a "coleções compartilhadas" foi removida.
// O dashboard agora sempre exibirá a coleção do usuário logado.

$page_title = 'Dashboard';
$show_header = true;
$show_footer = true;

try {
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
    setFlashMessage('error', 'Erro ao carregar dados do dashboard: ' . $e->getMessage());
    $stats = ['total_disks' => 0, 'by_type' => [], 'sealed_disks' => 0, 'imported_disks' => 0, 'favorite_disks' => 0];
    $recent_disks = [];
}

include 'includes/header.php';
?>
<main class="main">
    <div class="container">
        <!-- Header do Dashboard -->
        <div class="page-header dashboard-header">
            <div class="page-title-container">
                <div class="page-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div>
                    <h1 class="page-title">Dashboard</h1>
                    <p class="page-subtitle">Bem-vindo de volta, <?php echo htmlspecialchars($current_user['full_name']); ?>!</p>
                </div>
            </div>
            <div class="dashboard-actions">
                <a href="register_disk.php" class="btn btn-primary btn-large">
                    <i class="fas fa-plus-circle"></i> Novo Disco
                </a>
                <a href="search_disks.php" class="btn btn-secondary btn-large">
                    <i class="fas fa-search"></i> Ver Coleção Completa
                </a>
            </div>
        </div>

        <!-- Estatísticas da Coleção -->
        <section class="stats-section">
            <h2 class="section-title"><i class="fas fa-chart-bar"></i> Visão Geral da Coleção</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-compact-disc"></i></div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_disks']; ?></div>
                        <div class="stat-label">Total de Discos</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-sealed"><i class="fas fa-certificate"></i></div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['sealed_disks']; ?></div>
                        <div class="stat-label">Discos Lacrados</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-imported"><i class="fas fa-globe"></i></div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['imported_disks']; ?></div>
                        <div class="stat-label">Discos Importados</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-boxset"><i class="fas fa-box-open"></i></div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['by_type']['BoxSet'] ?? 0; ?></div>
                        <div class="stat-label">BoxSets</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-favorite"><i class="fas fa-star"></i></div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['favorite_disks']; ?></div>
                        <div class="stat-label">Discos Favoritos</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Distribuição por Tipo (Gráfico - se houver dados) -->
        <?php if ($stats['total_disks'] > 0): ?>
        <section class="type-distribution-section">
            <h2 class="section-title"><i class="fas fa-chart-pie"></i> Distribuição por Tipo</h2>
            <div class="type-cards">
                <?php foreach (['CD', 'LP', 'BoxSet'] as $type): ?>
                    <?php
                    $count = $stats['by_type'][$type] ?? 0;
                    $percentage = $stats['total_disks'] > 0 ? round(($count / $stats['total_disks']) * 100) : 0;
                    $icon_class = '';
                    switch ($type) {
                        case 'CD': $icon_class = 'fa-compact-disc'; break;
                        case 'LP': $icon_class = 'fa-record-vinyl'; break;
                        case 'BoxSet': $icon_class = 'fa-box-open'; break;
                    }
                    ?>
                    <div class="type-card">
                        <div class="type-icon"><i class="fas <?php echo $icon_class; ?>"></i></div>
                        <div class="type-info">
                            <div class="type-name"><?php echo $type; ?></div>
                            <div class="type-count"><?php echo $count; ?> Discos</div>
                            <div class="type-percentage"><?php echo $percentage; ?>%</div>
                        </div>
                        <div class="type-progress">
                            <div class="progress-bar" style="width: <?php echo $percentage; ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Discos Recentes -->
        <section class="recent-disks-section">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-history"></i> Adicionados Recentemente</h2>
                <a href="search_disks.php" class="view-all-link">Ver todos <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php if (empty($recent_disks)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-compact-disc"></i></div>
                    <h3>Nenhum disco cadastrado ainda</h3>
                    <p>Comece sua coleção cadastrando seu primeiro disco!</p>
                    <a href="register_disk.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Cadastrar Primeiro Disco</a>
                </div>
            <?php else: ?>
                <div class="disks-grid">
                    <?php foreach ($recent_disks as $disk): ?>
                        <div class="disk-card">
                            <?php if ($disk['image_path']): ?>
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
                                    <?php if ($disk['is_sealed']): ?><span class="badge badge-sealed"><i class="fas fa-certificate"></i> Lacrado</span><?php endif; ?>
                                    <?php if ($disk['is_imported']): ?><span class="badge badge-imported"><i class="fas fa-globe"></i> Importado</span><?php endif; ?>
                                    <?php if ($disk['is_favorite']): ?><span class="badge badge-favorite"><i class="fas fa-star"></i> Favorito</span><?php endif; ?>
                                </div>
                            </div>
                            <div class="disk-content">
                                <h3 class="disk-title"><?php echo htmlspecialchars($disk['album_name']); ?></h3>
                                <p class="disk-artist"><?php echo htmlspecialchars($disk['artist']); ?></p>
                                <div class="disk-details">
                                    <?php if ($disk['year']): ?><span class="detail-item"><i class="fas fa-calendar"></i> <?php echo $disk['year']; ?></span><?php endif; ?>
                                    <?php if ($disk['country_name']): ?><span class="detail-item"><i class="fas fa-flag"></i> <?php echo htmlspecialchars($disk['country_name']); ?></span><?php endif; ?>
                                    <span class="detail-item"><i class="fas fa-hand-sparkles"></i> <?php echo formatCondition($disk['condition_disk']); ?></span>
                                </div>
                            </div>
                            <div class="disk-actions">
                                <a href="disk_details.php?id=<?php echo $disk['id']; ?>" class="btn-action btn-view"><i class="fas fa-eye"></i> Ver</a>
                                <a href="edit_disk.php?id=<?php echo $disk['id']; ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i> Editar</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Ações Rápidas -->
        <section class="quick-actions-section">
            <h2 class="section-title"><i class="fas fa-bolt"></i> Ações Rápidas</h2>
            <div class="actions-grid">
                <a href="register_disk.php" class="action-card">
                    <div class="action-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="action-content">
                        <h3>Cadastrar Disco</h3>
                        <p>Adicione um novo item à sua coleção</p>
                    </div>
                </a>
                <a href="search_disks.php?filter=sealed" class="action-card">
                    <div class="action-icon action-icon-sealed"><i class="fas fa-certificate"></i></div>
                    <div class="action-content">
                        <h3>Ver Lacrados</h3>
                        <p>Explore seus discos lacrados</p>
                    </div>
                </a>
                <a href="search_disks.php?filter=imported" class="action-card">
                    <div class="action-icon action-icon-imported"><i class="fas fa-globe"></i></div>
                    <div class="action-content">
                        <h3>Ver Importados</h3>
                        <p>Descubra suas edições internacionais</p>
                    </div>
                </a>
                <a href="search_disks.php?filter=favorite" class="action-card">
                    <div class="action-icon action-icon-favorite"><i class="fas fa-star"></i></div>
                    <div class="action-content">
                        <h3>Ver Favoritos</h3>
                        <p>Acesse seus discos preferidos rapidamente</p>
                    </div>
                </a>
            </div>
        </section>
    </div>
</main>
<style>
/* Estilos Específicos do Dashboard */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid rgba(255, 107, 53, 0.1);
    margin-bottom: 3rem;
}
.dashboard-header .page-title-container {
    margin-bottom: 0;
}
.dashboard-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.dashboard-actions .btn {
    padding: 0.9rem 1.8rem;
    font-size: 0.95rem;
}

/* Seções Gerais */
.section-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: 2.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-bottom: 2px solid rgba(255, 107, 53, 0.2);
    padding-bottom: 0.75rem;
}
.section-title i {
    color: var(--color-primary);
    font-size: 1.5rem;
}

/* Stats Section */
.stats-section {
    margin-bottom: 4rem;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
}
.stat-card {
    background: var(--color-surface);
    border: 1px solid rgba(255, 107, 53, 0.1);
    border-radius: var(--border-radius-large);
    padding: 1.8rem;
    display: flex;
    align-items: center;
    gap: 1.2rem;
    transition: var(--transition);
    box-shadow: var(--shadow-small);
}
.stat-card:hover {
    transform: translateY(-8px);
    border-color: var(--color-primary);
    box-shadow: var(--shadow-medium);
}
.stat-icon {
    width: 55px;
    height: 55px;
    background: var(--gradient-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--color-white);
    flex-shrink: 0;
}
.stat-icon-sealed { background: linear-gradient(135deg, var(--color-success) 0%, #4CAF50 100%); }
.stat-icon-imported { background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%); }
.stat-icon-boxset { background: linear-gradient(135deg, #9C27B0 0%, #673AB7 100%); }
.stat-icon-favorite { background: linear-gradient(135deg, var(--color-accent) 0%, #FFD23F 100%); }

.stat-content {
    flex-grow: 1;
}
.stat-number {
    font-size: 2.2rem;
    font-weight: 700;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1.1;
    margin-bottom: 0.2rem;
}
.stat-label {
    color: var(--color-text-secondary);
    font-size: 0.9rem;
    font-weight: 500;
}

/* Type Distribution Section */
.type-distribution-section {
    margin-bottom: 4rem;
}
.type-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}
.type-card {
    background: var(--color-surface);
    border: 1px solid rgba(255, 107, 53, 0.1);
    border-radius: var(--border-radius-large);
    padding: 1.5rem;
    text-align: center;
    transition: var(--transition);
    box-shadow: var(--shadow-small);
}
.type-card:hover {
    transform: translateY(-5px);
    border-color: var(--color-secondary);
    box-shadow: var(--shadow-medium);
}
.type-card .type-icon {
    font-size: 2.5rem;
    color: var(--color-primary);
    margin-bottom: 0.8rem;
    opacity: 0.8;
}
.type-card .type-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.4rem;
}
.type-card .type-count {
    font-size: 0.9rem;
    color: var(--color-text-secondary);
    margin-bottom: 0.8rem;
}
.type-card .type-percentage {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: 1rem;
}
.type-progress {
    height: 6px;
    background: rgba(255, 107, 53, 0.1);
    border-radius: 3px;
    overflow: hidden;
}
.type-progress .progress-bar {
    height: 100%;
    background: var(--gradient-primary);
    border-radius: 3px;
    transition: width 0.5s ease-out;
}

/* Recent Disks Section */
.recent-disks-section {
    margin-bottom: 4rem;
}
.recent-disks-section .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2.5rem;
    border-bottom: none; /* Remove bottom border from generic section-title */
    padding-bottom: 0;
}
.recent-disks-section .section-title {
    margin-bottom: 0;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid rgba(255, 107, 53, 0.2);
}
.view-all-link {
    color: var(--color-text-secondary);
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: var(--transition);
    padding-bottom: 0.75rem; /* Align with section title border */
}
.view-all-link:hover {
    color: var(--color-primary);
    transform: translateX(5px);
}

.disks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}
.disk-card {
    background: var(--color-surface);
    border: 1px solid rgba(255, 107, 53, 0.1);
    border-radius: var(--border-radius-large);
    padding: 1.5rem;
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-small);
}
.disk-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--gradient-primary);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease-out;
}
.disk-card:hover::before {
    transform: scaleX(1);
}
.disk-card:hover {
    transform: translateY(-8px);
    border-color: var(--color-primary);
    box-shadow: var(--shadow-medium);
}
.disk-image, .disk-image-placeholder {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    background-color: var(--color-darker);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-text-secondary);
    font-size: 2.5rem;
}
.disk-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.8rem;
}
.disk-type {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: var(--color-primary);
    font-weight: 600;
    font-size: 0.85rem;
}
.disk-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.badge {
    padding: 0.3rem 0.7rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.badge-sealed { background: rgba(76, 175, 80, 0.15); color: var(--color-success); }
.badge-imported { background: rgba(33, 150, 243, 0.15); color: #2196F3; }
.badge-favorite { background: rgba(255, 215, 63, 0.15); color: var(--color-accent); }

.disk-content {
    flex-grow: 1;
    margin-bottom: 1rem;
}
.disk-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 0.3rem;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.disk-artist {
    color: var(--color-text-secondary);
    font-weight: 500;
    font-size: 0.9rem;
    margin-bottom: 0.8rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.disk-details {
    display: flex;
    flex-wrap: wrap;
    gap: 0.8rem;
    font-size: 0.8rem;
    color: var(--color-text-secondary);
}
.detail-item {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}
.detail-item i {
    color: var(--color-primary);
    font-size: 0.85rem;
}
.disk-actions {
    display: flex;
    gap: 0.7rem;
    justify-content: flex-end; /* Align buttons to the right */
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 107, 53, 0.08);
}
.btn-action {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.6rem 1.1rem;
    border-radius: var(--border-radius);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.85rem;
    transition: var(--transition);
    border: none;
    cursor: pointer;
}
.btn-action i {
    font-size: 0.9rem;
}
.btn-view { background: rgba(33, 150, 243, 0.15); color: #2196F3; }
.btn-view:hover { background: #2196F3; color: var(--color-white); }
.btn-edit { background: rgba(255, 152, 0, 0.15); color: var(--color-warning); }
.btn-edit:hover { background: var(--color-warning); color: var(--color-white); }

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--color-surface);
    border-radius: var(--border-radius-large);
    border: 1px solid rgba(255, 107, 53, 0.1);
    margin-top: 2rem;
    box-shadow: var(--shadow-small);
}
.empty-state .empty-icon {
    font-size: 4.5rem;
    color: var(--color-primary);
    margin-bottom: 1.5rem;
    opacity: 0.7;
}
.empty-state h3 {
    font-size: 1.6rem;
    margin-bottom: 1rem;
    color: var(--color-text);
}
.empty-state p {
    color: var(--color-text-secondary);
    margin-bottom: 2rem;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

/* Quick Actions Section */
.quick-actions-section {
    margin-bottom: 4rem;
}
.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}
.action-card {
    background: var(--color-surface);
    border: 1px solid rgba(255, 107, 53, 0.1);
    border-radius: var(--border-radius-large);
    padding: 2rem;
    text-decoration: none;
    color: inherit;
    transition: var(--transition);
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
    box-shadow: var(--shadow-small);
}
.action-card:hover {
    transform: translateY(-8px);
    border-color: var(--color-secondary);
    box-shadow: var(--shadow-medium);
}
.action-icon {
    width: 55px;
    height: 55px;
    background: var(--gradient-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--color-white);
    flex-shrink: 0;
}
.action-icon-sealed { background: linear-gradient(135deg, var(--color-success) 0%, #4CAF50 100%); }
.action-icon-imported { background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%); }
.action-icon-favorite { background: linear-gradient(135deg, var(--color-accent) 0%, #FFD23F 100%); }

.action-content h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 0.4rem;
}
.action-content p {
    color: var(--color-text-secondary);
    font-size: 0.9rem;
    margin: 0;
    line-height: 1.5;
}

/* Responsividade */
@media (max-width: 1200px) {
    .disks-grid {
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    }
}

@media (max-width: 992px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .dashboard-actions {
        width: 100%;
        justify-content: flex-start;
    }
    .stats-grid, .type-cards, .actions-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    .disk-card .disk-title {
        white-space: normal;
    }
}

@media (max-width: 768px) {
    .container {
        padding: 0 15px;
    }
    .page-header .page-title-container {
        flex-direction: column;
        text-align: center;
    }
    .page-header .page-icon {
        margin-bottom: 1rem;
    }
    .dashboard-actions {
        flex-direction: column;
        align-items: center;
    }
    .dashboard-actions .btn {
        width: 100%;
        max-width: 300px;
    }
    .section-title {
        font-size: 1.5rem;
        justify-content: center;
    }
    .section-title i {
        font-size: 1.2rem;
    }
    .stats-grid, .type-cards, .disks-grid, .actions-grid {
        grid-template-columns: 1fr;
    }
    .disk-actions {
        justify-content: center;
    }
    .action-card {
        flex-direction: column;
        text-align: center;
        align-items: center;
    }
    .action-card .action-icon {
        margin-bottom: 1rem;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 2rem;
    }
    .page-subtitle {
        font-size: 1rem;
    }
    .stat-number {
        font-size: 1.8rem;
    }
    .stat-label {
        font-size: 0.8rem;
    }
    .disk-title {
        font-size: 1rem;
    }
    .disk-artist {
        font-size: 0.8rem;
    }
    .disk-details {
        font-size: 0.75rem;
        gap: 0.5rem;
    }
    .btn-action {
        padding: 0.5rem 0.8rem;
        font-size: 0.75rem;
    }
    .action-content h3 {
        font-size: 1rem;
    }
    .action-content p {
        font-size: 0.8rem;
    }
}
</style>
<?php include 'includes/footer.php'; ?>