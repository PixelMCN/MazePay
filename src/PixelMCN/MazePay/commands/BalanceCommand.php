<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use PixelMCN\MazePay\MazePay;

class BalanceCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("balance", "View your balance", "/balance [player]", ["bal", "money"]);
        $this->setPermission("mazepay.command.balance");
        $this->plugin = $plugin;
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }
        
        $currency = $this->plugin->getCurrencySymbol();
        $economyManager = $this->plugin->getEconomyManager();
        
        if (count($args) === 0) {
            // Show own balance
            if (!$sender instanceof Player) {
                $sender->sendMessage($this->plugin->getMessage("prefix") . "§cThis command can only be used in-game.");
                return false;
            }
            
            $account = $economyManager->getAccount($sender->getName());
            $sender->sendMessage($this->plugin->getMessage("balance-self", [
                "{wallet}" => number_format($account->getWallet(), 2),
                "{bank}" => number_format($account->getBank(), 2),
                "{balance}" => number_format($account->getTotal(), 2)
            ]));
            
        } else {
            // Show other player's balance
            $targetName = $args[0];
            
            if (!$economyManager->accountExists($targetName)) {
                $sender->sendMessage($this->plugin->getMessage("player-not-found", [
                    "{player}" => $targetName
                ]));
                return false;
            }
            
            $account = $economyManager->getAccount($targetName);
            $sender->sendMessage($this->plugin->getMessage("balance-other", [
                "{player}" => $targetName,
                "{wallet}" => number_format($account->getWallet(), 2),
                "{bank}" => number_format($account->getBank(), 2),
                "{balance}" => number_format($account->getTotal(), 2)
            ]));
        }
        
        return true;
    }
}
