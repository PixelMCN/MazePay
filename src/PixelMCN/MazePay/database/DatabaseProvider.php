<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\database;

interface DatabaseProvider {
    
    /**
     * Initialize the database connection
     */
    public function initialize(): void;
    
    /**
     * Load account data for a player
     * 
     * @param string $username
     * @return array{wallet: float, bank: float}|null
     */
    public function loadAccount(string $username): ?array;
    
    /**
     * Save account data for a player
     * 
     * @param string $username
     * @param float $wallet
     * @param float $bank
     */
    public function saveAccount(string $username, float $wallet, float $bank): void;
    
    /**
     * Check if an account exists
     * 
     * @param string $username
     * @return bool
     */
    public function accountExists(string $username): bool;
    
    /**
     * Get top richest players
     * 
     * @param int $limit
     * @return array<array{username: string, total: float}>
     */
    public function getTopAccounts(int $limit): array;
    
    /**
     * Close the database connection
     */
    public function close(): void;
}
