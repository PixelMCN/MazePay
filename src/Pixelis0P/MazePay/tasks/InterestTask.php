<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\tasks;

use pocketmine\scheduler\Task;
use Pixelis0P\MazePay\MazePay;

class InterestTask extends Task {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        $this->plugin = $plugin;
    }
    
    public function onRun(): void {
        $db = $this->plugin->getDatabaseManager();
        $allPlayers = $db->getAllPlayers();
        $interestRate = $this->plugin->getInterestRate();
        $interestInterval = $this->plugin->getConfig()->get("interest-interval", 3600);
        $currentTime = time();
        
        foreach ($allPlayers as $playerData) {
            $uuid = $playerData['uuid'];
            $username = $playerData['username'];
            
            $lastInterest = $db->getLastInterestTime($uuid);
            $timeSinceLastInterest = $currentTime - $lastInterest;
            
            if ($timeSinceLastInterest >= $interestInterval) {
                $bankBalance = $db->getBankBalance($uuid);
                
                if ($bankBalance > 0) {
                    $interestAmount = $bankBalance * ($interestRate / 100);
                    $db->addBankBalance($uuid, $interestAmount);
                    $db->setLastInterestTime($uuid, $currentTime);
                    
                    $player = $this->plugin->getServer()->getPlayerByPrefix($username);
                    if ($player !== null && $player->isOnline()) {
                        $message = str_replace("{amount}", $this->plugin->formatMoney($interestAmount), $this->plugin->getMessage("interest-earned"));
                        $player->sendMessage($this->plugin->getPrefix() . $message);
                    }
                }
            }
        }
    }
}