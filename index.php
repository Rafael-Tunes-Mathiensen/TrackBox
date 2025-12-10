<?php
// index.php
require_once 'includes/functions.php';

// NOVO: Obter informações do usuário para a página inicial
require_once 'config/database.php';
$database = new Database();
$pdo = $database->getConnection();

$current_user = null;
if (isLoggedIn()) {
    $current_user = getCurrentUser($pdo);
}

// Verifica se está visualizando uma coleção compartilhada
$target_user_id_for_stats = $_SESSION['user_id'] ?? null;
$viewing_shared_collection_on_index = false;
$shared_code_on_index = $_GET['collection_code'] ?? null;
if ($shared_code_on_index) {
    $shared_owner_id_on_index = getSharedCollectionOwnerId($pdo, $shared_code_on_index);
    if ($shared_owner_id_on_index) {
        $target_user_id_for_stats = $shared_owner_id_on_index;
        $viewing_shared_collection_on_index = true;
    }
}

$page_title = 'Início';
$show_header = true;
$show_footer = true;

include 'includes/header.php';
?>
<main class="main">
    <section class="hero">
        <div class="hero-background">
            <div class="vinyl-animation">
                <div class="vinyl vinyl-1"></div>
                <div class="vinyl vinyl-2"></div>
                <div class="vinyl vinyl-3"></div>
            </div>
        </div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-music"></i>
                    <span>Para verdadeiros colecionadores</span>
                </div>
                <h2 class="hero-title">
                    Sua coleção de <span class="highlight">música</span>
                    organizada como nunca
                </h2>
                <p class="hero-description">
                    Cadastre, organize e consulte todos os detalhes dos seus CDs, LPs e BoxSets favoritos.
                    Mantenha o controle completo da sua coleção com informações detalhadas sobre edições,
                    condições e raridades.
                </p>
                <?php if (isLoggedIn()): ?>
                    <?php
                    // NOVO: Usar $target_user_id_for_stats para as estatísticas
                    $stats = getUserStats($pdo, $_SESSION['user_id'], $target_user_id_for_stats);
                    ?>
                    <?php if ($viewing_shared_collection_on_index): ?>
                        <div class="hero-info-message">
                            Você está visualizando a coleção compartilhada de **<?php echo $shared_owner_username; ?>**.
                        </div>
                    <?php endif; ?>
                    <div class="hero-stats">
                        <div class="stat">
                            <div class="stat-number"><?php echo $stats['total_disks']; ?></div>
                            <div class="stat-label">Discos na Coleção</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number"><?php echo $stats['sealed_disks']; ?></div>
                            <div class="stat-label">Discos Lacrados</div>
                        </div>
                        <div class="stat">
                            <div class="stat-number"><?php echo $stats['imported_disks']; ?></div>
                            <div class="stat-label">Discos Importados</div>
                        </div>
                        <!-- NOVO: Estatística de favoritos -->
                        <div class="stat">
                            <div class="stat-number"><?php echo $stats['favorite_disks']; ?></div>
                            <div class="stat-label">Discos Favoritos</div>
                        </div>
                    </div>
                    <div class="hero-buttons">
                        <?php if (!$viewing_shared_collection_on_index): // Ações só para sua própria coleção ?>
                            <a href="register_disk.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i>
                                Cadastrar Disco
                            </a>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-eye"></i>
                                Ver Coleção
                            </a>
                        <?php else: // Se estiver vendo coleção compartilhada, mostra apenas "Ver Coleção" ?>
                            <a href="search_disks.php?collection_code=<?php echo $shared_code_on_index; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i>
                                Explorar Coleção
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php if ($viewing_shared_collection_on_index): ?>
                        <div class="hero-info-message">
                            Você está visualizando a coleção compartilhada de **<?php echo $shared_owner_username; ?>**.
                        </div>
                        <div class="hero-buttons">
                            <a href="search_disks.php?collection_code=<?php echo $shared_code_on_index; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i>
                                Explorar Coleção
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="hero-stats">
                            <div class="stat">
                                <div class="stat-number">1000+</div>
                                <div class="stat-label">Discos Catalogados</div>
                            </div>
                            <div class="stat">
                                <div class="stat-number">50+</div>
                                <div class="stat-label">Países Suportados</div>
                            </div>
                            <div class="stat">
                                <div class="stat-number">100%</div>
                                <div class="stat-label">Gratuito</div>
                            </div>
                        </div>
                        <div class="hero-buttons">
                            <a href="register.php" class="btn btn-primary">
                                <i class="fas fa-rocket"></i>
                                Começar Agora
                            </a>
                            <a href="login.php" class="btn btn-secondary">
                                <i class="fas fa-sign-in-alt"></i>
                                Fazer Login
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <section class="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Recursos que fazem a diferença</h2>
                <p class="section-subtitle">Tudo que você precisa para gerenciar sua coleção musical</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-compact-disc"></i>
                    </div>
                    <h3>Cadastro Completo</h3>
                    <p>Registre CDs, LPs e BoxSets com todas as informações detalhadas: edição, país de origem, imagem e status de favorito.</p>
                    <div class="feature-link">
                        <a href="<?php echo isLoggedIn() ? 'register_disk.php' : 'register.php'; ?>">
                            Cadastrar agora <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search-plus"></i>
                    </div>
                    <h3>Busca Inteligente</h3>
                    <p>Pesquise sua coleção por artista, álbum, tipo de mídia, condição e outros filtros personalizados, incluindo favoritos.</p>
                    <div class="feature-link">
                        <a href="<?php echo isLoggedIn() ? 'search_disks.php' : 'login.php'; ?>">
                            Pesquisar <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-globe-americas"></i>
                    </div>
                    <h3>Edições Internacionais</h3>
                    <p>Identifique discos importados, registre o país e continente de origem, e evite falsificações.</p>
                    <div class="feature-link">
                        <a href="#fake-detection">Saiba mais <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Avaliação de Condição</h3>
                    <p>Classifique seus discos de acordo com padrões internacionais: de "Bom" até "Mint".</p>
                    <div class="feature-link">
                        <a href="#condition-guide">Ver padrões <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3>BoxSets Especiais</h3>
                    <p>Cadastre edições limitadas e enumeradas com todos os brindes e itens extras incluídos.</p>
                    <div class="feature-link">
                        <a href="#boxset-info">Explorar <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Estatísticas</h3>
                    <p>Acompanhe o crescimento da sua coleção com gráficos e estatísticas detalhadas, incluindo discos favoritos.</p>
                    <div class="feature-link">
                        <a href="<?php echo isLoggedIn() ? 'dashboard.php' : 'login.php'; ?>">
                            Ver estatísticas <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <div class="cta-icon">
                    <i class="fas fa-headphones"></i>
                </div>
                <h2 class="cta-title">Pronto para organizar sua coleção?</h2>
                <p class="cta-description">
                    Junte-se a milhares de colecionadores que já organizam suas músicas com o TrackBox
                </p>
                <?php if (isLoggedIn()): ?>
                    <?php if (!$viewing_shared_collection_on_index): ?>
                        <a href="register_disk.php" class="btn btn-primary btn-large">
                            <i class="fas fa-plus-circle"></i>
                            Cadastrar Primeiro Disco
                        </a>
                    <?php else: ?>
                        <a href="search_disks.php?collection_code=<?php echo $shared_code_on_index; ?>" class="btn btn-primary btn-large">
                            <i class="fas fa-eye"></i>
                            Explorar Coleção
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary btn-large">
                        <i class="fas fa-play"></i>
                        Cadastrar Primeiro Disco
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
<?php include 'includes/footer.php'; ?>