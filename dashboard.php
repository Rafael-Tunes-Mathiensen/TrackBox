<?php
// dashboard.php
require_once 'includes/functions.php';

// Verificar se está logado
requireLogin();

// NOVO: Obter informações do usuário logado
require_once 'config/database.php';
$database = new Database();
$pdo = $database->getConnection();
$current_user = getCurrentUser($pdo);

// NOVO: Verifica se está visualizando uma coleção compartilhada
$shared_collection_owner_id = null;
$viewing_shared_collection_on_dashboard = false;
$shared_code = $_GET['collection_code'] ?? null;
if ($shared_code) {
    $shared_owner_id_from_code = getSharedCollectionOwnerId($pdo, $shared_code);
    if ($shared_owner_id_from_code) {
        $shared_collection_owner_id = $shared_owner_id_from_code;
        $viewing_shared_collection_on_dashboard = true;
    } else {
        setFlashMessage('error', 'Código de coleção compartilhada inválido.');
        header('Location: dashboard.php');
        exit();
    }
}
$target_user_id = $shared_collection_owner_id ?? $_SESSION['user_id'];

$page_title = 'Dashboard';
$show_header = true;
$show_footer = true;
try {
    // Buscar estatísticas do usuário (ou da coleção compartilhada)
    $stats = getUserStats($pdo, $_SESSION['user_id'], $shared_collection_owner_id);

    // Buscar discos recentes (da coleção do usuário logado ou da compartilhada)
    $stmt = $pdo->prepare("
        SELECT d.*, c.country_name
        FROM disks d
        LEFT JOIN countries c ON d.country_id = c.id
        WHERE d.user_id = ?
        ORDER BY d.created_at DESC
        LIMIT 6
    ");
    $stmt->execute([$target_user_id]);
    $recent_disks = $stmt->fetchAll();
} catch (Exception $e) {
    setFlashMessage('error', 'Erro ao carregar dados do dashboard.');
    $stats = ['total_disks' => 0, 'by_type' => [], 'sealed_disks' => 0, 'imported_disks' => 0, 'favorite_disks' => 0]; // NOVO: favorite_disks
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
                    <p class="page-subtitle">Bem-vindo de volta, <?php echo htmlspecialchars($current_user['full_name']); ?></p>
                    <?php if ($current_user && $current_user['share_code'] && !$viewing_shared_collection_on_dashboard): ?>
                        <div class="share-info">
                            Seu código de compartilhamento: <span class="share-code"><?php echo htmlspecialchars($current_user['share_code']); ?> <i class="fas fa-copy copy-icon"></i></span>
                            <span class="share-hint">Compartilhe este código para permitir que outros visualizem sua coleção!</span>
                        </div>
                    <?php endif; ?>
                     <?php if ($viewing_shared_collection_on_dashboard): ?>
                        <div class="viewing-shared-message">
                            Você está visualizando a coleção de <?php echo htmlspecialchars($shared_owner_username); ?>.
                        </div>
                    <?php endif; ?>
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
                <!-- NOVO: Cartão de estatísticas de BoxSets -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['by_type']['BoxSet'] ?? 0; ?></div>
                        <div class="stat-label">BoxSets</div>
                    </div>
                </div>
                 <!-- NOVO: Cartão de estatísticas de favoritos -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['favorite_disks']; ?></div>
                        <div class="stat-label">Discos Favoritos</div>
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
                        <?php
                        $count = $stats['by_type'][$type] ?? 0;
                        $percentage = $stats['total_disks'] > 0 ? round(($count / $stats['total_disks']) * 100) : 0;
                        ?>
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
                <a href="search_disks.php<?php echo $viewing_shared_collection_on_dashboard ? '?collection_code=' . $shared_code : ''; ?>" class="view-all-link">
                    Ver todos <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <?php if (empty($recent_disks)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-compact-disc"></i>
                    </div>
                    <h3>Nenhum disco cadastrado ainda</h3>
                    <?php if (!$viewing_shared_collection_on_dashboard): ?>
                        <p>Comece sua coleção cadastrando seu primeiro disco!</p>
                        <a href="register_disk.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i>
                            Cadastrar Primeiro Disco
                        </a>
                    <?php else: ?>
                        <p>A coleção compartilhada ainda não possui discos.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="disks-grid">
                    <?php foreach ($recent_disks as $disk): ?>
                        <div class="disk-card">
                            <?php if ($disk['image_path']): // NOVO: Exibir imagem ?>
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
                                </div>
                            </div>
                            <div class="disk-content">
                                <h3 class="disk-title"><?php echo htmlspecialchars($disk['album_name']); ?></h3>
                                <p class="disk-artist"><?php echo htmlspecialchars($disk['artist']); ?></p>
                                <div class="disk-details">
                                    <?php if ($disk['year']): ?>
                                        <span class="detail-item">
                                            <i class="fas fa-calendar"></i> <?php echo $disk['year']; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($disk['country_name']): ?>
                                        <span class="detail-item">
                                            <i class="fas fa-globe"></i> <?php echo htmlspecialchars($disk['country_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="detail-item">
                                        <i class="fas fa-star"></i> <?php echo formatCondition($disk['condition_disk']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="disk-actions">
                                <a href="disk_details.php?id=<?php echo $disk['id']; ?><?php echo $viewing_shared_collection_on_dashboard ? '&collection_code=' . $shared_code : ''; ?>" class="btn-action">
                                    <i class="fas fa-eye"></i> Ver Detalhes
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <!-- Ações Rápidas -->
        <?php if (!$viewing_shared_collection_on_dashboard): // Ações rápidas apenas para a própria coleção ?>
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
                     <!-- NOVO: Ação rápida para ver favoritos -->
                    <a href="search_disks.php?filter=favorite" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="action-content">
                            <h3>Ver Favoritos</h3>
                            <p>Visualize todos os seus discos marcados como favoritos</p>
                        </div>
                    </a>
                </div>
            </div>
        <?php endif; ?>
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
        display: flex;
        flex-direction: column;
    }
    .disk-card:hover {
        transform: translateY(-3px);
        border-color: var(--color-primary);
        box-shadow: var(--shadow-medium);
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
    .sealed-badge, .badge-sealed { /* Combinando com o estilo do search_disks */
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
    .badge-imported {
        background: rgba(33, 150, 243, 0.1);
        color: #2196F3;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    .disk-content {
        flex-grow: 1; /* Garante que o conteúdo ocupe o espaço restante */
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
        margin-top: auto; /* Empurra os botões para baixo */
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
    /* NOVO: Estilos para Share Code no Dashboard */
    .share-info {
        margin-top: 1rem;
        padding: 0.75rem 1.25rem;
        background: rgba(33, 150, 243, 0.1);
        border-radius: var(--border-radius);
        color: #2196F3;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .share-info .share-code {
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(33, 150, 243, 0.2);
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        transition: var(--transition);
    }
    .share-info .share-code:hover {
        background: #2196F3;
        color: var(--color-white);
    }
    .share-info .share-code .copy-icon {
        font-size: 0.8rem;
    }
    .share-info .share-hint {
        font-size: 0.85rem;
        color: var(--color-text-secondary);
        margin-left: 1rem;
    }
    .viewing-shared-message {
        margin-top: 1rem;
        padding: 0.75rem 1.25rem;
        background: rgba(156, 39, 176, 0.1);
        border-radius: var(--border-radius);
        color: #9C27B0;
        font-size: 0.95rem;
        font-weight: 500;
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
        .share-info {
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }
    }
</style>
<script>
    // Lógica para copiar o share_code no dashboard
    document.addEventListener('DOMContentLoaded', function() {
        const shareCodeElement = document.querySelector('.share-code');
        if (shareCodeElement) {
            shareCodeElement.addEventListener('click', function() {
                const code = shareCodeElement.textContent.trim().split(' ')[0]; // Pega só o código
                navigator.clipboard.writeText(code).then(() => {
                    alert('Código de compartilhamento copiado: ' + code);
                }).catch(err => {
                    console.error('Falha ao copiar o código: ', err);
                });
            });
        }
    });
</script>
<?php include 'includes/footer.php'; ?>