<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use Pixelis0P\MazePay\MazePay;

class MoneyDeductCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("moneydeduct", "Deduct money from a player's account", "/moneydeduct <player> <amount/all> <wallet/bank>", ["moneydect"]);
        $this->setPermission("mazepay.command.moneydeduct");
        $this->plugin = $plugin;
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("no-permission"));
            return false;
        }
        
        if (count($args) < 3) {
            $sender->sendMessage($this->plugin->getPrefix() . "§cUsage: /moneydeduct <player> <amount/all> <wallet/bank>");
            return false;
        }
        
        $targetName = $args[0];
        $amountStr = $args[1];
        $account = strtolower($args[2]);
        
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
        
        if (strtolower($amountStr) === "all") {
            if ($account === "wallet") {
                $db->setWalletBalance($targetUUID, 0);
            } else {
                $db->setBankBalance($targetUUID, 0);
            }
            
            $message = str_replace(
                ["{player}", "{account}"],
                [$targetName, $account],
                $this->plugin->getMessage("moneydeduct-all")
            );
            $sender->sendMessage($this->plugin->getPrefix() . $message);
        } else {
            if (!is_numeric($amountStr) || (float)$amountStr <= 0) {
                $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("invalid-amount"));
                return false;
            }
            
            $amount = (float)$amountStr;
            
            if ($account === "wallet") {
                $db->deductWalletBalance($targetUUID, $amount);
            } else {
                $db->deductBankBalance($targetUUID, $amount);
            }
            
            $message = str_replace(
                ["{amount}", "{player}", "{account}"],
                [$this->plugin->formatMoney($amount), $targetName, $account],
                $this->plugin->getMessage("moneydeduct-success")
            );
            $sender->sendMessage($this->plugin->getPrefix() . $message);
        }
        
        return true;
    }
}