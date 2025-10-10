<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\economy;

use PixelMCN\MazePay\MazePay;
use PixelMCN\MazePay\database\DatabaseProvider;
use PixelMCN\MazePay\event\AccountCreateEvent;
use PixelMCN\MazePay\event\MoneyAddEvent;
use PixelMCN\MazePay\event\MoneyRemoveEvent;
use PixelMCN\MazePay\event\MoneyTransferEvent;
use pocketmine\player\Player;

class EconomyManager {
    
    private MazePay $plugin;
    private DatabaseProvider $database;
    
    /** @var array<string, Account> */
    private array $cache = [];
    
    private bool $cacheEnabled;
    private int $cacheLifetime;
    
    public function __construct(MazePay $plugin, DatabaseProvider $database) {
        $this->plugin = $plugin;
        $this->database = $database;
        $this->cacheEnabled = $plugin->getConfig()->getNested("performance.enable-cache", true);
        $this->cacheLifetime = $plugin->getConfig()->getNested("performance.cache-lifetime", 300);
    }
    
    /**
     * Get or create an account
     */
    public function getAccount(string $username): Account {
        $username = strtolower($username);
        
        // Check cache first
        if ($this->cacheEnabled && isset($this->cache[$username])) {
            $account = $this->cache[$username];
            if (time() - $account->getLastAccess() < $this->cacheLifetime) {
                return $account;
            }
        }
        
        // Load from database
        $data = $this->database->loadAccount($username);
        
        if ($data === null) {
            // Create new account
            $wallet = $this->plugin->getStartingBalance("wallet");
            $bank = $this->plugin->getStartingBalance("bank");
            
            $account = new Account($username, $wallet, $bank);
            $this->database->saveAccount($username, $wallet, $bank);
            
            // Call event
            $event = new AccountCreateEvent($username, $wallet, $bank);
            $event->call();
        } else {
            $account = new Account($username, $data["wallet"], $data["bank"]);
        }
        
        // Cache the account
        if ($this->cacheEnabled) {
            $this->cache[$username] = $account;
        }
        
        return $account;
    }
    
    /**
     * Check if account exists
     */
    public function accountExists(string $username): bool {
        return $this->database->accountExists(strtolower($username));
    }
    
    /**
     * Save an account
     */
    public function saveAccount(Account $account): void {
        $this->database->saveAccount(
            $account->getUsername(),
            $account->getWallet(),
            $account->getBank()
        );
    }
    
    /**
     * Add money to an account
     */
    public function addMoney(string $username, float $amount, string $type = "wallet"): bool {
        if ($amount <= 0) {
            return false;
        }
        
        $account = $this->getAccount($username);
        
        $event = new MoneyAddEvent($username, $amount, $type);
        $event->call();
        
        if ($event->isCancelled()) {
            return false;
        }
        
        $amount = $event->getAmount();
        
        if ($type === "wallet") {
            $account->addWallet($amount);
        } else {
            $account->addBank($amount);
        }
        
        $this->saveAccount($account);
        return true;
    }
    
    /**
     * Remove money from an account
     */
    public function removeMoney(string $username, float $amount, string $type = "wallet"): bool {
        if ($amount <= 0) {
            return false;
        }
        
        $account = $this->getAccount($username);
        
        // Check if sufficient funds
        $balance = $type === "wallet" ? $account->getWallet() : $account->getBank();
        if ($balance < $amount) {
            return false;
        }
        
        $event = new MoneyRemoveEvent($username, $amount, $type);
        $event->call();
        
        if ($event->isCancelled()) {
            return false;
        }
        
        $amount = $event->getAmount();
        
        if ($type === "wallet") {
            $account->subtractWallet($amount);
        } else {
            $account->subtractBank($amount);
        }
        
        $this->saveAccount($account);
        return true;
    }
    
    /**
     * Set money in an account
     */
    public function setMoney(string $username, float $amount, string $type = "wallet"): bool {
        if ($amount < 0) {
            return false;
        }
        
        $account = $this->getAccount($username);
        
        if ($type === "wallet") {
            $account->setWallet($amount);
        } else {
            $account->setBank($amount);
        }
        
        $this->saveAccount($account);
        return true;
    }
    
    /**
     * Transfer money between players
     */
    public function transfer(string $from, string $to, float $amount): bool {
        if ($amount <= 0) {
            return false;
        }
        
        $fromAccount = $this->getAccount($from);
        
        if ($fromAccount->getWallet() < $amount) {
            return false;
        }
        
        $event = new MoneyTransferEvent($from, $to, $amount);
        $event->call();
        
        if ($event->isCancelled()) {
            return false;
        }
        
        $amount = $event->getAmount();
        
        $fromAccount->subtractWallet($amount);
        $this->saveAccount($fromAccount);
        
        $toAccount = $this->getAccount($to);
        $toAccount->addWallet($amount);
        $this->saveAccount($toAccount);
        
        return true;
    }
    
    /**
     * Deposit money from wallet to bank
     */
    public function deposit(string $username, float $amount): bool {
        if ($amount <= 0) {
            return false;
        }
        
        $account = $this->getAccount($username);
        
        if ($account->getWallet() < $amount) {
            return false;
        }
        
        $account->subtractWallet($amount);
        $account->addBank($amount);
        $this->saveAccount($account);
        
        return true;
    }
    
    /**
     * Withdraw money from bank to wallet
     */
    public function withdraw(string $username, float $amount): bool {
        if ($amount <= 0) {
            return false;
        }
        
        $account = $this->getAccount($username);
        
        if ($account->getBank() < $amount) {
            return false;
        }
        
        $account->subtractBank($amount);
        $account->addWallet($amount);
        $this->saveAccount($account);
        
        return true;
    }
    
    /**
     * Get top accounts
     */
    public function getTopAccounts(int $limit): array {
        return $this->database->getTopAccounts($limit);
    }
    
    /**
     * Save all cached accounts
     */
    public function saveAll(): void {
        foreach ($this->cache as $account) {
            $this->saveAccount($account);
        }
        
        $this->plugin->getLogger()->info("Saved " . count($this->cache) . " player accounts.");
    }
    
    /**
     * Clear cache
     */
    public function clearCache(): void {
        $this->cache = [];
    }
}
