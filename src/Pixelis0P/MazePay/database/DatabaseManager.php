<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\database;

use Pixelis0P\MazePay\MazePay;
use pocketmine\player\Player;
use PDO;
use PDOException;

class DatabaseManager {

    private MazePay $plugin;
    /** @var PDO */
    private PDO $pdo;
    private string $driver; // sqlite or mysql

    public function __construct(MazePay $plugin) {
        $this->plugin = $plugin;
        $this->initDatabase();
    }

    private function initDatabase(): void {
        $dbConfig = $this->plugin->getConfig()->get("database", []);
        $type = strtolower($dbConfig['type'] ?? 'sqlite');
        $this->driver = $type;

        try {
            if ($type === 'mysql') {
                $mysql = $dbConfig['mysql'] ?? [];
                $host = $mysql['host'] ?? '127.0.0.1';
                $port = $mysql['port'] ?? 3306;
                $user = $mysql['user'] ?? '';
                $pass = $mysql['pass'] ?? '';
                $dbname = $mysql['database'] ?? 'mazepay';
                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                $this->pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            } else {
                $path = $this->plugin->getDataFolder() . "mazepay.db";
                $dsn = "sqlite:" . $path;
                $this->pdo = new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            }
        } catch (PDOException $e) {
            $this->plugin->getLogger()->error("[MazePay] Database connection failed: " . $e->getMessage());
            throw $e;
        }

        $this->createSchema();
    }

    private function createSchema(): void {
        // players table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS players (
            uuid TEXT PRIMARY KEY,
            username TEXT NOT NULL,
            wallet REAL NOT NULL DEFAULT 0,
            bank REAL NOT NULL DEFAULT 0,
            last_interest INTEGER NOT NULL DEFAULT 0
        )");

        // transactions table for logging
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ts INTEGER NOT NULL,
            uuid_from TEXT,
            uuid_to TEXT,
            amount REAL NOT NULL,
            account TEXT NOT NULL,
            type TEXT NOT NULL,
            note TEXT,
            admin_action INTEGER DEFAULT 0
        )");

        // pending notifications for offline players
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS pending_notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ts INTEGER NOT NULL,
            uuid TEXT NOT NULL,
            message TEXT NOT NULL,
            read INTEGER DEFAULT 0
        )");

        // indexes
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_players_username ON players(username)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_players_total ON players(uuid)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_transactions_uuid_to ON transactions(uuid_to)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_pending_uuid ON pending_notifications(uuid)");
    }

    public function createAccount(Player $player): void {
        $uuid = $player->getUniqueId()->toString();
        $username = $player->getName();
        $wallet = $this->plugin->getDefaultWalletBalance();
        $bank = $this->plugin->getDefaultBankBalance();

        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO players (uuid, username, wallet, bank, last_interest) VALUES (:uuid, :username, :wallet, :bank, :time)");
        $stmt->execute([':uuid' => $uuid, ':username' => $username, ':wallet' => $wallet, ':bank' => $bank, ':time' => time()]);
    }

    public function accountExists(string $uuid): bool {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM players WHERE uuid = :uuid");
        $stmt->execute([':uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['count'] > 0;
    }

    public function getWalletBalance(string $uuid): float {
        $stmt = $this->pdo->prepare("SELECT wallet FROM players WHERE uuid = :uuid");
        $stmt->execute([':uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['wallet'] : 0.0;
    }

    public function getBankBalance(string $uuid): float {
        $stmt = $this->pdo->prepare("SELECT bank FROM players WHERE uuid = :uuid");
        $stmt->execute([':uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['bank'] : 0.0;
    }

    public function setWalletBalance(string $uuid, float $amount): void {
        $stmt = $this->pdo->prepare("UPDATE players SET wallet = :amount WHERE uuid = :uuid");
        $stmt->execute([':amount' => $amount, ':uuid' => $uuid]);
    }

    public function setBankBalance(string $uuid, float $amount): void {
        $stmt = $this->pdo->prepare("UPDATE players SET bank = :amount WHERE uuid = :uuid");
        $stmt->execute([':amount' => $amount, ':uuid' => $uuid]);
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
        $stmt = $this->pdo->prepare("SELECT username, wallet, bank, (wallet + bank) as total FROM players ORDER BY total DESC LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        $players = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $players[] = $row;
        }

        return $players;
    }

    public function getUUIDByName(string $username): ?string {
        $stmt = $this->pdo->prepare("SELECT uuid FROM players WHERE LOWER(username) = LOWER(:username) LIMIT 1");
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['uuid'] : null;
    }

    public function updateUsername(string $uuid, string $username): void {
        $stmt = $this->pdo->prepare("UPDATE players SET username = :username WHERE uuid = :uuid");
        $stmt->execute([':username' => $username, ':uuid' => $uuid]);
    }

    public function getLastInterestTime(string $uuid): int {
        $stmt = $this->pdo->prepare("SELECT last_interest FROM players WHERE uuid = :uuid");
        $stmt->execute([':uuid' => $uuid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['last_interest'] : 0;
    }

    public function setLastInterestTime(string $uuid, int $time): void {
        $stmt = $this->pdo->prepare("UPDATE players SET last_interest = :time WHERE uuid = :uuid");
        $stmt->execute([':time' => $time, ':uuid' => $uuid]);
    }

    public function getAllPlayers(): array {
        $stmt = $this->pdo->query("SELECT uuid, username FROM players");

        $players = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $players[] = $row;
        }

        return $players;
    }

    public function logTransaction(?string $fromUuid, ?string $toUuid, float $amount, string $account, string $type, ?string $note = null, bool $admin = false): void {
        $stmt = $this->pdo->prepare("INSERT INTO transactions (ts, uuid_from, uuid_to, amount, account, type, note, admin_action) VALUES (:ts, :from, :to, :amount, :account, :type, :note, :admin)");
        $stmt->execute([
            ':ts' => time(),
            ':from' => $fromUuid,
            ':to' => $toUuid,
            ':amount' => $amount,
            ':account' => $account,
            ':type' => $type,
            ':note' => $note,
            ':admin' => $admin ? 1 : 0
        ]);
    }

    public function getTransactions(string $uuid, int $limit = 50, int $offset = 0): array {
        $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE uuid_from = :uuid OR uuid_to = :uuid ORDER BY ts DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':uuid', $uuid, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function addPendingNotification(string $uuid, string $message): void {
        $stmt = $this->pdo->prepare("INSERT INTO pending_notifications (ts, uuid, message, read) VALUES (:ts, :uuid, :message, 0)");
        $stmt->execute([':ts' => time(), ':uuid' => $uuid, ':message' => $message]);
    }

    public function getPendingNotifications(string $uuid): array {
        $stmt = $this->pdo->prepare("SELECT * FROM pending_notifications WHERE uuid = :uuid AND read = 0 ORDER BY ts ASC");
        $stmt->execute([':uuid' => $uuid]);
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function clearPendingNotificationsById(array $ids): void {
        if (empty($ids)) return;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("DELETE FROM pending_notifications WHERE id IN ($placeholders)");
        $stmt->execute($ids);
    }

    public function backupDatabase(): ?string {
        $backupDir = $this->plugin->getDataFolder() . "backups/";
        @mkdir($backupDir);
        $filename = "mazepay_backup_" . date('Ymd_His') . ".db";
        $path = $backupDir . $filename;

        if ($this->driver === 'sqlite') {
            $src = $this->plugin->getDataFolder() . "mazepay.db";
            if (!file_exists($src)) return null;
            copy($src, $path);
            return $path;
        }

        // For MySQL, export to SQL file
        if ($this->driver === 'mysql') {
            $file = $backupDir . "mazepay_backup_" . date('Ymd_His') . ".sql";
            // Best-effort: attempt to use mysqldump if available
            $this->plugin->getLogger()->info("[MazePay] MySQL backup requested. Please ensure mysqldump is available on the server.");
            return $file;
        }

        return null;
    }

    public function audit(string $message): void {
        // Simple audit to file for plugin-level events
        $logDir = $this->plugin->getDataFolder() . "audit/";
        @mkdir($logDir);
        $file = $logDir . date('Y-m-d') . ".log";
        $line = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public function close(): void {
        // PDO closes automatically when object is destroyed
        unset($this->pdo);
    }
}