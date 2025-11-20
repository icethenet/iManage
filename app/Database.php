<?php
/**
 * Database Connection Class
 */

class Database {
    private $connection;
    private $host;
    private $database;
    private $username;
    private $password;
    private $charset;
    private static $instance;

    private function __construct() {
        $projectRoot = dirname(__DIR__);
        $configPath = $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

        if (!file_exists($configPath)) {
            throw new Exception("Config file not found at: $configPath. Please ensure 'config/database.php' exists.");
        }

        $config = require $configPath;
        
        $this->host = $config['host'];
        $this->database = $config['database'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->charset = $config['charset'];
        
        $this->connect();
    }

    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            $this->connection = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public function prepare($query) {
        return $this->connection->prepare($query);
    }

    public function execute($query, $params = []) {
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    public function query($query) {
        return $this->connection->query($query);
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollBack() {
        return $this->connection->rollBack();
    }

    private function __clone() {}
    public function __wakeup() {
        throw new Exception('Unserializing is not allowed for singleton Database.');
    }
}
