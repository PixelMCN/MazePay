<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Pixelis0P\MazePay\MazePay;

class PayCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("pay", "Pay money to another player", "/pay <player> <amount> <wallet/bank>");
        $this->setPermission("mazepay.command.pay");
        $this->plugin = $plugin;
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("player-only"));
            return false;
        }
        
        if (!$this->testPermission($sender)) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("no-permission"));
            return false;
        }
        
        if (count($args) < 3) {
            $sender->sendMessage($this->plugin->getPrefix() . "§cUsage: /pay <player> <amount> <wallet/bank>");
            return false;
        }
        
        $targetName = $args[0];
        $amount = $args[1];
        $account = strtolower($args[2]);
        
        if (!is_numeric($amount) || (float)$amount <= 0) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("pay-negative"));
            return false;
        }
        
        $amount = (float)$amount;
        
        if ($account !== "wallet" && $account !== "bank") {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("invalid-account"));
            return false;
        }
        
        $target = $this->plugin->getServer()->getPlayerByPrefix($targetName);
        if ($target === null) {
            $targetUUID = $this->plugin->getDatabaseManager()->getUUIDByName($targetName);
            if ($targetUUID === null) {
                $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("player-not-found"));
                return false;
            }
            $targetUUIDStr = $targetUUID;
        } else {
            if ($target->getName() === $sender->getName()) {
                $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("pay-self"));
                return false;
            }
            $targetUUIDStr = $target->getUniqueId()->toString();
        }
        
        $senderUUID = $sender->getUniqueId()->toString();
        $db = $this->plugin->getDatabaseManager();
        
        $senderBalance = $account === "wallet" ? $db->getWalletBalance($senderUUID) : $db->getBankBalance($senderUUID);
        
        if ($senderBalance < $amount) {
            $message = str_replace("{account}", $account, $this->plugin->getMessage("pay-insufficient"));
            $sender->sendMessage($this->plugin->getPrefix() . $message);
            return false;
        }
        
        if ($account === "wallet") {
            $db->deductWalletBalance($senderUUID, $amount);
            $db->addWalletBalance($targetUUIDStr, $amount);
        } else {
            $db->deductBankBalance($senderUUID, $amount);
            $db->addBankBalance($targetUUIDStr, $amount);
        }
        
        $message = str_replace(["{amount}", "{player}", "{account}"], 
            [$this->plugin->formatMoney($amount), $target ? $target->getName() : $targetName, $account], 
            $this->plugin->getMessage("pay-success-sender"));
        $sender->sendMessage($this->plugin->getPrefix() . $message);
        
        if ($target !== null) {
            $message = str_replace(["{amount}", "{player}"], 
                [$this->plugin->formatMoney($amount), $sender->getName()], 
                $this->plugin->getMessage("pay-success-receiver"));
            $target->sendMessage($this->plugin->getPrefix() . $message);
        }
        
        return true;
    }
}