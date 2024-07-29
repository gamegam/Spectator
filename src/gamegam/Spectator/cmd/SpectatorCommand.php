<?php

namespace gamegam\Spectator\cmd;

use gamegam\Spectator\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\GameMode;
use pocketmine\plugin\PluginOwnedTrait;
use pocketmine\Server;

class SpectatorCommand extends Command{

	use PluginOwnedTrait;

	public function __construct(){
		parent::__construct("spectator", "This is the spectator's command.", null, [
			"sp"
		]);
		$this->setPermission(DefaultPermissionNames::BROADCAST_ADMIN);
	}

	public function getTrager($pp)
	{
		return Server::getInstance()->getPlayerExact($pp);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): void{
		if (! $this->testPermission($sender)) {
			return;
		}
		if (!isset($args[0]) || !isset($args[1])) {
			$sender->sendMessage("Enter /spectator [Player] [Destination] and the player will assign the target an observer.");
		}else{
			$player = $this->getTrager($args[0]);
			if ($player == null){
				$sender->sendMessage("§cThe player is offline and cannot use spectator commands.");
			}else{
				$trager = $this->getTrager($args[1]);
				if ($trager == null){
					$sender->sendMessage("§cThis target is offline and cannot proceed.");
				}else{
					if ($trager->isSpectator()){
						$sender->sendMessage("§cThe target is in a state of observation.");
						return;
					}
					$player->setGamemode(GameMode::SPECTATOR);
					$sender->sendMessage("§a{$args[0]} added a spectator to {$args[1]}.");
					Main::getInstance()->addSpectator($player, $trager);
				}
			}
		}
	}
}