<?php
/**
 * config.php - Arquivo de Configuração do Banco de Dados
 * 
 * Este arquivo será usado para conectar ao banco MySQL
 * Altere as informações conforme seu ambiente
 */

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'trackbox');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações de Timezone
date_default_timezone_set('America/Sao_Paulo');

// Função para conectar ao banco de dados
function conectarDB() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Erro ao conectar ao banco de dados: " . $e->getMessage());
    }
}

// Você pode descomentar a linha abaixo quando criar o banco de dados
// $pdo = conectarDB();
?>