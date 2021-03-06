<?php
declare (strict_types = 1);
namespace Lemuria\Engine\Lemuria\Command;

use Lemuria\Engine\Lemuria\Exception\InvalidCommandException;
use Lemuria\Engine\Lemuria\Message\Construction\NameConstructionMessage;
use Lemuria\Engine\Lemuria\Message\Construction\NameOwnerMessage;
use Lemuria\Engine\Lemuria\Message\Region\NameCastleMessage;
use Lemuria\Engine\Lemuria\Message\Region\NameRegionMessage;
use Lemuria\Engine\Lemuria\Message\Unit\NameUnitMessage;
use Lemuria\Engine\Lemuria\Message\Unit\NameNotInConstructionMessage;
use Lemuria\Engine\Lemuria\Message\Unit\NameNotInVesselMessage;
use Lemuria\Engine\Lemuria\Message\Vessel\NameCaptainMessage;
use Lemuria\Engine\Lemuria\Message\Vessel\NameVesselMessage;
use Lemuria\Model\Lemuria\Building\Castle;
use Lemuria\Model\Lemuria\Construction;

/**
 * The Name command is used to set the name of a unit or the construction, region or vessel it controls.
 *
 * - NAME [Einheit] <Name>
 * - NAME Burg|Gebäude <Name>
 * - NAME Region <Name>
 * - NAME Schiff <Name>
 */
final class Name extends UnitCommand
{
	protected function run(): void {
		$n = $this->phrase->count();
		if ($n <= 0) {
			throw new InvalidCommandException($this, 'No name given.');
		}
		if ($n === 1) {
			$type = 'Einheit';
			$name = $this->trimName($this->phrase->getLine(1));
		} else {
			$type = $this->phrase->getParameter(1);
			$name = $this->trimName($this->phrase->getLine(2));
		}

		switch (strtolower($type)) {
			case 'einheit' :
				$this->renameUnit($name);
				break;
			case 'burg' :
			case 'gebäude' :
			case 'gebaeude':
				$this->renameConstruction($name);
				break;
			case 'region' :
				$this->renameRegion($name);
				break;
			case 'schiff' :
				$this->renameVessel($name);
				break;
			default :
				$this->renameUnit($this->trimName($this->phrase->getLine()));
		}
	}

	/**
	 * Set name of unit.
	 */
	private function renameUnit(string $name): void {
		$this->unit->setName($name);
		$this->message(NameUnitMessage::class)->p($name);
	}

	/**
	 * Set name of construction the unit controls.
	 */
	private function renameConstruction(string $name): void {
		$construction = $this->unit->Construction();
		if ($construction) {
			$owner = $construction->Inhabitants()->Owner();
			if ($owner && $owner === $this->unit) {
				$construction->setName($name);
				$this->message(NameConstructionMessage::class, $construction)->p($name);
				return;
			}
			$this->message(NameOwnerMessage::class, $construction)->e($this->unit);
			return;
		}
		$this->message(NameNotInConstructionMessage::class);
	}

	/**
	 * Set name of region the unit controls.
	 */
	private function renameRegion(string $name): void {
		$region = $this->unit->Region();
		$home   = $this->unit->Construction();
		if ($home) {
			$castle = null; /* @var Construction $castle */
			foreach ($region->Estate() as $construction /* @var Construction $construction */) {
				if ($construction->Building() instanceof Castle) {
					if (!$castle || $construction->Size() >= $castle->Size()) {
						$castle = $construction;
					}
				}
			}
			if ($castle === $home && $home->Inhabitants()->Owner() === $this->unit) {
				$region->setName($name);
				$this->message(NameRegionMessage::class, $region)->p($name);
				return;
			}
		}
		$this->message(NameCastleMessage::class, $region)->e($this->unit);
	}

	/**
	 * Set name of vessel the unit controls.
	 */
	private function renameVessel(string $name): void {
		$vessel = $this->unit->Vessel();
		if ($vessel) {
			$captain = $vessel->Passengers()->Owner();
			if ($captain && $captain === $this->unit) {
				$vessel->setName($name);
				$this->message(NameVesselMessage::class, $vessel)->p($name);
				return;
			}
			$this->message(NameCaptainMessage::class, $vessel)->e($this->unit);
			return;
		}
		$this->message(NameNotInVesselMessage::class);
	}

	/**
	 * Trim special characters from name.
	 */
	private function trimName(string $name): string {
		return trim($name, "\"'`'^°!§$%&/()=?{[]}\\+*~#<>|,.-;:_ ");
	}
}
