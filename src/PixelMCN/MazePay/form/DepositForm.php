<?php

declare(strict_types=1);

namespace PixelMCN\MazePay\form;

use pocketmine\player\Player;
use pocketmine\form\Form;
use PixelMCN\MazePay\MazePay;

class DepositForm implements Form {
    
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
        
        $content = $this->plugin->getMessage("bank-form-deposit-content", [
            "{wallet}" => $wallet
        ]);
        
        $placeholder = $this->plugin->getMessage("bank-form-deposit-placeholder");
        
        return [
            "type" => "custom_form",
            "title" => $this->plugin->getMessage("bank-form-deposit-title"),
            "content" => [
                [
                    "type" => "label",
                    "text" => $content
                ],
                [
                    "type" => "input",
                    "text" => "Amount",
                    "placeholder" => $placeholder,
                    "default" => ""
                ]
            ]
        ];
    }
    
    public function handleResponse(Player $player, mixed $data): void {
        if ($data === null) {
            return;
        }
        
        $amount = $data[1] ?? "";
        
        if (!is_numeric($amount) || ($amount = (float) $amount) <= 0) {
            $player->sendMessage($this->plugin->getMessage("invalid-amount"));
            return;
        }
        
        $economyManager = $this->plugin->getEconomyManager();
        
        if (!$economyManager->deposit($player->getName(), $amount)) {
            $player->sendMessage($this->plugin->getMessage("wallet-insufficient"));
            return;
        }
        
        $player->sendMessage($this->plugin->getMessage("deposit-success", [
            "{amount}" => number_format($amount, 2)
        ]));
    }
}
