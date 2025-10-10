<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use PixelMCN\MazePay\MazePay;

class PlayerListener implements Listener {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        $this->plugin = $plugin;
    }
    
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $economyManager = $this->plugin->getEconomyManager();
        
        // Load or create account
        $account = $economyManager->getAccount($player->getName());
        
        // Apply interest if enabled
        $interestRate = $this->plugin->getInterestRate();
        if ($interestRate > 0) {
            $bankBalance = $account->getBank();
            if ($bankBalance > 0) {
                $interest = $bankBalance * ($interestRate / 100);
                $account->addBank($interest);
                $economyManager->saveAccount($account);
                
                // Notify player about interest
                $player->sendMessage($this->plugin->getMessage("prefix") . 
                    "§aYou earned " . $this->plugin->getCurrencySymbol() . 
                    number_format($interest, 2) . " in bank interest!");
            }
        }
    }
    
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $economyManager = $this->plugin->getEconomyManager();
        
        // Save player data on quit
        $account = $economyManager->getAccount($player->getName());
        $economyManager->saveAccount($account);
    }
}
