<?php
// register.php
require_once 'includes/functions.php';
// Redirecionar se já estiver logado
requireLogout();

$page_title = 'Cadastrar Conta';
$css_file = 'auth.css';
$show_header = false;
$show_footer = false;
$error_message = '';
$success_message = '';

// Processar formulário de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validações
    if (!verifyCSRFToken($csrf_token)) {
        $error_message = 'Token de segurança inválido!';
    } elseif (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error_message = 'Por favor, preencha todos os campos!';
    } elseif (!validateEmail($email)) {
        $error_message = 'E-mail inválido!';
    } elseif (strlen($password) < 8) {
        $error_message = 'A senha deve ter pelo menos 8 caracteres!';
    } elseif ($password !== $confirm_password) {
        $error_message = 'As senhas não coincidem!';
    } elseif (!$terms) {
        $error_message = 'Você deve aceitar os termos de uso!';
    } else {
        try {
            require_once 'config/database.php';
            $database = new Database();
            $pdo = $database->getConnection();

            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error_message = 'Este e-mail já está cadastrado!';
            } else {
                // Verificar se username já existe
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $error_message = 'Este nome de usuário já está em uso!';
                } else {
                    // Criar usuário
                    $hashed_password = hashPassword($password);
                    // A coluna `is_admin` foi removida, então a inserção é mais simples
                    // A coluna `share_code` também foi removida.
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$username, $email, $hashed_password, $full_name])) {
                        $success_message = 'Conta criada com sucesso! Faça login para continuar.';
                        // Limpar campos
                        $full_name = $username = $email = '';
                    } else {
                        $error_message = 'Erro ao criar conta. Tente novamente.';
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Erro no registro: " . $e->getMessage()); // Log do erro
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
    <div class="auth-card register-card">
        <div class="logo-container">
            <img src="assets/img/TrackBoxLogo.png" alt="TrackBox Logo" class="logo-img">
            <div class="logo-text">
                <h1>TrackBox</h1>
                <p>Crie sua conta</p>
            </div>
        </div>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>
        <form method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="form-group">
                <label class="form-label" for="full_name">
                    <i class="fas fa-user"></i>
                    Nome Completo
                </label>
                <input type="text" id="full_name" name="full_name" class="form-input"
                       placeholder="Digite seu nome completo" required
                       value="<?php echo htmlspecialchars($full_name ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="username">
                    <i class="fas fa-at"></i>
                    Nome de Usuário
                </label>
                <input type="text" id="username" name="username" class="form-input"
                       placeholder="Digite seu nome de usuário" required
                       value="<?php echo htmlspecialchars($username ?? ''); ?>">
            </div>
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
                       placeholder="Crie uma senha forte" required minlength="8">
                <small style="color: var(--color-text-secondary); font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                    Mínimo de 8 caracteres
                </small>
            </div>
            <div class="form-group">
                <label class="form-label" for="confirm_password">
                    <i class="fas fa-lock"></i>
                    Confirmar Senha
                </label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input"
                       placeholder="Digite a senha novamente" required minlength="8">
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="terms" name="terms" required>
                    <span class="checkmark"></span>
                    <span>Aceito os <a href="#" class="terms-link">termos de uso</a> e <a href="#" class="terms-link">política de privacidade</a>.</span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-user-plus"></i>
                Criar Conta
            </button>
        </form>
        <div class="divider">
            <span>ou</span>
        </div>
        <div class="link-text">
            Já tem uma conta?
            <a href="login.php">
                <i class="fas fa-sign-in-alt"></i>
                Fazer login
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