<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\plugin\PluginEvent;
use PixelMCN\MazePay\MazePay;

class AccountCreateEvent extends PluginEvent implements Cancellable {
    use CancellableTrait;
    
    private string $username;
    private float $startingWallet;
    private float $startingBank;
    
    public function __construct(string $username, float $startingWallet, float $startingBank) {
        parent::__construct(MazePay::getInstance());
        $this->username = $username;
        $this->startingWallet = $startingWallet;
        $this->startingBank = $startingBank;
    }
    
    public function getUsername(): string {
        return $this->username;
    }
    
    public function getStartingWallet(): float {
        return $this->startingWallet;
    }
    
    public function getStartingBank(): float {
        return $this->startingBank;
    }
    
    public function setStartingWallet(float $amount): void {
        $this->startingWallet = $amount;
    }
    
    public function setStartingBank(float $amount): void {
        $this->startingBank = $amount;
    }
}
