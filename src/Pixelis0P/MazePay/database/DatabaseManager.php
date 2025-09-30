<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\database;

use Pixelis0P\MazePay\MazePay;
use SQLite3;
use pocketmine\player\Player;

class DatabaseManager {
    
    private MazePay $plugin;
    private SQLite3 $database;
    
    public function __construct(MazePay $plugin) {
        $this->plugin = $plugin;
        $this->initDatabase();
    }
    
    private function initDatabase(): void {
        $this->database = new SQLite3($this->plugin->getDataFolder() . "mazepay.db");
        
        $this->database->exec("CREATE TABLE IF NOT EXISTS players (
            uuid TEXT PRIMARY KEY,
            username TEXT NOT NULL,
            wallet REAL NOT NULL DEFAULT 0,
            bank REAL NOT NULL DEFAULT 0,
            last_interest INTEGER NOT NULL DEFAULT 0
        )");
        
        $this->database->exec("CREATE INDEX IF NOT EXISTS idx_username ON players(username)");
        $this->database->exec("CREATE INDEX IF NOT EXISTS idx_total_balance ON players((wallet + bank))");
    }
    
    public function createAccount(Player $player): void {
        $uuid = $player->getUniqueId()->toString();
        $username = $player->getName();
        $wallet = $this->plugin->getDefaultWalletBalance();
        $bank = $this->plugin->getDefaultBankBalance();
        
        $stmt = $this->database->prepare("INSERT OR IGNORE INTO players (uuid, username, wallet, bank, last_interest) VALUES (:uuid, :username, :wallet, :bank, :time)");
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':wallet', $wallet, SQLITE3_FLOAT);
        $stmt->bindValue(':bank', $bank, SQLITE3_FLOAT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    public function accountExists(string $uuid): bool {
        $stmt = $this->database->prepare("SELECT COUNT(*) as count FROM players WHERE uuid = :uuid");
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row['count'] > 0;
    }
    
    public function getWalletBalance(string $uuid): float {
        $stmt = $this->database->prepare("SELECT wallet FROM players WHERE uuid = :uuid");
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? (float)$row['wallet'] : 0.0;
    }
    
    public function getBankBalance(string $uuid): float {
        $stmt = $this->database->prepare("SELECT bank FROM players WHERE uuid = :uuid");
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? (float)$row['bank'] : 0.0;
    }
    
    public function setWalletBalance(string $uuid, float $amount): void {
        $stmt = $this->database->prepare("UPDATE players SET wallet = :amount WHERE uuid = :uuid");
        $stmt->bindValue(':amount', $amount, SQLITE3_FLOAT);
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        $stmt->execute();
    }
    
    public function setBankBalance(string $uuid, float $amount): void {
        $stmt = $this->database->prepare("UPDATE players SET bank = :amount WHERE uuid = :uuid");
        $stmt->bindValue(':amount', $amount, SQLITE3_FLOAT);
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        $stmt->execute();
    }
    
    public function addWalletBalance(string $uuid, float $amount): void {
        $current = $this->getWalletBalance($uuid);
        $this->setWalletBalance($uuid, $current + $amount);
    }
    
    public function addBankBalance(string $uuid, float $amount): void {
        $current = $this->getBankBalance($uuid);
        $this->setBankBalance($uuid, $current + $amount);
    }
    
    public function deductWalletBalance(string $uuid, float $amount): void {
        $current = $this->getWalletBalance($uuid);
        $this->setWalletBalance($uuid, max(0, $current - $amount));
    }
    
    public function deductBankBalance(string $uuid, float $amount): void {
        $current = $this->getBankBalance($uuid);
        $this->setBankBalance($uuid, max(0, $current - $amount));
    }
    
    public function getTopPlayers(int $limit = 10): array {
        $stmt = $this->database->prepare("SELECT username, wallet, bank, (wallet + bank) as total FROM players ORDER BY total DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $players = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $players[] = $row;
        }
        
        return $players;
    }
    
    public function getUUIDByName(string $username): ?string {
        $stmt = $this->database->prepare("SELECT uuid FROM players WHERE LOWER(username) = LOWER(:username)");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['uuid'] : null;
    }
    
    public function updateUsername(string $uuid, string $username): void {
        $stmt = $this->database->prepare("UPDATE players SET username = :username WHERE uuid = :uuid");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        $stmt->execute();
    }
    
    public function getLastInterestTime(string $uuid): int {
        $stmt = $this->database->prepare("SELECT last_interest FROM players WHERE uuid = :uuid");
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? (int)$row['last_interest'] : 0;
    }
    
    public function setLastInterestTime(string $uuid, int $time): void {
        $stmt = $this->database->prepare("UPDATE players SET last_interest = :time WHERE uuid = :uuid");
        $stmt->bindValue(':time', $time, SQLITE3_INTEGER);
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        $stmt->execute();
    }
    
    public function getAllPlayers(): array {
        $result = $this->database->query("SELECT uuid, username FROM players");
        
        $players = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $players[] = $row;
        }
        
        return $players;
    }
    
    public function close(): void {
        $this->database->close();
    }
}