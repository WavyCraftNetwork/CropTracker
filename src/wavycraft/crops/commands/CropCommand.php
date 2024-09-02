<?php

declare(strict_types=1);

namespace wavycraft\crops\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

use wavycraft\crops\Crops;
use jojoe77777\FormAPI\SimpleForm;

class CropCommand extends Command implements PluginOwned {

    public function __construct() {
        parent::__construct("crops", "Show your crop farming stats", null, ["c"]);
        $this->setPermission("croptracker.crops");
    }

    public function getOwningPlugin() : plugin{
        return Crops::getInstance();
    }

    public function execute(CommandSender $sender, string $label, array $args) : bool{
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used in-game.");
            return false;
        }

        $this->showCropStats($sender);
        return true;
    }

    public function showCropStats(Player $player) {
        $plugin = Crops::getInstance();
        $playerName = $player->getName();
        $playerData = $plugin->cropData->get($playerName, []);

        $form = new SimpleForm(function (Player $player, ?int $data = null) {
            //NOOP
        });

        $form->setTitle($player->getName() . " Crop Status");
        $message = "Here are your crop farming stats:\n \n";

        if (empty($playerData)) {
            $message .= "§cYou haven't farmed any crops yet!";
        } else {
            foreach ($playerData as $cropType => $count) {
                $message .= "§e" . ucfirst($cropType) . ": §f" . $count . "\n";
            }
        }

        $form->setContent($message);
        $form->addButton("Close");
        $player->sendForm($form);
    }
}
