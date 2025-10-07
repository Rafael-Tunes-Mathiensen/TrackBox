<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'trackbox';
    private $username = 'root'; // Ajuste conforme sua configuração
    private $password = '';     // Ajuste conforme sua configuração
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
        } catch(PDOException $exception) {
            echo "Erro na conexão: " . $exception->getMessage();
            die();
        }
        
        return $this->conn;
    }
}
?>