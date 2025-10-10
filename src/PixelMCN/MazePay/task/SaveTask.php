<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\task;

use pocketmine\scheduler\Task;
use PixelMCN\MazePay\MazePay;

class SaveTask extends Task {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        $this->plugin = $plugin;
    }
    
    public function onRun(): void {
        $economyManager = $this->plugin->getEconomyManager();
        $economyManager->saveAll();
    }
}
