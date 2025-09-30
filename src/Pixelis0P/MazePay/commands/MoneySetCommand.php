<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use Pixelis0P\MazePay\MazePay;

class MoneySetCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("moneyset", "Set a player's money", "/moneyset <player> <amount> <wallet/bank>");
        $this->setPermission("mazepay.command.moneyset");
        $this->plugin = $plugin;
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("no-permission"));
            return false;
        }
        
        if (count($args) < 3) {
            $sender->sendMessage($this->plugin->getPrefix() . "§cUsage: /moneyset <player> <amount> <wallet/bank>");
            return false;
        }
        
        $targetName = $args[0];
        $amount = $args[1];
        $account = strtolower($args[2]);
        
        if (!is_numeric($amount) || (float)$amount < 0) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("invalid-amount"));
            return false;
        }
        
        $amount = (float)$amount;
        
        if ($account !== "wallet" && $account !== "bank") {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("invalid-account"));
            return false;
        }
        
        $db = $this->plugin->getDatabaseManager();
        $targetUUID = $db->getUUIDByName($targetName);
        
        if ($targetUUID === null) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("player-not-found"));
            return false;
        }
        
        if ($account === "wallet") {
            $db->setWalletBalance($targetUUID, $amount);
        } else {
            $db->setBankBalance($targetUUID, $amount);
        }
        
        $message = str_replace(
            ["{player}", "{account}", "{amount}"],
            [$targetName, $account, $this->plugin->formatMoney($amount)],
            $this->plugin->getMessage("moneyset-success")
        );
        $sender->sendMessage($this->plugin->getPrefix() . $message);
        
        return true;
    }
}