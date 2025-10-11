# MazePay

[![Author](https://img.shields.io/badge/Author-PixelMCN%20%26%20MazecraftMCN-blue.svg)](https://github.com/PixelMCN)
[![Version](https://img.shields.io/badge/Version-1.0.0-green.svg)](https://github.com/PixelMCN/MazePay/releases)
[![PocketMine](https://img.shields.io/badge/PocketMine-5.0.0-orange.svg)](https://github.com/pmmp/PocketMine-MP)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-purple.svg)](https://www.php.net/)

A lightweight, production-ready economy plugin for PocketMine-MP 5 with dual account system (wallet + bank), custom forms, multi-database support, and comprehensive API.

## ✨ Features

- 💰 **Dual Account System** - Separate wallet and bank accounts
- 🏦 **Bank Interest** - Configurable interest rate on deposits
- 📱 **Custom Forms** - Beautiful UI with customizable images
- 🗄️ **Multi-Database** - SQLite, MySQL, JSON support
- ⚡ **High Performance** - Built-in caching and async operations
- 🔌 **Developer API** - Complete API with cancellable events
- 🎨 **Fully Customizable** - Messages, forms, economy settings

---

## 📦 Installation

1. Download the latest release from [Releases](https://github.com/PixelMCN/MazePay/releases)
2. Place the `.phar` file in your `plugins/` folder
3. Restart your server
4. Customize `plugin_data/MazePay/config.yml` and `messages.yml`

---

## 📋 Commands

### Player Commands

| Command | Aliases | Description | Usage |
|---------|---------|-------------|-------|
| `/balance` | `/bal`, `/money` | View your or another player's balance | `/balance [player]` |
| `/pay` | - | Transfer money to another player | `/pay <player> <amount>` |
| `/bank` | - | Open the interactive bank menu | `/bank` |
| `/deposit` | - | Deposit money from wallet to bank | `/deposit <amount>` |
| `/withdraw` | - | Withdraw money from bank to wallet | `/withdraw <amount>` |
| `/rich` | `/top`, `/baltop` | View top richest players | `/rich [10\|20\|30]` |

### Admin Commands

| Command | Description | Usage | Permission |
|---------|-------------|-------|------------|
| `/addbalance` | Add money to a player's account | `/addbalance <player> <amount> <bank\|wallet>` | `mazepay.command.addbalance` |
| `/removebalance` | Remove money from a player's account | `/removebalance <player> <amount> <bank\|wallet>` | `mazepay.command.removebalance` |
| `/setbalance` | Set a player's account balance | `/setbalance <player> <amount> <bank\|wallet>` | `mazepay.command.setbalance` |

---

## ⚙️ Configuration

### Database Configuration

```yaml
database:
  type: sqlite  # Options: sqlite, mysql, json
  
  mysql:  # For multi-server setups
    host: localhost
    port: 3306
    username: root
    password: ""
    database: mazepay
```

### Economy Settings

```yaml
economy:
  currency-symbol: "$"  # Currency symbol displayed
  starting-balance:
    wallet: 1000  # Starting wallet balance
    bank: 0       # Starting bank balance
  interest-rate: 0.5  # Bank interest percentage per login
  minimum-transaction: 1  # Minimum amount for transactions
  max-wallet: -1  # Max wallet capacity (-1 = unlimited)
  max-bank: -1    # Max bank capacity (-1 = unlimited)
```

### Performance & Forms

```yaml
performance:
  enable-cache: true        # Enable caching for faster lookups
  cache-lifetime: 300       # Cache lifetime in seconds
  save-interval: 6000       # Auto-save interval in ticks

forms:
  enable-images: true       # Enable images in forms
  images:
    bank-menu: "textures/blocks/gold_block"
    deposit: "textures/items/gold_ingot"
    withdraw: "textures/items/emerald"
```

### Custom Messages

All messages in `messages.yml` support these placeholders:
- `{player}` - Player name
- `{amount}` - Transaction amount
- `{balance}` - Total balance
- `{wallet}` - Wallet balance
- `{bank}` - Bank balance
- `{currency}` - Currency symbol
- `{target}` - Target player name
- `{interest}` - Interest rate

---

## 🔌 API Documentation

### Getting Started

First, add MazePay as a dependency in your `plugin.yml`:

```yaml
depend: [MazePay]
```

Then access the plugin instance and economy manager:

```php
use PixelMCN\MazePay\MazePay;

$economy = MazePay::getInstance()->getEconomyManager();
```

---

### Account Management

#### Get or Create Account

```php
// Automatically creates account if doesn't exist
$account = $economy->getAccount("PlayerName");

// Check if account exists
if ($economy->accountExists("PlayerName")) {
    // Account exists
}
```

#### Account Methods

```php
$account = $economy->getAccount("PlayerName");

// Get balances
$wallet = $account->getWallet();      // Get wallet balance
$bank = $account->getBank();          // Get bank balance
$total = $account->getTotal();        // Get total (wallet + bank)

// Modify wallet
$account->setWallet(1000.0);          // Set wallet balance
$account->addWallet(100.0);           // Add to wallet
$account->subtractWallet(50.0);       // Remove from wallet

// Modify bank
$account->setBank(500.0);             // Set bank balance
$account->addBank(200.0);             // Add to bank
$account->subtractBank(25.0);         // Remove from bank

// Save changes
$economy->saveAccount($account);      // IMPORTANT: Always save after modifications!
```

---

### Economy Manager Methods

#### Add Money

```php
// Add money to wallet
$success = $economy->addMoney("PlayerName", 100.0, "wallet");

// Add money to bank
$success = $economy->addMoney("PlayerName", 50.0, "bank");

// Returns: bool (true on success, false on failure)
```

#### Remove Money

```php
// Remove money from wallet
$success = $economy->removeMoney("PlayerName", 25.0, "wallet");

// Remove money from bank
$success = $economy->removeMoney("PlayerName", 10.0, "bank");

// Returns: bool (true on success, false if insufficient funds)
```

#### Set Money

```php
// Set wallet balance
$success = $economy->setMoney("PlayerName", 1000.0, "wallet");

// Set bank balance
$success = $economy->setMoney("PlayerName", 500.0, "bank");

// Returns: bool (true on success)
```

#### Transfer Between Players

```php
// Transfer from wallet to wallet
$success = $economy->transfer("SenderName", "ReceiverName", 100.0);

// Returns: bool (true on success, false if insufficient funds)
```

#### Deposit & Withdraw

```php
// Deposit from wallet to bank
$success = $economy->deposit("PlayerName", 100.0);

// Withdraw from bank to wallet
$success = $economy->withdraw("PlayerName", 50.0);

// Returns: bool (true on success, false if insufficient funds)
```

#### Get Top Accounts

```php
// Get top 10 richest players
$topPlayers = $economy->getTopAccounts(10);

// Returns: array [["username" => string, "total" => float], ...]
foreach ($topPlayers as $data) {
    echo $data["username"] . ": $" . $data["total"];
}
```

---

### Events System

All events are **cancellable** and allow modification before the action occurs.

#### Available Events

| Event | Description | When Triggered |
|-------|-------------|----------------|
| `AccountCreateEvent` | New account creation | When a player's account is first created |
| `MoneyAddEvent` | Money being added | Before money is added to an account |
| `MoneyRemoveEvent` | Money being removed | Before money is removed from an account |
| `MoneyTransferEvent` | Money transfer | Before money is transferred between players |

#### Event Usage Examples

**Listen to Events**

```php
use pocketmine\event\Listener;
use PixelMCN\MazePay\event\MoneyAddEvent;
use PixelMCN\MazePay\event\MoneyRemoveEvent;
use PixelMCN\MazePay\event\MoneyTransferEvent;
use PixelMCN\MazePay\event\AccountCreateEvent;

class MyListener implements Listener {
    
    public function onAccountCreate(AccountCreateEvent $event): void {
        $username = $event->getUsername();
        $startingWallet = $event->getStartingWallet();
        $startingBank = $event->getStartingBank();
        
        // Give VIP players bonus starting money
        if ($this->isVIP($username)) {
            $event->setStartingWallet(10000);
            $event->setStartingBank(5000);
        }
    }
    
    public function onMoneyAdd(MoneyAddEvent $event): void {
        $username = $event->getUsername();
        $amount = $event->getAmount();
        $type = $event->getType(); // "wallet" or "bank"
        
        // Double money for VIP players
        if ($this->isVIP($username)) {
            $event->setAmount($amount * 2);
        }
        
        // Cancel if needed
        if ($amount > 10000) {
            $event->cancel();
        }
    }
    
    public function onMoneyRemove(MoneyRemoveEvent $event): void {
        $username = $event->getUsername();
        $amount = $event->getAmount();
        
        // Prevent removing more than $1000 at once
        if ($amount > 1000) {
            $event->cancel();
        }
    }
    
    public function onMoneyTransfer(MoneyTransferEvent $event): void {
        $from = $event->getFrom();
        $to = $event->getTo();
        $amount = $event->getAmount();
        
        // Apply 5% transaction fee
        $fee = $amount * 0.05;
        $event->setAmount($amount - $fee);
        
        // Log the transaction
        $this->logTransaction($from, $to, $amount, $fee);
    }
}
```

---

### Plugin Integration Examples

#### Shop Plugin

```php
<?php

namespace MyShop;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use PixelMCN\MazePay\MazePay;

class ShopPlugin extends PluginBase {
    
    private $economy;
    
    public function onEnable(): void {
        $this->economy = MazePay::getInstance()->getEconomyManager();
    }
    
    public function buyItem(Player $player, string $itemName, float $price): bool {
        $account = $this->economy->getAccount($player->getName());
        
        // Check if player has enough money
        if ($account->getWallet() < $price) {
            $player->sendMessage("§cYou need $$price to buy this item!");
            return false;
        }
        
        // Remove money from wallet
        if (!$this->economy->removeMoney($player->getName(), $price, "wallet")) {
            $player->sendMessage("§cTransaction failed!");
            return false;
        }
        
        // Give item to player
        $this->giveItem($player, $itemName);
        $player->sendMessage("§aYou bought $itemName for $$price!");
        return true;
    }
    
    public function sellItem(Player $player, string $itemName, float $price): bool {
        // Add money to player's wallet
        $this->economy->addMoney($player->getName(), $price, "wallet");
        
        // Take item from player
        $this->takeItem($player, $itemName);
        $player->sendMessage("§aYou sold $itemName for $$price!");
        return true;
    }
}
```

#### Daily Rewards Plugin

```php
<?php

namespace MyRewards;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use PixelMCN\MazePay\MazePay;

class RewardsPlugin extends PluginBase implements Listener {
    
    private $economy;
    private array $lastClaim = [];
    
    public function onEnable(): void {
        $this->economy = MazePay::getInstance()->getEconomyManager();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        
        // Check if already claimed today
        if ($this->canClaim($name)) {
            $reward = 100.0;
            $this->economy->addMoney($name, $reward, "wallet");
            $player->sendMessage("§aDaily reward: $$reward");
            $this->lastClaim[$name] = time();
        }
    }
    
    private function canClaim(string $name): bool {
        if (!isset($this->lastClaim[$name])) {
            return true;
        }
        return (time() - $this->lastClaim[$name]) >= 86400; // 24 hours
    }
}
```

#### Tax System

```php
<?php

namespace MyTax;

use pocketmine\event\Listener;
use PixelMCN\MazePay\event\MoneyTransferEvent;

class TaxListener implements Listener {
    
    private float $taxRate = 0.05; // 5%
    
    public function onMoneyTransfer(MoneyTransferEvent $event): void {
        $amount = $event->getAmount();
        
        // Calculate and apply tax
        $tax = $amount * $this->taxRate;
        $afterTax = $amount - $tax;
        $event->setAmount($afterTax);
        
        // Log tax collection
        $this->getLogger()->info("Tax collected: $$tax from transfer");
    }
}
```

#### VIP Bonus System

```php
<?php

namespace MyVIP;

use pocketmine\event\Listener;
use PixelMCN\MazePay\event\AccountCreateEvent;
use PixelMCN\MazePay\event\MoneyAddEvent;

class VIPListener implements Listener {
    
    public function onAccountCreate(AccountCreateEvent $event): void {
        $username = $event->getUsername();
        
        // VIP players start with more money
        if ($this->isVIP($username)) {
            $event->setStartingWallet(5000);
            $event->setStartingBank(1000);
        }
    }
    
    public function onMoneyAdd(MoneyAddEvent $event): void {
        $username = $event->getUsername();
        
        // VIP players get 50% bonus on all money earned
        if ($this->isVIP($username)) {
            $amount = $event->getAmount();
            $event->setAmount($amount * 1.5);
        }
    }
    
    private function isVIP(string $username): bool {
        // Your VIP check logic here
        return true;
    }
}
```

---

## 📊 Database Support

MazePay supports multiple database backends for different server setups:

| Database | Use Case | Configuration | Notes |
|----------|----------|---------------|-------|
| **SQLite** | Single server, default | None required | Automatic setup, no external dependencies |
| **MySQL** | Multi-server network | `database.mysql` | Best for large networks, supports server clustering |
| **JSON** | Development/Testing | None required | Simple file-based storage, not recommended for production |

**Switching Database Types:**

1. Edit `plugin_data/MazePay/config.yml`
2. Change `database.type` to your preferred database
3. Configure connection details if using MySQL
4. Restart your server

---

## 🔒 Permissions

All commands have dedicated permissions that can be configured in your permissions plugin.

### Player Permissions (Default: true)

| Permission | Command | Description |
|------------|---------|-------------|
| `mazepay.command.balance` | `/balance` | View balance (own or others) |
| `mazepay.command.pay` | `/pay` | Transfer money to other players |
| `mazepay.command.bank` | `/bank` | Access bank menu |
| `mazepay.command.deposit` | `/deposit` | Deposit money to bank |
| `mazepay.command.withdraw` | `/withdraw` | Withdraw money from bank |
| `mazepay.command.rich` | `/rich` | View leaderboard |

### Admin Permissions (Default: op)

| Permission | Command | Description |
|------------|---------|-------------|
| `mazepay.command.addbalance` | `/addbalance` | Add money to player accounts |
| `mazepay.command.removebalance` | `/removebalance` | Remove money from player accounts |
| `mazepay.command.setbalance` | `/setbalance` | Set player account balance |

**Note:** Permissions can be managed using plugins like PurePerms or your preferred permission manager.

---

## 🛠️ Development

### Requirements
- **PHP:** 8.4 or higher
- **PocketMine-MP:** 5.0.0 API
- **DevTools:** For building from source

### Building from Source

```bash
# Clone the repository
git clone https://github.com/PixelMCN/MazePay.git

# Build with DevTools
# Place in plugins folder with DevTools enabled
# Or use Poggit for automated builds
```

### Contributing

Contributions are welcome! To contribute:

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Code Standards

- Follow PSR-12 coding standards
- Use strict types (`declare(strict_types=1)`)
- Add type hints to all methods
- Document public APIs with PHPDoc

---

## 📖 License

This project is licensed under the **MIT License**. See the [LICENSE](LICENSE) file for details.

---

## 🤝 Support & Community

### Get Help

- **Issues:** Report bugs or request features on [GitHub Issues](https://github.com/PixelMCN/MazePay/issues)
- **Discussions:** Join conversations on [GitHub Discussions](https://github.com/PixelMCN/MazePay/discussions)
- **Documentation:** Visit our [Wiki](https://github.com/PixelMCN/MazePay/wiki) for detailed guides

### Stay Updated

- **Star** ⭐ the repository to show your support
- **Watch** 👀 for updates and new releases
- **Share** 🔗 with other server owners

---

## 👥 Credits

**Developed by:** PixelMCN & MazecraftMCN Team

**Built with ❤️ for the PocketMine-MP community**

---

<div align="center">

**If you find MazePay useful, please consider giving it a ⭐ on GitHub!**

[Report Bug](https://github.com/PixelMCN/MazePay/issues) · [Request Feature](https://github.com/PixelMCN/MazePay/issues) · [View Documentation](https://github.com/PixelMCN/MazePay/wiki)

</div>



