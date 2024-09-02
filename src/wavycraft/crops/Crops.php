<?php

declare(strict_types=1);

namespace wavycraft\crops;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\event\world\ChunkUnloadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\block\BlockTypeIds;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;
use pocketmine\utils\Config;

use wavycraft\crops\commands\CropFTCommand;
use wavycraft\crops\commands\CropCommand;
use wavycraft\crops\api\FloatingTextAPI;

class Crops extends PluginBase implements Listener {

    private static $instance;
    public $cropData;

    protected function onLoad() : void{
        self::$instance = $this;
    }

    protected function onEnable() : void{
        $this->cropData = new Config($this->getDataFolder() . "crop_data.json", Config::JSON);

        $this->getServer()->getCommandMap()->registerAll("CropTracker", [
            new CropCommand(),
            new CropFTCommand()
        ]);
        
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDisable() : void{
        $this->cropData->save();
        FloatingTextAPI::saveFile();
    }

    public static function getInstance() : self{
        return self::$instance;
    }

    public function onBlockBreak(BlockBreakEvent $event) {
        $block = $event->getBlock();
        $player = $event->getPlayer();

        if ($this->isCrop($block)) {
            $this->incrementCropCount($player, $block);
        }
    }

    public function onChunkLoad(ChunkLoadEvent $event) {
        $filePath = $this->getDataFolder() . "floating_text_data.json";
        FloatingTextAPI::loadFromFile($filePath);
    }

    public function onChunkUnload(ChunkUnloadEvent $event) {
        FloatingTextAPI::saveFile();
    }

    public function onWorldUnload(WorldUnloadEvent $event) {
        FloatingTextAPI::saveFile();
    }

    public function onEntityTeleport(EntityTeleportEvent $event) {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $fromWorld = $event->getFrom()->getWorld();
            $toWorld = $event->getTo()->getWorld();
        
            if ($fromWorld !== $toWorld) {
                foreach (FloatingTextAPI::$floatingText as $tag => [$position, $floatingText]) {
                    if ($position->getWorld() === $fromWorld) {
                        FloatingTextAPI::makeInvisible($tag);
                    }
                }
            }
        }
    }

    public function isCrop($block) : bool{
        return in_array($block->getTypeId(), [
            BlockTypeIds::BAMBOO,
            BlockTypeIds::WHEAT,
            BlockTypeIds::CARROTS,
            BlockTypeIds::POTATOES,
            BlockTypeIds::NETHER_WART,
            BlockTypeIds::BEETROOTS,
            BlockTypeIds::CACTUS,
            BlockTypeIds::COCOA_POD,
            BlockTypeIds::MELON,
            BlockTypeIds::PUMPKIN,
            BlockTypeIds::SUGARCANE
        ]);
    }

    public function incrementCropCount(Player $player, $block) {
        $cropType = $this->getCropName($block);

        $topPlayers = $this->cropData->getAll();

        if (isset($topPlayers[$player->getName()])) {
            if (isset($topPlayers[$player->getName()][$cropType])) {
                $topPlayers[$player->getName()][$cropType]++;
            } else {
                $topPlayers[$player->getName()][$cropType] = 1;
            }
        } else {
            $topPlayers[$player->getName()] = [$cropType => 1];
        }

        $this->cropData->setAll($topPlayers);
        $this->cropData->save();

        $this->updateFloatingText($player, $topPlayers);
    }

    public function getCropName($block) : string{
        $typeId = $block->getTypeId();

        return match ($typeId) {
            BlockTypeIds::BAMBOO => "Bamboo",
            BlockTypeIds::WHEAT => "Wheat",
            BlockTypeIds::CARROTS => "Carrot",
            BlockTypeIds::POTATOES => "Potato",
            BlockTypeIds::NETHER_WART => "Nether Wart",
            BlockTypeIds::BEETROOTS => "Beetroot",
            BlockTypeIds::CACTUS => "Cactus",
            BlockTypeIds::COCOA_POD => "Cocoa Pod",
            BlockTypeIds::MELON => "Melon",
            BlockTypeIds::PUMPKIN => "Pumpkin",
            BlockTypeIds::SUGARCANE => "Sugarcane",
            default => "unknown"
        };
    }

    public function updateFloatingText(Player $player, array $topPlayers) {
        $tag = "top_crops";

        if (!isset(FloatingTextAPI::$floatingText[$tag])) {
            $position = $player->getPosition();
            FloatingTextAPI::create($position, $tag, "");
        }

        $text = "§l-=Top 10 Crop Harvesters=-\n";
        $rank = 1;
        foreach ($topPlayers as $playerName => $cropData) {
            $totalCrops = is_array($cropData) ? array_sum($cropData) : $cropData;
            $text .= "{$rank}. {$playerName}: §e{$totalCrops} crops\n";
            $rank++;
        }

        FloatingTextAPI::update($tag, $text);
    }

    public function getTopPlayers(int $limit = 10, string $cropType = null) : array{
        $allData = $this->cropData->getAll();
        $topPlayers = [];

        foreach ($allData as $playerName => $crops) {
            $cropCount = $cropType ? ($crops[$cropType] ?? 0) : array_sum($crops);
            $topPlayers[$playerName] = $cropCount;
        }

        arsort($topPlayers);
        return array_slice($topPlayers, 0, $limit, true);
    }

    public function displayTopPlayers(Player $player, string $cropType = null) {
        $topPlayers = $this->getTopPlayers(10, $cropType);

        $text = "§l-=Top 10 Crop Harvesters=-\n";
        $rank = 1;
        foreach ($topPlayers as $playerName => $cropCount) {
            $text .= "{$rank}. {$playerName}: §e{$cropCount} crops\n";
            $rank++;
        }

        $position = $player->getPosition();
        FloatingTextAPI::create($position, "top_crops", $text);
    }
}
