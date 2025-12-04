<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title><?php echo isset($page_title) ? $page_title . ' - TrackBox' : 'TrackBox - Sua Coleção de CDs e LPs'; ?></title>
    <link rel="icon" type="image/x-icon" href="assets/img/TrackBox.ico">
    <link rel="stylesheet" href="assets/css/<?php echo isset($css_file) ? $css_file : 'style.css'; ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php if (isset($show_header) && $show_header): ?>
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="assets/img/TrackBoxLogo.png" alt="TrackBox Logo" class="logo-img">
                <div class="logo-text">
                    <h1>TrackBox</h1>
                    <span class="tagline">Organize sua coleção musical</span>
                </div>
            </div>
            
            <?php if (isLoggedIn()): ?>
            <nav class="nav">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="register_disk.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'register_disk.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span>Cadastrar</span>
                </a>
                <a href="search_disks.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'search_disks.php' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i>
                    <span>Pesquisar</span>
                </a>
            </nav>
            
            <div class="user-menu">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span>Olá, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
                <a href="logout.php" class="logout-btn" title="Sair">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
            <?php else: ?>
            <nav class="nav">
                <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Início</span>
                </a>
                <a href="login.php" class="nav-link">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
                <a href="register.php" class="nav-link">
                    <i class="fas fa-user-plus"></i>
                    <span>Cadastrar</span>
                </a>
            </nav>
            <?php endif; ?>
        </div>
    </header>
    <?php endif; ?>
    
    <?php
    // Exibir mensagens flash
    $flash = getFlashMessage();
    if ($flash):
    ?>
    <div class="flash-message alert alert-<?php echo $flash['type']; ?>">
        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <span><?php echo htmlspecialchars($flash['message']); ?></span>
    </div>
    
    <style>
    .flash-message {
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--color-surface);
        border: 1px solid;
        border-radius: var(--border-radius);
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        z-index: 10001;
        box-shadow: var(--shadow-large);
        animation: slideInRight 0.3s ease-out;
        max-width: 400px;
    }
    
    .flash-message.alert-success {
        border-color: var(--color-success);
        color: var(--color-success);
    }
    
    .flash-message.alert-error {
        border-color: var(--color-error);
        color: var(--color-error);
    }
    
    .flash-message i {
        font-size: 1.2rem;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    /* Auto-hide after 5 seconds */
    .flash-message {
        animation: slideInRight 0.3s ease-out, slideOutRight 0.3s ease-out 4.7s forwards;
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    </style>
    <?php endif; ?>