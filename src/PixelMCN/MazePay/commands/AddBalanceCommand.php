<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use PixelMCN\MazePay\MazePay;

class AddBalanceCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("addbalance", "Add money to a player", "/addbalance <player> <amount> <bank|wallet>");
        $this->setPermission("mazepay.command.addbalance");
        $this->plugin = $plugin;
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }
        
        if (count($args) < 3) {
            $sender->sendMessage($this->plugin->getMessage("admin-add-usage"));
            return false;
        }
        
        $targetName = $args[0];
        $amount = $args[1];
        $type = strtolower($args[2]);
        
        // Validate type
        if (!in_array($type, ["bank", "wallet"])) {
            $sender->sendMessage($this->plugin->getMessage("admin-invalid-type"));
            return false;
        }
        
        // Validate amount
        if (!is_numeric($amount) || ($amount = (float) $amount) <= 0) {
            $sender->sendMessage($this->plugin->getMessage("invalid-amount"));
            return false;
        }
        
        $economyManager = $this->plugin->getEconomyManager();
        
        // Add money
        if (!$economyManager->addMoney($targetName, $amount, $type)) {
            $sender->sendMessage($this->plugin->getMessage("prefix") . "§cFailed to add money.");
            return false;
        }
        
        $sender->sendMessage($this->plugin->getMessage("admin-add-success", [
            "{amount}" => number_format($amount, 2),
            "{target}" => $targetName,
            "{type}" => $type
        ]));
        
        return true;
    }
}
