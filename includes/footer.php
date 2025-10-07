<?php
// includes/footer.php
?>
    <?php if (isset($show_footer) && $show_footer): ?>
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="assets/img/TrackBoxLogo.png" alt="TrackBox Logo" class="footer-logo-img">
                    <div>
                        <h3>TrackBox</h3>
                        <p>Organize sua coleção musical</p>
                    </div>
                </div>
                <div class="footer-links">
                    <a href="index.php">Início</a>
                    <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="register_disk.php">Cadastrar</a>
                    <a href="search_disks.php">Pesquisar</a>
                    <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Cadastrar</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 TrackBox. Todos os direitos reservados.</p>
                <p class="footer-note">Plataforma para colecionadores de CDs, LPs e BoxSets</p>
            </div>
        </div>
    </footer>
    <?php endif; ?>
</body>
</html>