<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\economy;

class Account {
    
    private string $username;
    private float $wallet;
    private float $bank;
    private int $lastAccess;
    
    public function __construct(string $username, float $wallet, float $bank) {
        $this->username = strtolower($username);
        $this->wallet = $wallet;
        $this->bank = $bank;
        $this->lastAccess = time();
    }
    
    public function getUsername(): string {
        return $this->username;
    }
    
    public function getWallet(): float {
        $this->lastAccess = time();
        return $this->wallet;
    }
    
    public function getBank(): float {
        $this->lastAccess = time();
        return $this->bank;
    }
    
    public function getTotal(): float {
        return $this->wallet + $this->bank;
    }
    
    public function setWallet(float $amount): void {
        $this->wallet = max(0, $amount);
        $this->lastAccess = time();
    }
    
    public function setBank(float $amount): void {
        $this->bank = max(0, $amount);
        $this->lastAccess = time();
    }
    
    public function addWallet(float $amount): void {
        $this->wallet += $amount;
        $this->lastAccess = time();
    }
    
    public function addBank(float $amount): void {
        $this->bank += $amount;
        $this->lastAccess = time();
    }
    
    public function subtractWallet(float $amount): void {
        $this->wallet = max(0, $this->wallet - $amount);
        $this->lastAccess = time();
    }
    
    public function subtractBank(float $amount): void {
        $this->bank = max(0, $this->bank - $amount);
        $this->lastAccess = time();
    }
    
    public function getLastAccess(): int {
        return $this->lastAccess;
    }
}
