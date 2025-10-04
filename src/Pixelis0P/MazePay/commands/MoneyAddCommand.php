<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use Pixelis0P\MazePay\MazePay;

class MoneyAddCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("moneyadd", "Add money to a player's account", "/moneyadd <player> <amount> <wallet/bank>", ["moneyadd", "madd"]);
        $this->setPermission("mazepay.command.moneyadd");
        $this->plugin = $plugin;
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("no-permission"));
            return false;
        }
        
        if (count($args) < 3) {
            $sender->sendMessage($this->plugin->getPrefix() . "§cUsage: /moneyadd <player> <amount> <wallet/bank>");
            return false;
        }
        
        $targetName = $args[0];
        $amount = $args[1];
        $account = strtolower($args[2]);
        
        if (!is_numeric($amount) || (float)$amount <= 0) {
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
            $db->addWalletBalance($targetUUID, $amount);
        } else {
            $db->addBankBalance($targetUUID, $amount);
        }
        // Log admin transaction
        $db->logTransaction(null, $targetUUID, $amount, $account, 'moneyadd', "Added by {$sender->getName()}", true);
        $this->plugin->getDatabaseManager()->audit("Admin {$sender->getName()} added {$amount} to {$targetName} ({$account})");

        // If player offline, add pending notification
        $targetPlayer = $this->plugin->getServer()->getPlayerByPrefix($targetName);
        if ($targetPlayer === null) {
            $message = str_replace([
                "{amount}", "{player}", "{account}"
            ], [$this->plugin->formatMoney($amount), $sender->getName(), $account], $this->plugin->getMessage("moneyadd-success"));
            $db->addPendingNotification($targetUUID, $message);
        }
        $message = str_replace(
            ["{amount}", "{player}", "{account}"],
            [$this->plugin->formatMoney($amount), $targetName, $account],
            $this->plugin->getMessage("moneyadd-success")
        );
        $sender->sendMessage($this->plugin->getPrefix() . $message);
        
        return true;
    }
}