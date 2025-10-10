<?php

declare(strict_types=1);

namespace PixelMCN\MazePay;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use PixelMCN\MazePay\economy\EconomyManager;
use PixelMCN\MazePay\database\DatabaseProvider;
use PixelMCN\MazePay\database\SQLiteProvider;
use PixelMCN\MazePay\database\MySQLProvider;
use PixelMCN\MazePay\database\JSONProvider;
use PixelMCN\MazePay\commands\BalanceCommand;
use PixelMCN\MazePay\commands\PayCommand;
use PixelMCN\MazePay\commands\BankCommand;
use PixelMCN\MazePay\commands\DepositCommand;
use PixelMCN\MazePay\commands\WithdrawCommand;
use PixelMCN\MazePay\commands\RichCommand;
use PixelMCN\MazePay\commands\AddBalanceCommand;
use PixelMCN\MazePay\commands\RemoveBalanceCommand;
use PixelMCN\MazePay\commands\SetBalanceCommand;
use PixelMCN\MazePay\listener\PlayerListener;

class MazePay extends PluginBase {
    use SingletonTrait;

    private DatabaseProvider $database;
    private EconomyManager $economyManager;
    private Config $messages;
    private array $messageCache = [];

    protected function onLoad(): void {
        self::setInstance($this);
    }

    protected function onEnable(): void {
        // Save default configuration files
        $this->saveDefaultConfig();
        $this->saveResource("messages.yml");
        
        // Load messages
        $this->messages = new Config($this->getDataFolder() . "messages.yml", Config::YAML);
        
        // Initialize database
        if (!$this->initializeDatabase()) {
            $this->getLogger()->error("Failed to initialize database. Disabling plugin.");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        
        // Initialize economy manager
        $this->economyManager = new EconomyManager($this, $this->database);
        
        // Register commands
        $this->registerCommands();
        
        // Register event listeners
        $this->getServer()->getPluginManager()->registerEvents(new PlayerListener($this), $this);
        
        // Schedule periodic save
        $saveInterval = $this->getConfig()->getNested("performance.save-interval", 6000);
        $this->getScheduler()->scheduleRepeatingTask(new \PixelMCN\MazePay\task\SaveTask($this), $saveInterval);
        
        $this->getLogger()->info("§aMazePay v" . $this->getDescription()->getVersion() . " enabled!");
        $this->getLogger()->info("§aDatabase: " . $this->getConfig()->getNested("database.type", "sqlite"));
    }

    protected function onDisable(): void {
        if (isset($this->economyManager)) {
            $this->economyManager->saveAll();
        }
        
        if (isset($this->database)) {
            $this->database->close();
        }
        
        $this->getLogger()->info("§cMazePay disabled. All data saved.");
    }

    private function initializeDatabase(): bool {
        $type = strtolower($this->getConfig()->getNested("database.type", "sqlite"));
        
        try {
            $this->database = match ($type) {
                "sqlite" => new SQLiteProvider($this),
                "mysql" => new MySQLProvider($this),
                "json" => new JSONProvider($this),
                default => throw new \InvalidArgumentException("Invalid database type: $type")
            };
            
            $this->database->initialize();
            $this->getLogger()->info($this->getMessage("database-connected", [
                "{type}" => ucfirst($type)
            ]));
            return true;
        } catch (\Exception $e) {
            $this->getLogger()->error($this->getMessage("database-error", [
                "{error}" => $e->getMessage()
            ]));
            return false;
        }
    }

    private function registerCommands(): void {
        $commandMap = $this->getServer()->getCommandMap();
        
        $commands = [
            new BalanceCommand($this),
            new PayCommand($this),
            new BankCommand($this),
            new DepositCommand($this),
            new WithdrawCommand($this),
            new RichCommand($this),
            new AddBalanceCommand($this),
            new RemoveBalanceCommand($this),
            new SetBalanceCommand($this)
        ];
        
        foreach ($commands as $command) {
            $commandMap->register("mazepay", $command);
        }
    }

    public function getDatabase(): DatabaseProvider {
        return $this->database;
    }

    public function getEconomyManager(): EconomyManager {
        return $this->economyManager;
    }

    public function getMessage(string $key, array $replacements = []): string {
        if (isset($this->messageCache[$key])) {
            $message = $this->messageCache[$key];
        } else {
            $message = $this->messages->get($key, $key);
            $this->messageCache[$key] = $message;
        }
        
        // Replace prefix if not already in replacements
        if (!isset($replacements["{prefix}"])) {
            $replacements["{prefix}"] = $this->messages->get("prefix", "§l§6[MazePay]§r ");
        }
        
        // Replace currency symbol
        if (!isset($replacements["{currency}"])) {
            $replacements["{currency}"] = $this->getConfig()->getNested("economy.currency-symbol", "$");
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    public function getCurrencySymbol(): string {
        return $this->getConfig()->getNested("economy.currency-symbol", "$");
    }

    public function getStartingBalance(string $type): float {
        return (float) $this->getConfig()->getNested("economy.starting-balance.$type", $type === "wallet" ? 1000 : 0);
    }

    public function getInterestRate(): float {
        return (float) $this->getConfig()->getNested("economy.interest-rate", 0.5);
    }

    public function getMinimumTransaction(): float {
        return (float) $this->getConfig()->getNested("economy.minimum-transaction", 1);
    }
}
