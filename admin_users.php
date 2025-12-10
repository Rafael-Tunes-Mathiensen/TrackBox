<?php
// admin_users.php
require_once 'includes/functions.php';

// NOVO: Apenas administradores podem acessar esta página
requireAdmin();

// Conectar ao banco de dados
require_once 'config/database.php';
$database = new Database();
$pdo = $database->getConnection();
$current_user = getCurrentUser($pdo);

$page_title = 'Gerenciar Usuários';
$show_header = true;
$show_footer = true;

$users = [];
try {
    $users = getAllUsers($pdo, $current_user['id']);
} catch (Exception $e) {
    setFlashMessage('error', 'Erro ao carregar usuários: ' . $e->getMessage());
}

include 'includes/header.php';
?>
<main class="main">
    <div class="container">
        <div class="page-header">
            <div class="page-title-container">
                <div class="page-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div>
                    <h1 class="page-title">Gerenciar Usuários</h1>
                    <p class="page-subtitle">Controle as permissões e dados dos usuários da plataforma</p>
                </div>
            </div>
        </div>

        <div class="users-management-section">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <h3>Nenhum outro usuário cadastrado</h3>
                    <p>Você é o único usuário além do administrador.</p>
                </div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Nome de Usuário</th>
                                <th>Admin</th>
                                <th>Código de Compartilhamento</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr data-user-id="<?php echo $user['id']; ?>">
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <button class="btn btn-small toggle-admin-btn <?php echo $user['is_admin'] ? 'btn-success' : 'btn-secondary'; ?>"
                                                data-user-id="<?php echo $user['id']; ?>"
                                                data-is-admin="<?php echo $user['is_admin'] ? 'true' : 'false'; ?>">
                                            <i class="fas fa-user-<?php echo $user['is_admin'] ? 'shield' : 'alt'; ?>"></i>
                                            <?php echo $user['is_admin'] ? 'Admin' : 'Usuário'; ?>
                                        </button>
                                    </td>
                                    <td class="share-code-cell"><?php echo htmlspecialchars($user['share_code'] ?? 'N/A'); ?></td>
                                    <td>
                                        <button class="btn btn-small btn-info reset-share-code-btn" data-user-id="<?php echo $user['id']; ?>" title="Gerar novo código de compartilhamento"><i class="fas fa-sync-alt"></i> Resetar Código</button>
                                        <button class="btn btn-small btn-danger delete-user-btn" data-user-id="<?php echo $user['id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>" title="Excluir Usuário"><i class="fas fa-trash"></i> Excluir</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal de Confirmação de Exclusão de Usuário -->
<div class="modal-overlay" id="deleteUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3>
                <i class="fas fa-exclamation-triangle"></i>
                Confirmar Exclusão de Usuário
            </h3>
            <button class="modal-close" onclick="closeDeleteUserModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Tem certeza que deseja excluir o usuário:</p>
            <div class="delete-user-info">
                <strong id="deleteUserName"></strong>
            </div>
            <p class="warning-text">
                <i class="fas fa-exclamation-triangle"></i>
                Esta ação excluirá o usuário e TODOS os discos associados a ele. Esta ação não pode ser desfeita!
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeDeleteUserModal()">
                <i class="fas fa-times"></i>
                Cancelar
            </button>
            <button class="btn btn-danger" id="confirmDeleteUserBtn">
                <i class="fas fa-trash"></i>
                Excluir Usuário
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Toggle Admin Status
        document.querySelectorAll('.toggle-admin-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.userId;
                const isAdmin = this.dataset.isAdmin === 'true';
                if (!confirm(`Tem certeza que deseja ${isAdmin ? 'remover' : 'conceder'} status de administrador para este usuário?`)) {
                    return;
                }
                performAdminAction('toggle_admin', userId, null, this);
            });
        });

        // Reset Share Code
        document.querySelectorAll('.reset-share-code-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.userId;
                if (!confirm('Tem certeza que deseja gerar um novo código de compartilhamento para este usuário? O código anterior será invalidado.')) {
                    return;
                }
                performAdminAction('reset_share_code', userId, null, this);
            });
        });

        // Delete User
        let userToDeleteId = null;
        document.querySelectorAll('.delete-user-btn').forEach(button => {
            button.addEventListener('click', function() {
                userToDeleteId = this.dataset.userId;
                const username = this.dataset.username;
                document.getElementById('deleteUserName').textContent = username;
                document.getElementById('deleteUserModal').classList.add('active');
            });
        });

        document.getElementById('confirmDeleteUserBtn').addEventListener('click', function() {
            if (userToDeleteId) {
                performAdminAction('delete_user', userToDeleteId, null, null);
                closeDeleteUserModal();
            }
        });

        function closeDeleteUserModal() {
            document.getElementById('deleteUserModal').classList.remove('active');
            userToDeleteId = null;
        }

        function performAdminAction(action, userId, value = null, buttonElement = null) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('user_id', userId);
            formData.append('csrf_token', csrfToken);
            if (value !== null) {
                formData.append('value', value);
            }

            // Exibir loading overlay
            showLoading();

            fetch('api/admin_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification(data.message, 'success');
                    // Atualizar UI com base na ação
                    if (action === 'toggle_admin') {
                        if (buttonElement) {
                            buttonElement.dataset.isAdmin = data.new_status ? 'true' : 'false';
                            buttonElement.classList.toggle('btn-success', data.new_status);
                            buttonElement.classList.toggle('btn-secondary', !data.new_status);
                            buttonElement.innerHTML = `<i class="fas fa-user-${data.new_status ? 'shield' : 'alt'}"></i> ${data.new_status ? 'Admin' : 'Usuário'}`;
                        }
                    } else if (action === 'reset_share_code') {
                        const row = document.querySelector(`tr[data-user-id="${userId}"]`);
                        if (row) {
                            row.querySelector('.share-code-cell').textContent = data.new_share_code;
                        }
                    } else if (action === 'delete_user') {
                        const row = document.querySelector(`tr[data-user-id="${userId}"]`);
                        if (row) {
                            row.remove();
                        }
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                showNotification('Erro de comunicação com o servidor.', 'error');
                console.error('Error:', error);
            });
        }

        // Funções de utilidade (loading e notification)
        function showLoading() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) loadingOverlay.classList.add('active');
        }

        function hideLoading() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) loadingOverlay.classList.remove('active');
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.classList.add('show'), 100);
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    });
</script>
<style>
    .users-management-section {
        background: var(--color-surface);
        border: 1px solid rgba(255, 107, 53, 0.1);
        border-radius: var(--border-radius-large);
        padding: 2rem;
        margin-bottom: 3rem;
    }
    .users-table-container {
        overflow-x: auto;
    }
    .users-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1.5rem;
    }
    .users-table th, .users-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid rgba(255, 107, 53, 0.1);
        white-space: nowrap; /* Impede que o texto quebre */
    }
    .users-table th {
        background: rgba(255, 107, 53, 0.05);
        color: var(--color-primary);
        font-weight: 600;
        font-size: 0.9rem;
    }
    .users-table td {
        color: var(--color-text-secondary);
        font-size: 0.95rem;
    }
    .users-table tr:hover {
        background: rgba(255, 107, 53, 0.02);
    }
    .btn-small {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        border-radius: var(--border-radius);
        margin-right: 0.5rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .btn-info {
        background: rgba(33, 150, 243, 0.1);
        color: #2196F3;
        border: 1px solid rgba(33, 150, 243, 0.2);
    }
    .btn-info:hover {
        background: #2196F3;
        color: var(--color-white);
    }
    .btn-success {
        background: rgba(76, 175, 80, 0.1);
        color: var(--color-success);
        border: 1px solid rgba(76, 175, 80, 0.2);
    }
    .btn-success:hover {
        background: var(--color-success);
        color: var(--color-white);
    }
    .btn-secondary {
        background: rgba(176, 176, 176, 0.1);
        color: var(--color-text-secondary);
        border: 1px solid rgba(176, 176, 176, 0.2);
    }
    .btn-secondary:hover {
        background: var(--color-text-secondary);
        color: var(--color-dark);
    }
    .btn-danger {
        background: rgba(244, 67, 54, 0.1);
        color: var(--color-error);
        border: 1px solid rgba(244, 67, 54, 0.2);
    }
    .btn-danger:hover {
        background: var(--color-error);
        color: var(--color-white);
    }
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: var(--color-background);
        border-radius: var(--border-radius-large);
        border: 1px solid rgba(255, 107, 53, 0.1);
        margin-top: 2rem;
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
    /* Loading overlay styles (se não estiver em style.css) */
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
    /* Notification styles (se não estiver em style.css) */
    .notification {
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
</style>
<?php include 'includes/footer.php'; ?>