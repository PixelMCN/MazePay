<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\listeners;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDeathEvent;
use Pixelis0P\MazePay\MazePay;

class PlayerListener implements Listener {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        $this->plugin = $plugin;
    }
    
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $uuid = $player->getUniqueId()->toString();
        $db = $this->plugin->getDatabaseManager();
        
        if (!$db->accountExists($uuid)) {
            $db->createAccount($player);
        } else {
            $db->updateUsername($uuid, $player->getName());
        }
    }
    
    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $uuid = $player->getUniqueId()->toString();
        $db = $this->plugin->getDatabaseManager();
        
        $walletBalance = $db->getWalletBalance($uuid);
        $penaltyPercent = $this->plugin->getDeathPenaltyPercent();
        
        if ($walletBalance > 0 && $penaltyPercent > 0) {
            $lostAmount = $walletBalance * ($penaltyPercent / 100);
            $db->deductWalletBalance($uuid, $lostAmount);
            
            $message = str_replace("{amount}", $this->plugin->formatMoney($lostAmount), $this->plugin->getMessage("death-penalty"));
            $player->sendMessage($this->plugin->getPrefix() . $message);
        }
    }
}