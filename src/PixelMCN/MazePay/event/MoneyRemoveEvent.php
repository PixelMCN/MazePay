<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\plugin\PluginEvent;
use PixelMCN\MazePay\MazePay;

class MoneyRemoveEvent extends PluginEvent implements Cancellable {
    use CancellableTrait;
    
    private string $username;
    private float $amount;
    private string $type;
    
    public function __construct(string $username, float $amount, string $type) {
        parent::__construct(MazePay::getInstance());
        $this->username = $username;
        $this->amount = $amount;
        $this->type = $type;
    }
    
    public function getUsername(): string {
        return $this->username;
    }
    
    public function getAmount(): float {
        return $this->amount;
    }
    
    public function setAmount(float $amount): void {
        $this->amount = $amount;
    }
    
    public function getType(): string {
        return $this->type;
    }
}
