<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use PixelMCN\MazePay\MazePay;

class RichCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("rich", "View top richest players", "/rich [10|20|30]", ["top", "baltop"]);
        $this->setPermission("mazepay.command.rich");
        $this->plugin = $plugin;
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }
        
        $limit = 10;
        
        if (count($args) > 0) {
            if (!is_numeric($args[0]) || !in_array((int) $args[0], [10, 20, 30])) {
                $sender->sendMessage($this->plugin->getMessage("rich-usage"));
                return false;
            }
            $limit = (int) $args[0];
        }
        
        $economyManager = $this->plugin->getEconomyManager();
        $topAccounts = $economyManager->getTopAccounts($limit);
        
        if (empty($topAccounts)) {
            $sender->sendMessage($this->plugin->getMessage("prefix") . "§cNo accounts found.");
            return false;
        }
        
        $sender->sendMessage($this->plugin->getMessage("rich-title", [
            "{count}" => (string) $limit
        ]));
        
        $rank = 1;
        foreach ($topAccounts as $account) {
            $sender->sendMessage($this->plugin->getMessage("rich-entry", [
                "{rank}" => (string) $rank,
                "{player}" => $account["username"],
                "{balance}" => number_format($account["total"], 2)
            ]));
            $rank++;
        }
        
        return true;
    }
}
