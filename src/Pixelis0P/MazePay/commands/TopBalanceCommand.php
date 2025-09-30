<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use Pixelis0P\MazePay\MazePay;

class TopBalanceCommand extends Command {
    
    private MazePay $plugin;
    
    public function __construct(MazePay $plugin) {
        parent::__construct("topbalance", "View the richest players", "/topbalance [10/20]", ["topbal"]);
        $this->setPermission("mazepay.command.topbalance");
        $this->plugin = $plugin;
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("no-permission"));
            return false;
        }
        
        $limit = 10;
        
        if (count($args) > 0) {
            if (is_numeric($args[0])) {
                $limit = (int)$args[0];
                if ($limit !== 10 && $limit !== 20) {
                    $sender->sendMessage($this->plugin->getPrefix() . "§cPlease choose either 10 or 20!");
                    return false;
                }
            }
        }
        
        $db = $this->plugin->getDatabaseManager();
        $topPlayers = $db->getTopPlayers($limit);
        
        $header = str_replace("{count}", (string)$limit, $this->plugin->getMessage("topbal-header"));
        $sender->sendMessage($this->plugin->getPrefix() . $header);
        
        $rank = 1;
        foreach ($topPlayers as $player) {
            $total = (float)$player['total'];
            $message = str_replace(
                ["{rank}", "{player}", "{amount}"],
                [$rank, $player['username'], $this->plugin->formatMoney($total)],
                $this->plugin->getMessage("topbal-entry")
            );
            $sender->sendMessage($message);
            $rank++;
        }
        
        $sender->sendMessage($this->plugin->getMessage("topbal-footer"));
        
        return true;
    }
}