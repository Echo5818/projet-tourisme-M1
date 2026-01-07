<?php
/**
 * Configuration de la base de données
 * Ndere City Tourism - Database Configuration
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Configuration de la base de données
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'ndere_city_tourism';
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Empêcher le clonage de l'instance
    private function __clone() {}
    
    // Empêcher la désérialisation de l'instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Fonction helper pour obtenir une connexion PDO rapidement
function getDBConnection() {
    return Database::getInstance()->getConnection();
}
