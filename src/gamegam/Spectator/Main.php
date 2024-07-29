<?php

namespace gamegam\Spectator;

use gamegam\Spectator\cmd\SpectatorCommand;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerEntityInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;

class Main extends PluginBase implements Listener{

	use SingletonTrait;

	private array $player = [];

	private array $death = [];

	public function onEnable(): void{
		self::setInstance($this);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		//command
		$this->getServer()->getCommandMap()->registerAll($this->getName(), [
			new SpectatorCommand(),
		]);
	}

	public function addSpectator(Player $p, Player $trager){
		$this->player[$p->getName()] = [
			"trager" => $trager->getName()
		];
	}

	public function Hand(PlayerEntityInteractEvent $ev){
		$p = $ev->getPlayer();
		$entity = $ev->getEntity();
		if ($p->isSpectator() && $entity instanceof Player) {
			$this->addSpectator($p, $entity);
		}
	}

	public function getTrager(Player $p){
		if (isset($this->player[$p->getName()]["trager"])){
			$pp = $this->getServer()->getPlayerExact($this->player[$p->getName()]["trager"]);
			return $pp;
		}
	}

	public function isSpectator(Player $p){
		if (isset($this->player[$p->getName()]["trager"])){
			$pp = $this->getTrager($p);
			if ($pp == null){
				unset($this->player[$p->getName()]);
			}else{
				$p->showPlayer($pp);
				unset($this->player[$p->getName()]);
			}
		}
	}

	public function onQuit(PlayerQuitEvent $ev)
	{
		$this->isSpectator($ev->getPlayer());
	}

	public function Task(DataPacketReceiveEvent $packet){
		$pk = $packet->getPacket();
		if ($pk instanceof PlayerAuthInputPacket) {
			$p = $packet->getOrigin();
			if ($p == null){
				return;
			}
			$p = $p->getPlayer();
			if (isset($this->death[$p->getName()]["trager"])){
				$pp = $this->getServer()->getPlayerExact($this->death[$p->getName()]["trager"]);
				if ($pp == null){
					unset($this->death[$p->getName()]);
					$this->isSpectator($p);
				}else{
					$p->showPlayer($pp);
					unset($this->death[$p->getName()]);
				}
			}
			if (isset($this->player[$p->getName()]["trager"])){
				$pp = $this->getTrager($p);
				if ($pp == null){
					unset($this->player[$p->getName()]);
				}else{
					if (! $p->isSpectator() || $pp->isSpectator()){
						$p->showPlayer($pp);
						unset($this->player[$p->getName()]);
						return;
					}
					$p->hidePlayer($pp);
					$pos = $pp->getPosition();
					$x = $pos->getX();
					$y = $pos->getY();
					$z = $pos->getZ();
					$world = $this->getServer()->getWorldManager()->getWorldByName($pp->getWorld()->getFolderName());
					$yaw = $pp->getLocation()->getYaw();
					$pitch = $pp->getLocation()->getPitch();
					$p->setMotion(new Vector3(0, 0, 0));
					$p->teleport(new Position($x, $y, $z, $world), $yaw, $pitch);
				}
			}
		}
	}

	public function onDeach(PlayerRespawnEvent $ev){
		$p = $ev->getPlayer();
		if ($p->isSpectator()){
			$pp = $this->getTrager($p);
			if ($pp !== null){
				$this->death[$p->getName()] = ["trager" => $pp->getName()];
				$this->isSpectator($p);
			}
		}
	}

	//Death
	public function onDe(PlayerDeathEvent $ev){
		$p = $ev->getPlayer();
		if (isset($this->player[$p->getName()])){
			$ev->setDrops([]);
		}
	}
}