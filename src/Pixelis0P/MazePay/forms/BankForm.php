<?php

declare(strict_types=1);

namespace Pixelis0P\MazePay\forms;

use pocketmine\form\Form;
use pocketmine\player\Player;
use Pixelis0P\MazePay\MazePay;

class BankForm implements Form {
    
    private MazePay $plugin;
    private Player $player;
    private string $formType;
    
    public function __construct(MazePay $plugin, Player $player, string $formType = "main") {
        $this->plugin = $plugin;
        $this->player = $player;
        $this->formType = $formType;
    }
    
    public function jsonSerialize(): array {
        return match($this->formType) {
            "main" => $this->getMainForm(),
            "deposit" => $this->getDepositForm(),
            "withdraw" => $this->getWithdrawForm(),
            default => $this->getMainForm()
        };
    }
    
    private function getMainForm(): array {
        $uuid = $this->player->getUniqueId()->toString();
        $db = $this->plugin->getDatabaseManager();
        
        $walletBalance = $db->getWalletBalance($uuid);
        $bankBalance = $db->getBankBalance($uuid);
        $interestRate = $this->plugin->getInterestRate();
        
        $depositImage = $this->plugin->getConfig()->get("form-images")["deposit"] ?? "";
        $withdrawImage = $this->plugin->getConfig()->get("form-images")["withdraw"] ?? "";
        
        $content = "§l§ePlayer:§r " . $this->player->getName() . "\n\n";
        $content .= "§l§aWallet Balance:§r " . $this->plugin->formatMoney($walletBalance) . "\n";
        $content .= "§l§aBank Balance:§r " . $this->plugin->formatMoney($bankBalance) . "\n";
        $content .= "§l§6Interest Rate:§r " . $interestRate . "%";
        
        $buttons = [];
        
        // Deposit button
        $depositButton = ["text" => "§l§aDeposit"];
        if (!empty($depositImage)) {
            $depositButton["image"] = [
                "type" => "url",
                "data" => $depositImage
            ];
        }
        $buttons[] = $depositButton;
        
        // Withdraw button
        $withdrawButton = ["text" => "§l§cWithdraw"];
        if (!empty($withdrawImage)) {
            $withdrawButton["image"] = [
                "type" => "url",
                "data" => $withdrawImage
            ];
        }
        $buttons[] = $withdrawButton;
        
        return [
            "type" => "form",
            "title" => "§l§bBank Menu",
            "content" => $content,
            "buttons" => $buttons
        ];
    }
    
    private function getDepositForm(): array {
        $uuid = $this->player->getUniqueId()->toString();
        $db = $this->plugin->getDatabaseManager();
        $walletBalance = $db->getWalletBalance($uuid);
        
        return [
            "type" => "custom_form",
            "title" => "§l§aDeposit Menu",
            "content" => [
                [
                    "type" => "label",
                    "text" => "§eWallet Balance: §a" . $this->plugin->formatMoney($walletBalance) . "\n\n§7Enter the amount you want to deposit into your bank:"
                ],
                [
                    "type" => "input",
                    "text" => "§l§aAmount",
                    "placeholder" => "Enter amount..."
                ]
            ]
        ];
    }
    
    private function getWithdrawForm(): array {
        $uuid = $this->player->getUniqueId()->toString();
        $db = $this->plugin->getDatabaseManager();
        $bankBalance = $db->getBankBalance($uuid);
        
        return [
            "type" => "custom_form",
            "title" => "§l§cWithdraw Menu",
            "content" => [
                [
                    "type" => "label",
                    "text" => "§eBank Balance: §a" . $this->plugin->formatMoney($bankBalance) . "\n\n§7Enter the amount you want to withdraw from your bank:"
                ],
                [
                    "type" => "input",
                    "text" => "§l§cAmount",
                    "placeholder" => "Enter amount..."
                ]
            ]
        ];
    }
    
    public function handleResponse(Player $player, $data): void {
        if ($data === null) {
            // Player closed form
            if ($this->formType !== "main") {
                // Return to main menu if in deposit/withdraw form
                $player->sendForm(new BankForm($this->plugin, $player, "main"));
            }
            return;
        }
        
        match($this->formType) {
            "main" => $this->handleMainResponse($player, $data),
            "deposit" => $this->handleDepositResponse($player, $data),
            "withdraw" => $this->handleWithdrawResponse($player, $data),
            default => null
        };
    }
    
    private function handleMainResponse(Player $player, int $data): void {
        if ($data === 0) {
            // Deposit button
            $player->sendForm(new BankForm($this->plugin, $player, "deposit"));
        } elseif ($data === 1) {
            // Withdraw button
            $player->sendForm(new BankForm($this->plugin, $player, "withdraw"));
        }
    }
    
    private function handleDepositResponse(Player $player, array $data): void {
        $amount = $data[1] ?? "";
        
        if (!is_numeric($amount) || (float)$amount <= 0) {
            $player->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("invalid-amount"));
            $player->sendForm(new BankForm($this->plugin, $player, "deposit"));
            return;
        }
        
        $amount = (float)$amount;
        $uuid = $player->getUniqueId()->toString();
        $db = $this->plugin->getDatabaseManager();
        
        $walletBalance = $db->getWalletBalance($uuid);
        
        if ($walletBalance < $amount) {
            $player->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("deposit-insufficient"));
            $player->sendForm(new BankForm($this->plugin, $player, "deposit"));
            return;
        }
        
        $db->deductWalletBalance($uuid, $amount);
        $db->addBankBalance($uuid, $amount);
        
        $message = str_replace("{amount}", $this->plugin->formatMoney($amount), $this->plugin->getMessage("deposit-success"));
        $player->sendMessage($this->plugin->getPrefix() . $message);
        
        // Return to main bank menu
        $player->sendForm(new BankForm($this->plugin, $player, "main"));
    }
    
    private function handleWithdrawResponse(Player $player, array $data): void {
        $amount = $data[1] ?? "";
        
        if (!is_numeric($amount) || (float)$amount <= 0) {
            $player->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("invalid-amount"));
            $player->sendForm(new BankForm($this->plugin, $player, "withdraw"));
            return;
        }
        
        $amount = (float)$amount;
        $uuid = $player->getUniqueId()->toString();
        $db = $this->plugin->getDatabaseManager();
        
        $bankBalance = $db->getBankBalance($uuid);
        
        if ($bankBalance < $amount) {
            $player->sendMessage($this->plugin->getPrefix() . $this->plugin->getMessage("withdraw-insufficient"));
            $player->sendForm(new BankForm($this->plugin, $player, "withdraw"));
            return;
        }
        
        $db->deductBankBalance($uuid, $amount);
        $db->addWalletBalance($uuid, $amount);
        
        $message = str_replace("{amount}", $this->plugin->formatMoney($amount), $this->plugin->getMessage("withdraw-success"));
        $player->sendMessage($this->plugin->getPrefix() . $message);
        
        // Return to main bank menu
        $player->sendForm(new BankForm($this->plugin, $player, "main"));
    }
}