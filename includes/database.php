<?php
class Database {
    private $db;
    
    public function __construct() {
        try {
            $this->db = new PDO('sqlite:' . __DIR__ . '/../db/emotions.db');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->initializeDatabase();
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    private function initializeDatabase() {
        // Verificar si las tablas ya existen
        $tables = $this->db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('users', $tables)) {
            $this->db->exec("CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                pin TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        if (!in_array('entries', $tables)) {
            $this->db->exec("CREATE TABLE entries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                text TEXT NOT NULL,
                emotion TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )");
        }
        
        if (!in_array('reminders', $tables)) {
            $this->db->exec("CREATE TABLE reminders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                time TEXT NOT NULL,
                is_active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )");
        }
    }
    
    public function getConnection() {
        return $this->db;
    }
}
?>