<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Pixelis0P\MazePay\MazePay;

class DepositCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("deposit", "Deposit money into your bank", "/deposit <amount>", ["dep", "deposit"]);
        $this->setPermission("mazepay.command.deposit");
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
        
        if (count($args) < 1) {
            $sender->sendMessage($this->plugin->getPrefix() . "§cUsage: /deposit <amount>");
            return false;
        }
        
        $amount = $args[0];
        
        if (!is_numeric($amount) || (float)$amount <= 0) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("invalid-amount"));
            return false;
        }
        
        $amount = (float)$amount;
        $uuid = $sender->getUniqueId()->toString();
        $db = $this->plugin->getDatabaseManager();
        
        $walletBalance = $db->getWalletBalance($uuid);
        
        if ($walletBalance < $amount) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("deposit-insufficient"));
            return false;
        }
        
        $db->deductWalletBalance($uuid, $amount);
        $db->addBankBalance($uuid, $amount);
    $db->logTransaction($uuid, $uuid, $amount, 'bank', 'deposit', 'Player deposit');
    $this->plugin->getDatabaseManager()->audit("Player {$sender->getName()} ({$uuid}) deposited {$amount}");
        
        $message = str_replace("{amount}", $this->plugin->formatMoney($amount), $this->plugin->getMessage("deposit-success"));
        $sender->sendMessage($this->plugin->getPrefix() . $message);
        
        return true;
    }
}