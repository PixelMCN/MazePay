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
            $this->plugin->getDatabaseManager()->audit("Account created for {$player->getName()} ({$uuid})");
        } else {
            $db->updateUsername($uuid, $player->getName());
            $this->plugin->getDatabaseManager()->audit("Username updated for {$uuid} -> {$player->getName()}");
        }

        // Send pending notifications (offline pays, admin messages, etc.)
        $pending = $db->getPendingNotifications($uuid);
        $idsToClear = [];
        foreach ($pending as $row) {
            $player->sendMessage($this->plugin->getPrefix() . $row['message']);
            $idsToClear[] = (int)$row['id'];
        }
        if (!empty($idsToClear)) {
            $db->clearPendingNotificationsById($idsToClear);
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