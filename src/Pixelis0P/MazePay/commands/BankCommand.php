<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\form\Form;
use Pixelis0P\MazePay\MazePay;
use Pixelis0P\MazePay\forms\BankForm;

class BankCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("bank", "Open bank menu", "/bank");
        $this->setPermission("mazepay.command.bank");
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
        
        $sender->sendForm(new BankForm($this->plugin, $sender, "main"));
        
        return true;
    }
}