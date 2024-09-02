<?php

declare(strict_types=1);

namespace wavycraft\crops\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

use wavycraft\crops\Crops;

class CropFTCommand extends Command implements PluginOwned {

    public function __construct() {
        parent::__construct("cropft", "Spawn in the crop leaderboard", "/cropft", ["cft"]);
        $this->setPermission("croptracker.cropft");
    }

    public function getOwningPlugin() : plugin{
        return Crops::getInstance();
    }

    public function execute(CommandSender $sender, string $label, array $args) : bool{
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used in-game.");
            return false;
        }

        Crops::getInstance()->displayTopPlayers($sender);
        $sender->sendMessage("§aTop crops floating text created!");
        return true;
    }
}
