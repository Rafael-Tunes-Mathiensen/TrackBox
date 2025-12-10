<?php
// login.php
require_once 'includes/functions.php';
// Redirecionar se já estiver logado
requireLogout();

$page_title = 'Login';
$css_file = 'auth.css';
$show_header = false;
$show_footer = false;
$error_message = '';

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Verificar token CSRF
    if (!verifyCSRFToken($csrf_token)) {
        $error_message = 'Token de segurança inválido!';
    } elseif (empty($email) || empty($password)) {
        $error_message = 'Por favor, preencha todos os campos!';
    } elseif (!validateEmail($email)) {
        $error_message = 'E-mail inválido!';
    } else {
        try {
            require_once 'config/database.php';
            $database = new Database();
            $pdo = $database->getConnection();

            // Buscar usuário (agora sem is_admin e share_code)
            $stmt = $pdo->prepare("SELECT id, username, email, password, full_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($password, $user['password'])) {
                // Login bem-sucedido
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Redirecionar para o dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = 'E-mail ou senha incorretos!';
            }
        } catch (Exception $e) {
            error_log("Erro no login: " . $e->getMessage()); // Log do erro
            $error_message = 'Erro interno do servidor. Tente novamente.';
        }
    }
}

include 'includes/header.php';
?>
<div class="auth-background">
    <div class="vinyl-animation">
        <div class="vinyl vinyl-1"></div>
        <div class="vinyl vinyl-2"></div>
        <div class="vinyl vinyl-3"></div>
    </div>
</div>
<div class="auth-container">
    <div class="auth-card">
        <div class="logo-container">
            <img src="assets/img/TrackBoxLogo.png" alt="TrackBox Logo" class="logo-img">
            <div class="logo-text">
                <h1>TrackBox</h1>
                <p>Entre na sua conta</p>
            </div>
        </div>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>
        <form method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="form-group">
                <label class="form-label" for="email">
                    <i class="fas fa-envelope"></i>
                    E-mail
                </label>
                <input type="email" id="email" name="email" class="form-input"
                       placeholder="seu@email.com" required
                       value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="password">
                    <i class="fas fa-lock"></i>
                    Senha
                </label>
                <input type="password" id="password" name="password" class="form-input"
                       placeholder="Digite sua senha" required>
            </div>
            <div class="form-footer">
                <label class="checkbox-label">
                    <input type="checkbox" id="remember" name="remember">
                    <span class="checkmark"></span>
                    <span>Lembrar-me</span>
                </label>
                <a href="#" class="forgot-link">
                    <i class="fas fa-question-circle"></i>
                    Esqueceu a senha?
                </a>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i>
                Entrar
            </button>
        </form>
        <div class="divider">
            <span>ou</span>
        </div>
        <div class="link-text">
            Não tem uma conta?
            <a href="register.php">
                <i class="fas fa-user-plus"></i>
                Cadastre-se
            </a>
        </div>
        <div class="home-link">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i>
                Voltar para o início
            </a>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>