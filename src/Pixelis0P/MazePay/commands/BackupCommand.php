<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use Pixelis0P\MazePay\MazePay;

class BackupCommand extends Command {

    private MazePay $plugin;

    public function __construct(MazePay $plugin) {
        parent::__construct("mazepaybackup", "Create a backup of MazePay database", "/mazepaybackup", ["mpbackup", "mazepaybackup"]);
        $this->setPermission("mazepay.command.backup");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("no-permission"));
            return false;
        }

        $path = $this->plugin->getDatabaseManager()->backupDatabase();
        if ($path === null) {
            $sender->sendMessage($this->plugin->getPrefix() . "§cBackup failed or not supported on this driver.");
            return false;
        }

        $sender->sendMessage($this->plugin->getPrefix() . "§aBackup created: " . $path);
        $this->plugin->getDatabaseManager()->audit("Backup created by {$sender->getName()}: {$path}");
        return true;
    }
}
