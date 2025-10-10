<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\database;

use PixelMCN\MazePay\MazePay;
use SQLite3;

class SQLiteProvider implements DatabaseProvider {
    
    private MazePay $plugin;
    private ?SQLite3 $database = null;
    
    public function __construct(MazePay $plugin) {
        $this->plugin = $plugin;
    }
    
    public function initialize(): void {
        $dataFolder = $this->plugin->getDataFolder();
        if (!is_dir($dataFolder)) {
            mkdir($dataFolder, 0777, true);
        }
        
        $this->database = new SQLite3($dataFolder . "accounts.db");
        $this->database->exec("CREATE TABLE IF NOT EXISTS accounts (
            username TEXT PRIMARY KEY,
            wallet REAL NOT NULL DEFAULT 0,
            bank REAL NOT NULL DEFAULT 0
        )");
        
        // Create index for faster queries
        $this->database->exec("CREATE INDEX IF NOT EXISTS idx_total ON accounts ((wallet + bank) DESC)");
    }
    
    public function loadAccount(string $username): ?array {
        $stmt = $this->database->prepare("SELECT wallet, bank FROM accounts WHERE username = :username");
        $stmt->bindValue(":username", strtolower($username), SQLITE3_TEXT);
        $result = $stmt->execute();
        
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $result->finalize();
        $stmt->close();
        
        if ($row === false) {
            return null;
        }
        
        return [
            "wallet" => (float) $row["wallet"],
            "bank" => (float) $row["bank"]
        ];
    }
    
    public function saveAccount(string $username, float $wallet, float $bank): void {
        $stmt = $this->database->prepare(
            "INSERT OR REPLACE INTO accounts (username, wallet, bank) VALUES (:username, :wallet, :bank)"
        );
        $stmt->bindValue(":username", strtolower($username), SQLITE3_TEXT);
        $stmt->bindValue(":wallet", $wallet, SQLITE3_FLOAT);
        $stmt->bindValue(":bank", $bank, SQLITE3_FLOAT);
        $stmt->execute();
        $stmt->close();
    }
    
    public function accountExists(string $username): bool {
        $stmt = $this->database->prepare("SELECT 1 FROM accounts WHERE username = :username");
        $stmt->bindValue(":username", strtolower($username), SQLITE3_TEXT);
        $result = $stmt->execute();
        
        $exists = $result->fetchArray() !== false;
        $result->finalize();
        $stmt->close();
        
        return $exists;
    }
    
    public function getTopAccounts(int $limit): array {
        $stmt = $this->database->prepare(
            "SELECT username, (wallet + bank) as total FROM accounts ORDER BY total DESC LIMIT :limit"
        );
        $stmt->bindValue(":limit", $limit, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $accounts = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $accounts[] = [
                "username" => $row["username"],
                "total" => (float) $row["total"]
            ];
        }
        
        $result->finalize();
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
