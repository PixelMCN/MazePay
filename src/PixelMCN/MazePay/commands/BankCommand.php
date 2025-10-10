<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use PixelMCN\MazePay\MazePay;
use PixelMCN\MazePay\form\BankForm;

class BankCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("bank", "Open the bank menu", "/bank");
        $this->setPermission("mazepay.command.bank");
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
        
        $form = new BankForm($this->plugin, $sender);
        $form->send($sender);
        
        return true;
    }
}
