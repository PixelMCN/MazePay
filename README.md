# 💰 MazePay - Economy Plugin for PocketMine-MP

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![API](https://img.shields.io/badge/PocketMine--MP-5.0.0+-green.svg)
![PHP](https://img.shields.io/badge/PHP-8.4+-purple.svg)

A feature-rich economy plugin for PocketMine-MP servers with a dual account system, interest rates, and an intuitive form-based UI.

**Authors:** Pixelis0P & MazecraftMCN Team

---

## 📋 Table of Contents
- [Features](#-features)
- [Installation](#-installation)
- [Commands](#-commands)
- [Permissions](#-permissions)
- [Configuration](#%EF%B8%8F-configuration)
- [API for Developers](#-api-for-developers)
- [FAQ](#-faq)
- [Support](#-support)

---

## ✨ Features

### 🏦 **Dual Account System**
- **Wallet** - Carry money with you, but lose some on death
- **Bank** - Safe storage that earns interest and is protected from death penalties

### 🎮 **User-Friendly Interface**
- Beautiful form-based UI for the `/bank` command
- Traditional commands available for power users
- Fully customizable button images

### 💸 **Economy Features**
- Player-to-player money transfers
- Interest system for bank accounts (configurable rate & interval)
- Death penalty system (only affects wallet, not bank)
- Top richest players leaderboard (Top 10/20)

### 🔧 **Admin Tools**
- Set, add, or remove money from any player
- Full control over player economies
- Support for offline players

### 🗄️ **Database**
- SQLite database for reliable local storage
- Automatic data migration and indexing
- Stores player balances and interest timestamps

### 🎨 **Highly Customizable**
- Customize all messages and prefixes
- Change currency symbols
- Configure interest rates and intervals
- Adjust death penalties
- Custom button images for forms
- Config version control for easy updates

---

## 📥 Installation

1. **Download** the latest `MazePay.phar` from the [Releases](https://github.com/Pixelis0P/MazePay/releases) page
2. **Place** the `.phar` file in your server's `plugins/` folder
3. **Restart** your server
4. **Configure** the plugin by editing `plugins/MazePay/config.yml`
5. **Enjoy!** 🎉

---

## 📝 Commands

### 👤 Player Commands
| Command | Description | Aliases |
|---------|-------------|---------|
| `/balance [wallet/bank]` | Check your balance | `/bal` |
| `/pay <player> <amount> <wallet/bank>` | Send money to another player | - |
| `/deposit <amount>` | Move money from wallet to bank | - |
| `/withdraw <amount>` | Move money from bank to wallet | - |
| `/bank` | Open the bank menu (form UI) | - |
| `/topbalance [10/20]` | View richest players | `/topbal` |
| `/mazepay help` | Show all commands | - |

### 👑 Admin Commands
| Command | Description | Aliases |
|---------|-------------|---------|
| `/moneyset <player> <amount> <wallet/bank>` | Set a player's balance | - |
| `/moneyadd <player> <amount> <wallet/bank>` | Add money to a player | - |
| `/moneydeduct <player> <amount/all> <wallet/bank>` | Remove money from a player | `/moneydect` |

---

## 🔐 Permissions

### Default Permissions (All Players)
```yaml
mazepay.command.help          # Use /mazepay help
mazepay.command.balance       # Use /balance
mazepay.command.pay           # Use /pay
mazepay.command.deposit       # Use /deposit
mazepay.command.withdraw      # Use /withdraw
mazepay.command.bank          # Use /bank
mazepay.command.topbalance    # Use /topbalance
```

### Admin Permissions (Operators Only)
```yaml
mazepay.command.moneyset      # Use /moneyset
mazepay.command.moneyadd      # Use /moneyadd
mazepay.command.moneydeduct   # Use /moneydeduct
```

---

## ⚙️ Configuration

Edit `plugins/MazePay/config.yml` to customize the plugin:

```yaml
# Plugin Settings
prefix: "§b[MazePay]§r "
currency-symbol: "$"

# Starting Balances
default-wallet-balance: 1000
default-bank-balance: 0

# Interest System
interest-rate: 5.0              # 5% interest
interest-interval: 3600         # Every 1 hour (in seconds)

# Death Penalty
death-penalty-percent: 10.0     # Lose 10% of wallet on death

# Form Button Images (URLs)
form-images:
  deposit: "https://i.imgur.com/your-deposit-icon.png"
  withdraw: "https://i.imgur.com/your-withdraw-icon.png"
  back: "https://i.imgur.com/your-back-icon.png"
```

### 📝 Message Customization
All messages can be customized in the config file, including:
- Command responses
- Error messages
- Form titles and content
- Success notifications

**Tip:** Use Minecraft color codes (§) to add colors to your messages!

---

## 💻 API for Developers

### Using MazePay with Other Plugins

MazePay is **fully compatible** with shop plugins and other economy-dependent plugins! Access the economy system programmatically:

```php
use Pixelis0P\MazePay\MazePay;

// Get plugin instance
$mazepay = MazePay::getInstance();
$db = $mazepay->getDatabaseManager();

// Get player's wallet balance
$uuid = $player->getUniqueId()->toString();
$walletBalance = $db->getWalletBalance($uuid);

// Get bank balance
$bankBalance = $db->getBankBalance($uuid);

// Add money to wallet
$db->addWalletBalance($uuid, 100.0);

// Remove money from wallet
$db->deductWalletBalance($uuid, 50.0);

// Set balance directly
$db->setWalletBalance($uuid, 1000.0);
```

### Available API Methods

**Database Manager Methods:**
- `getWalletBalance(string $uuid): float`
- `getBankBalance(string $uuid): float`
- `setWalletBalance(string $uuid, float $amount): void`
- `setBankBalance(string $uuid, float $amount): void`
- `addWalletBalance(string $uuid, float $amount): void`
- `addBankBalance(string $uuid, float $amount): void`
- `deductWalletBalance(string $uuid, float $amount): void`
- `deductBankBalance(string $uuid, float $amount): void`
- `getUUIDByName(string $username): ?string`
- `accountExists(string $uuid): bool`

---

## ❓ FAQ

### **Q: Can I use this with shop plugins?**
**A:** Yes! MazePay is designed to work seamlessly with shop plugins. Shop plugins can integrate with MazePay using the API methods to check balances and deduct money from players' wallets.

### **Q: What happens to my money when I die?**
**A:** You lose a percentage (configurable) of your **wallet** money. Your **bank** money is completely safe!

### **Q: How does the interest system work?**
**A:** Every configured interval (default: 1 hour), players earn interest on their bank balance. If you have $1000 in the bank with 5% interest, you'll earn $50 per interval.

### **Q: Can players transfer money while offline?**
**A:** Yes! Admins can use commands to manage offline players' balances. Players can also send money to offline players using `/pay`.

### **Q: Does this support multiple currencies?**
**A:** Currently, MazePay uses a single currency system, but you can customize the currency symbol in the config.

### **Q: How do I add custom images to the bank form?**
**A:** Edit the `form-images` section in `config.yml` and add direct image URLs (must be HTTPS). Leave empty `""` to use text-only buttons.

### **Q: Can I disable the death penalty?**
**A:** Yes! Set `death-penalty-percent: 0.0` in the config to disable it.

### **Q: Is there a minimum PocketMine-MP version required?**
**A:** Yes, MazePay requires PocketMine-MP API 5.0.0 or higher and PHP 8.4+.

---

## 🐛 Known Issues

None at the moment! If you find a bug, please report it on our [Issues](https://github.com/Pixelis0P/MazePay/issues) page.

---

## 📜 Examples

### Player Usage
```
/balance
→ Your wallet balance: $1,000.00
→ Your bank balance: $0.00

/deposit 500
→ You deposited $500.00 into your bank account!

/pay Steve 100 wallet
→ You paid $100.00 to Steve from your wallet!

/bank
→ Opens beautiful form UI with player info and buttons

/topbal 10
→ === Top 10 Richest Players ===
→ #1. Alex - $10,000.00
→ #2. Steve - $5,500.00
```

### Admin Usage
```
/moneyset Alex 10000 bank
→ Set Alex's bank to $10,000.00!

/moneyadd Steve 500 wallet
→ Added $500.00 to Steve's wallet!

/moneydeduct John all wallet
→ Deducted all money from John's wallet!
```

---

## 🤝 Support

Need help? Found a bug? Have a suggestion?

- **GitHub Issues:** [Report Issues](https://github.com/Pixelis0P/MazePay/issues)
- **Discord:** [Join our Discord](#) *(Add your Discord link)*
- **Wiki:** [Read the Wiki](https://github.com/Pixelis0P/MazePay/wiki) *(Coming soon)*

---

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

---

## 🌟 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 💖 Credits

**Developed by:**
- **Pixelis0P** - Lead Developer
- **MazecraftMCN Team** - Development Team

**Special Thanks:**
- PocketMine-MP Team for the amazing server software
- All contributors and users of MazePay

---

<div align="center">

### ⭐ If you like MazePay, please consider giving it a star!

**Made with ❤️ by Pixelis0P & MazecraftMCN Team**

[⬆ Back to Top](#-mazepay---economy-plugin-for-pocketmine-mp)

</div>