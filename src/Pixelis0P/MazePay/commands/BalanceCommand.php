<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Pixelis0P\MazePay\MazePay;

class BalanceCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("balance", "Check your balance", "/balance [wallet/bank]", ["bal"]);
        $this->setPermission("mazepay.command.balance");
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
        
        $uuid = $sender->getUniqueId()->toString();
        $db = $this->plugin->getDatabaseManager();
        
        if (count($args) === 0) {
            $wallet = $db->getWalletBalance($uuid);
            $bank = $db->getBankBalance($uuid);
            
            $message = str_replace("{amount}", $this->plugin->formatMoney($wallet), $this->plugin->getMessage("balance-wallet"));
            $sender->sendMessage($this->plugin->getPrefix() . $message);
            
            $message = str_replace("{amount}", $this->plugin->formatMoney($bank), $this->plugin->getMessage("balance-bank"));
            $sender->sendMessage($this->plugin->getPrefix() . $message);
        } else {
            $account = strtolower($args[0]);
            
            if ($account !== "wallet" && $account !== "bank") {
                $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("invalid-account"));
                return false;
            }
            
            $balance = $account === "wallet" ? $db->getWalletBalance($uuid) : $db->getBankBalance($uuid);
            $messageKey = $account === "wallet" ? "balance-wallet" : "balance-bank";
            
            $message = str_replace("{amount}", $this->plugin->formatMoney($balance), $this->plugin->getMessage($messageKey));
            $sender->sendMessage($this->plugin->getPrefix() . $message);
        }
        
        return true;
    }
}