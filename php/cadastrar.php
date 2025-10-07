<?php
// cadastrar.php
session_start();

// Aqui você irá adicionar a conexão com o banco de dados futuramente
// include 'config.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Processar o formulário
    $tipo_midia = $_POST['tipo_midia'] ?? '';
    $artista = $_POST['artista'] ?? '';
    $nome_disco = $_POST['nome_disco'] ?? '';
    $ano = $_POST['ano'] ?? '';
    $gravadora = $_POST['gravadora'] ?? '';
    $origem = $_POST['origem'] ?? '';
    $pais = $_POST['pais'] ?? '';
    $continente = $_POST['continente'] ?? '';
    $edicao = $_POST['edicao'] ?? '';
    $condicao = $_POST['condicao'] ?? '';
    $lacrado = isset($_POST['lacrado']) ? 1 : 0;
    $encarte = isset($_POST['encarte']) ? 1 : 0;
    $poster = isset($_POST['poster']) ? 1 : 0;
    $fotos = isset($_POST['fotos']) ? 1 : 0;
    $livreto = isset($_POST['livreto']) ? 1 : 0;
    $observacoes = $_POST['observacoes'] ?? '';
    
    // Aqui você irá inserir no banco de dados
    // Exemplo: INSERT INTO discos (artista, nome_disco, ...) VALUES (?, ?, ...)
    
    $mensagem = '<div class="alert alert-success">Disco cadastrado com sucesso!</div>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Disco - TrackBox</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1>TrackBox</h1>
                <span class="tagline">Organize sua coleção musical</span>
            </div>
            <nav class="nav">
                <a href="index.html" class="nav-link">Início</a>
                <a href="cadastrar.php" class="nav-link active">Cadastrar</a>
                <a href="colecao.php" class="nav-link">Minha Coleção</a>
                <a href="pesquisar.php" class="nav-link">Pesquisar</a>
            </nav>
        </div>
    </header>

    <main class="main">
        <section style="padding: 3rem 0;">
            <div class="container">
                <h2 class="section-title">Cadastrar Novo Disco</h2>
                
                <?php if ($mensagem): ?>
                    <?php echo $mensagem; ?>
                <?php endif; ?>

                <form method="POST" class="form-container" id="formCadastro">
                    <!-- Tipo de Mídia -->
                    <div class="form-group">
                        <label class="form-label">Tipo de Mídia *</label>
                        <div class="form-radio-group">
                            <label class="form-radio-label">
                                <input type="radio" name="tipo_midia" value="CD" required>
                                <span>CD</span>
                            </label>
                            <label class="form-radio-label">
                                <input type="radio" name="tipo_midia" value="LP">
                                <span>LP</span>
                            </label>
                            <label class="form-radio-label">
                                <input type="radio" name="tipo_midia" value="BoxSet">
                                <span>BoxSet</span>
                            </label>
                        </div>
                    </div>

                    <!-- Informações Básicas -->
                    <div class="form-group">
                        <label class="form-label" for="artista">Artista/Banda *</label>
                        <input type="text" id="artista" name="artista" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="nome_disco">Nome do Disco *</label>
                        <input type="text" id="nome_disco" name="nome_disco" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="ano">Ano de Lançamento</label>
                        <input type="number" id="ano" name="ano" class="form-input" min="1900" max="2025">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="gravadora">Gravadora</label>
                        <input type="text" id="gravadora" name="gravadora" class="form-input">
                    </div>

                    <!-- Origem -->
                    <div class="form-group">
                        <label class="form-label">Origem *</label>
                        <div class="form-radio-group">
                            <label class="form-radio-label">
                                <input type="radio" name="origem" value="nacional" id="origem_nacional" required>
                                <span>Nacional</span>
                            </label>
                            <label class="form-radio-label">
                                <input type="radio" name="origem" value="importado" id="origem_importado">
                                <span>Importado</span>
                            </label>
                        </div>
                    </div>

                    <!-- Campos para Importado -->
                    <div id="campos_importado" style="display: none;">
                        <div class="form-group">
                            <label class="form-label" for="pais">País de Origem</label>
                            <input type="text" id="pais" name="pais" class="form-input" placeholder="Ex: Japão, EUA, Reino Unido">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="continente">Continente</label>
                            <select id="continente" name="continente" class="form-select">
                                <option value="">Selecione...</option>
                                <option value="Africa">África</option>
                                <option value="America do Norte">América do Norte</option>
                                <option value="America do Sul">América do Sul</option>
                                <option value="Asia">Ásia</option>
                                <option value="Europa">Europa</option>
                                <option value="Oceania">Oceania</option>
                            </select>
                        </div>
                    </div>

                    <!-- Edição -->
                    <div class="form-group">
                        <label class="form-label" for="edicao">Edição</label>
                        <select id="edicao" name="edicao" class="form-select">
                            <option value="">Selecione...</option>
                            <option value="Primeira Edicao">Primeira Edição</option>
                            <option value="Reedicao">Reedição</option>
                            <option value="Edicao Limitada">Edição Limitada</option>
                            <option value="Edicao Especial">Edição Especial</option>
                        </select>
                    </div>

                    <!-- Condição -->
                    <div class="form-group">
                        <label class="form-label" for="condicao">Condição do Disco *</label>
                        <select id="condicao" name="condicao" class="form-select" required>
                            <option value="">Selecione...</option>
                            <option value="G">G - Bom</option>
                            <option value="G+">G+ - Bom+</option>
                            <option value="VG">VG - Muito Bom</option>
                            <option value="VG+">VG+ - Muito Bom+</option>
                            <option value="E">E - Excelente</option>
                            <option value="E+">E+ - Excelente+</option>
                            <option value="Mint">Mint - Perfeito</option>
                        </select>
                    </div>

                    <!-- Lacrado -->
                    <div class="form-group">
                        <label class="form-radio-label">
                            <input type="checkbox" name="lacrado" value="1">
                            <span>Disco Lacrado/Selado</span>
                        </label>
                    </div>

                    <!-- Itens Extras -->
                    <div class="form-group">
                        <label class="form-label">Itens Extras</label>
                        <div class="form-radio-group">
                            <label class="form-radio-label">
                                <input type="checkbox" name="encarte" value="1">
                                <span>Encarte</span>
                            </label>
                            <label class="form-radio-label">
                                <input type="checkbox" name="poster" value="1">
                                <span>Poster</span>
                            </label>
                            <label class="form-radio-label">
                                <input type="checkbox" name="fotos" value="1">
                                <span>Fotos</span>
                            </label>
                            <label class="form-radio-label">
                                <input type="checkbox" name="livreto" value="1">
                                <span>Livreto</span>
                            </label>
                        </div>
                    </div>

                    <!-- Observações -->
                    <div class="form-group">
                        <label class="form-label" for="observacoes">Observações</label>
                        <textarea id="observacoes" name="observacoes" class="form-textarea" placeholder="Informações adicionais, raridades, características especiais..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-large" style="width: 100%;">Cadastrar Disco</button>
                </form>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 TrackBox. Todos os direitos reservados.</p>
            <p class="footer-note">Plataforma para colecionadores de CDs, LPs e BoxSets</p>
        </div>
    </footer>

    <script>
        // Mostrar/ocultar campos de importado
        document.getElementById('origem_importado').addEventListener('change', function() {
            document.getElementById('campos_importado').style.display = 'block';
        });
        
        document.getElementById('origem_nacional').addEventListener('change', function() {
            document.getElementById('campos_importado').style.display = 'none';
        });
    </script>
</body>
</html>