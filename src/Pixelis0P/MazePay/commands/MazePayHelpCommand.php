<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use Pixelis0P\MazePay\MazePay;

class MazePayHelpCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("mazepay", "View MazePay help", "/mazepay help");
        $this->setPermission("mazepay.command.help");
        $this->plugin = $plugin;
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("no-permission"));
            return false;
        }
        
        if (count($args) === 0 || strtolower($args[0]) !== "help") {
            $sender->sendMessage($this->plugin->getPrefix() . "§cUsage: /mazepay help");
            return false;
        }
        
        $sender->sendMessage($this->plugin->getMessage("help-header"));
        $sender->sendMessage($this->plugin->getMessage("help-pay"));
        $sender->sendMessage($this->plugin->getMessage("help-balance"));
        $sender->sendMessage($this->plugin->getMessage("help-deposit"));
        $sender->sendMessage($this->plugin->getMessage("help-withdraw"));
        $sender->sendMessage($this->plugin->getMessage("help-topbal"));
        $sender->sendMessage($this->plugin->getMessage("help-bank"));
        
        if ($sender->hasPermission("mazepay.command.moneyset") || 
            $sender->hasPermission("mazepay.command.moneyadd") || 
            $sender->hasPermission("mazepay.command.moneydeduct")) {
            $sender->sendMessage($this->plugin->getMessage("help-admin-header"));
            
            if ($sender->hasPermission("mazepay.command.moneyset")) {
                $sender->sendMessage($this->plugin->getMessage("help-moneyset"));
            }
            if ($sender->hasPermission("mazepay.command.moneyadd")) {
                $sender->sendMessage($this->plugin->getMessage("help-moneyadd"));
            }
            if ($sender->hasPermission("mazepay.command.moneydeduct")) {
                $sender->sendMessage($this->plugin->getMessage("help-moneydeduct"));
            }
        }
        
        $sender->sendMessage($this->plugin->getMessage("help-footer"));
        
        return true;
    }
}