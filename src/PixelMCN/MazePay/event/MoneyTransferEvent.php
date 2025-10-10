<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\plugin\PluginEvent;
use PixelMCN\MazePay\MazePay;

class MoneyTransferEvent extends PluginEvent implements Cancellable {
    use CancellableTrait;
    
    private string $from;
    private string $to;
    private float $amount;
    
    public function __construct(string $from, string $to, float $amount) {
        parent::__construct(MazePay::getInstance());
        $this->from = $from;
        $this->to = $to;
        $this->amount = $amount;
    }
    
    public function getFrom(): string {
        return $this->from;
    }
    
    public function getTo(): string {
        return $this->to;
    }
    
    public function getAmount(): float {
        return $this->amount;
    }
    
    public function setAmount(float $amount): void {
        $this->amount = $amount;
    }
}
