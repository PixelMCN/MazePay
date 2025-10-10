<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\form;

use pocketmine\player\Player;
use pocketmine\form\Form;
use PixelMCN\MazePay\MazePay;

class BankForm implements Form {
    
    private MazePay $plugin;
    private Player $player;
    
    public function __construct(MazePay $plugin, Player $player) {
        $this->plugin = $plugin;
        $this->player = $player;
    }
    
    public function send(Player $player): void {
        $player->sendForm($this);
    }
    
    public function jsonSerialize(): mixed {
        $economyManager = $this->plugin->getEconomyManager();
        $account = $economyManager->getAccount($this->player->getName());
        
        $wallet = number_format($account->getWallet(), 2);
        $bank = number_format($account->getBank(), 2);
        $interest = $this->plugin->getInterestRate();
        $currency = $this->plugin->getCurrencySymbol();
        
        $content = $this->plugin->getMessage("bank-form-content", [
            "{wallet}" => $wallet,
            "{bank}" => $bank,
            "{interest}" => number_format($interest, 2)
        ]);
        
        $data = [
            "type" => "form",
            "title" => $this->plugin->getMessage("bank-form-title"),
            "content" => $content,
            "buttons" => [
                [
                    "text" => $this->plugin->getMessage("bank-form-deposit-button")
                ],
                [
                    "text" => $this->plugin->getMessage("bank-form-withdraw-button")
                ]
            ]
        ];
        
        // Add images if enabled
        if ($this->plugin->getConfig()->getNested("forms.enable-images", true)) {
            $depositImage = $this->plugin->getConfig()->getNested("forms.images.deposit", "");
            $withdrawImage = $this->plugin->getConfig()->getNested("forms.images.withdraw", "");
            
            if (!empty($depositImage)) {
                $data["buttons"][0]["image"] = [
                    "type" => "path",
                    "data" => $depositImage
                ];
            }
            
            if (!empty($withdrawImage)) {
                $data["buttons"][1]["image"] = [
                    "type" => "path",
                    "data" => $withdrawImage
                ];
            }
        }
        
        return $data;
    }
    
    public function handleResponse(Player $player, mixed $data): void {
        if ($data === null) {
            return;
        }
        
        if ($data === 0) {
            // Deposit
            $form = new DepositForm($this->plugin, $player);
            $form->send($player);
        } elseif ($data === 1) {
            // Withdraw
            $form = new WithdrawForm($this->plugin, $player);
            $form->send($player);
        }
    }
}
