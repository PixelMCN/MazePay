<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use PixelMCN\MazePay\MazePay;

class PayCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("pay", "Pay another player", "/pay <player> <amount>");
        $this->setPermission("mazepay.command.pay");
        $this->plugin = $plugin;
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }
        
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->plugin->getMessage("prefix") . "§cThis command can only be used in-game.");
            return false;
        }
        
        if (count($args) < 2) {
            $sender->sendMessage($this->plugin->getMessage("pay-usage"));
            return false;
        }
        
        $targetName = $args[0];
        $amount = $args[1];
        
        // Check if paying self
        if (strtolower($targetName) === strtolower($sender->getName())) {
            $sender->sendMessage($this->plugin->getMessage("pay-self-error"));
            return false;
        }
        
        // Validate amount
        if (!is_numeric($amount) || ($amount = (float) $amount) <= 0) {
            $sender->sendMessage($this->plugin->getMessage("invalid-amount"));
            return false;
        }
        
        // Check minimum transaction
        $minTransaction = $this->plugin->getMinimumTransaction();
        if ($amount < $minTransaction) {
            $sender->sendMessage($this->plugin->getMessage("amount-too-low", [
                "{amount}" => number_format($minTransaction, 2)
            ]));
            return false;
        }
        
        $economyManager = $this->plugin->getEconomyManager();
        
        // Check if target exists (create account if not)
        $economyManager->getAccount($targetName);
        
        // Transfer money
        if (!$economyManager->transfer($sender->getName(), $targetName, $amount)) {
            $sender->sendMessage($this->plugin->getMessage("insufficient-funds", [
                "{amount}" => number_format($amount, 2)
            ]));
            return false;
        }
        
        // Send success messages
        $sender->sendMessage($this->plugin->getMessage("pay-success-sender", [
            "{amount}" => number_format($amount, 2),
            "{target}" => $targetName
        ]));
        
        // Notify target if online
        $targetPlayer = $this->plugin->getServer()->getPlayerByPrefix($targetName);
        if ($targetPlayer !== null) {
            $targetPlayer->sendMessage($this->plugin->getMessage("pay-success-receiver", [
                "{amount}" => number_format($amount, 2),
                "{player}" => $sender->getName()
            ]));
        }
        
        return true;
    }
}
