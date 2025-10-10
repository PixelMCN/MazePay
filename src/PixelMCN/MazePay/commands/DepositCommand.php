<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use PixelMCN\MazePay\MazePay;

class DepositCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("deposit", "Deposit money into your bank", "/deposit <amount>");
        $this->setPermission("mazepay.command.deposit");
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
        
        if (count($args) < 1) {
            $sender->sendMessage($this->plugin->getMessage("deposit-usage"));
            return false;
        }
        
        $amount = $args[0];
        
        // Validate amount
        if (!is_numeric($amount) || ($amount = (float) $amount) <= 0) {
            $sender->sendMessage($this->plugin->getMessage("invalid-amount"));
            return false;
        }
        
        $economyManager = $this->plugin->getEconomyManager();
        
        // Deposit money
        if (!$economyManager->deposit($sender->getName(), $amount)) {
            $sender->sendMessage($this->plugin->getMessage("wallet-insufficient"));
            return false;
        }
        
        $sender->sendMessage($this->plugin->getMessage("deposit-success", [
            "{amount}" => number_format($amount, 2)
        ]));
        
        return true;
    }
}
