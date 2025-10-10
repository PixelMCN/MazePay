<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\database;

use PixelMCN\MazePay\MazePay;
use pocketmine\utils\Config;

class JSONProvider implements DatabaseProvider {
    
    private MazePay $plugin;
    private Config $config;
    private array $accounts = [];
    
    public function __construct(MazePay $plugin) {
        $this->plugin = $plugin;
    }
    
    public function initialize(): void {
        $dataFolder = $this->plugin->getDataFolder();
        if (!is_dir($dataFolder)) {
            mkdir($dataFolder, 0777, true);
        }
        
        $filePath = $dataFolder . "accounts.json";
        if (!file_exists($filePath)) {
            file_put_contents($filePath, json_encode([], JSON_PRETTY_PRINT));
        }
        
        $this->config = new Config($filePath, Config::JSON);
        $this->accounts = $this->config->getAll();
    }
    
    public function loadAccount(string $username): ?array {
        $username = strtolower($username);
        
        if (!isset($this->accounts[$username])) {
            return null;
        }
        
        return [
            "wallet" => (float) $this->accounts[$username]["wallet"],
            "bank" => (float) $this->accounts[$username]["bank"]
        ];
    }
    
    public function saveAccount(string $username, float $wallet, float $bank): void {
        $username = strtolower($username);
        
        $this->accounts[$username] = [
            "wallet" => $wallet,
            "bank" => $bank
        ];
        
        $this->config->setAll($this->accounts);
        $this->config->save();
    }
    
    public function accountExists(string $username): bool {
        return isset($this->accounts[strtolower($username)]);
    }
    
    public function getTopAccounts(int $limit): array {
        $sorted = [];
        
        foreach ($this->accounts as $username => $data) {
            $sorted[] = [
                "username" => $username,
                "total" => (float) ($data["wallet"] + $data["bank"])
            ];
        }
        
        // Sort by total descending
        usort($sorted, function($a, $b) {
            return $b["total"] <=> $a["total"];
        });
        
        return array_slice($sorted, 0, $limit);
    }
    
    public function close(): void {
        if (isset($this->config)) {
            $this->config->save();
        }
    }
}
