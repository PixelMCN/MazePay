<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\database;

use PixelMCN\MazePay\MazePay;
use mysqli;

class MySQLProvider implements DatabaseProvider {
    
    private MazePay $plugin;
    private ?mysqli $database = null;
    
    public function __construct(MazePay $plugin) {
        $this->plugin = $plugin;
    }
    
    public function initialize(): void {
        $config = $this->plugin->getConfig()->getNested("database.mysql");
        
        $this->database = new mysqli(
            $config["host"],
            $config["username"],
            $config["password"],
            $config["database"],
            $config["port"]
        );
        
        if ($this->database->connect_error) {
            throw new \RuntimeException("MySQL connection failed: " . $this->database->connect_error);
        }
        
        $this->database->query("CREATE TABLE IF NOT EXISTS accounts (
            username VARCHAR(16) PRIMARY KEY,
            wallet DOUBLE NOT NULL DEFAULT 0,
            bank DOUBLE NOT NULL DEFAULT 0,
            total DOUBLE GENERATED ALWAYS AS (wallet + bank) STORED,
            KEY idx_total (total DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    public function loadAccount(string $username): ?array {
        $stmt = $this->database->prepare("SELECT wallet, bank FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row === null) {
            return null;
        }
        
        return [
            "wallet" => (float) $row["wallet"],
            "bank" => (float) $row["bank"]
        ];
    }
    
    public function saveAccount(string $username, float $wallet, float $bank): void {
        $stmt = $this->database->prepare(
            "INSERT INTO accounts (username, wallet, bank) VALUES (?, ?, ?) 
             ON DUPLICATE KEY UPDATE wallet = ?, bank = ?"
        );
        $stmt->bind_param("sdddd", $username, $wallet, $bank, $wallet, $bank);
        $stmt->execute();
        $stmt->close();
    }
    
    public function accountExists(string $username): bool {
        $stmt = $this->database->prepare("SELECT 1 FROM accounts WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    public function getTopAccounts(int $limit): array {
        $stmt = $this->database->prepare(
            "SELECT username, (wallet + bank) as total FROM accounts ORDER BY total DESC LIMIT ?"
        );
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $accounts = [];
        while ($row = $result->fetch_assoc()) {
            $accounts[] = [
                "username" => $row["username"],
                "total" => (float) $row["total"]
            ];
        }
        
        $stmt->close();
        
        return $accounts;
    }
    
    public function close(): void {
        if ($this->database !== null) {
            $this->database->close();
            $this->database = null;
        }
    }
}
