<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use Pixelis0P\MazePay\commands\PayCommand;
use Pixelis0P\MazePay\commands\BalanceCommand;
use Pixelis0P\MazePay\commands\DepositCommand;
use Pixelis0P\MazePay\commands\WithdrawCommand;
use Pixelis0P\MazePay\commands\TopBalanceCommand;
use Pixelis0P\MazePay\commands\MoneySetCommand;
use Pixelis0P\MazePay\commands\MoneyAddCommand;
use Pixelis0P\MazePay\commands\MoneyDeductCommand;
use Pixelis0P\MazePay\commands\BankCommand;
use Pixelis0P\MazePay\commands\MazePayHelpCommand;
use Pixelis0P\MazePay\commands\BackupCommand;
use Pixelis0P\MazePay\database\DatabaseManager;
use Pixelis0P\MazePay\listeners\PlayerListener;
use Pixelis0P\MazePay\tasks\InterestTask;

class MazePay extends PluginBase implements Listener {
    
    private static MazePay $instance;
    private DatabaseManager $databaseManager;
    private Config $config;
    
    public function onEnable(): void {
        self::$instance = $this;
        
        $this->saveDefaultConfig();
        // Removed config version control
        $this->config = $this->getConfig();
        
        @mkdir($this->getDataFolder());
        
        // Initialize database manager (supports sqlite or mysql based on config)
        $this->databaseManager = new DatabaseManager($this);
        
        $this->registerCommands();
        $this->getServer()->getPluginManager()->registerEvents(new PlayerListener($this), $this);
        
        $interestInterval = $this->config->get("interest-interval", 3600) * 20;
        $this->getScheduler()->scheduleRepeatingTask(new InterestTask($this), (int)$interestInterval);
        
        $this->getLogger()->info("§b§l[MazePay] §aplugin enabled!");
    }
    
    public function onDisable(): void {
        if(isset($this->databaseManager)) {
            $this->databaseManager->close();
        }
        $this->getLogger()->info("§b§l[MazePay] §aplugin disabled!");
    }
    
    private function registerCommands(): void {
        $commandMap = $this->getServer()->getCommandMap();
        // Register commands (constructors supply aliases)
        $commandMap->register("mazepay", new PayCommand($this));
        $commandMap->register("mazepay", new BalanceCommand($this));
        $commandMap->register("mazepay", new DepositCommand($this));
        $commandMap->register("mazepay", new WithdrawCommand($this));
        $commandMap->register("mazepay", new TopBalanceCommand($this));
        $commandMap->register("mazepay", new MoneySetCommand($this));
        $commandMap->register("mazepay", new MoneyAddCommand($this));
        $commandMap->register("mazepay", new MoneyDeductCommand($this));
        $commandMap->register("mazepay", new BankCommand($this));
        $commandMap->register("mazepay", new MazePayHelpCommand($this));
        $commandMap->register("mazepay", new BackupCommand($this));
    }
    

    public static function getInstance(): MazePay {
        return self::$instance;
    }
    
    public function getDatabaseManager(): DatabaseManager {
        return $this->databaseManager;
    }
    
    public function getPrefix(): string {
        return $this->config->get("prefix", "§b[MazePay]§r ");
    }
    
    public function getCurrencySymbol(): string {
        return $this->config->get("currency-symbol", "$");
    }
    
    public function getMessage(string $key): string {
        $messages = $this->config->get("messages", []);
        return $messages[$key] ?? "Message not found: $key";
    }
    
    public function getDefaultWalletBalance(): float {
        return (float)$this->config->get("default-wallet-balance", 1000);
    }
    
    public function getDefaultBankBalance(): float {
        return (float)$this->config->get("default-bank-balance", 0);
    }
    
    public function getInterestRate(): float {
        return (float)$this->config->get("interest-rate", 5.0);
    }
    
    public function getDeathPenaltyPercent(): float {
        return (float)$this->config->get("death-penalty-percent", 10.0);
    }
    
    public function formatMoney(float $amount): string {
        return $this->getCurrencySymbol() . number_format($amount, 2);
    }
}