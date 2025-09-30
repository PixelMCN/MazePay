<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Pixelis0P\MazePay\MazePay;

class WithdrawCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("withdraw", "Withdraw money from your bank", "/withdraw <amount>");
        $this->setPermission("mazepay.command.withdraw");
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
            $sender->sendMessage($this->plugin->getPrefix() . "§cUsage: /withdraw <amount>");
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
        
        $bankBalance = $db->getBankBalance($uuid);
        
        if ($bankBalance < $amount) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("withdraw-insufficient"));
            return false;
        }
        
        $db->deductBankBalance($uuid, $amount);
        $db->addWalletBalance($uuid, $amount);
        
        $message = str_replace("{amount}", $this->plugin->formatMoney($amount), $this->plugin->getMessage("withdraw-success"));
        $sender->sendMessage($this->plugin->getPrefix() . $message);
        
        return true;
    }
}