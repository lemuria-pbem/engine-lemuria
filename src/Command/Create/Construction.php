<?php
declare (strict_types = 1);
namespace Lemuria\Engine\Lemuria\Command\Create;

use Lemuria\Engine\Lemuria\Message\Unit\ConstructionBuildMessage;
use Lemuria\Engine\Lemuria\Message\Unit\ConstructionCreateMessage;
use Lemuria\Engine\Lemuria\Message\Unit\ConstructionExperienceMessage;
use Lemuria\Engine\Lemuria\Message\Unit\ConstructionMessage;
use Lemuria\Engine\Lemuria\Message\Unit\ConstructionOnlyMessage;
use Lemuria\Engine\Lemuria\Message\Unit\ConstructionResourcesMessage;
use Lemuria\Engine\Lemuria\Message\Unit\ConstructionUnableMessage;
use Lemuria\Exception\LemuriaException;
use Lemuria\Lemuria;
use Lemuria\Model\Catalog;
use Lemuria\Model\Lemuria\Building;
use Lemuria\Model\Lemuria\Building\AbstractCastle;
use Lemuria\Model\Lemuria\Building\Castle;
use Lemuria\Model\Lemuria\Construction as ConstructionModel;
use Lemuria\Model\Lemuria\Quantity;
use Lemuria\Model\Lemuria\Requirement;

/**
 * Implementation of command MACHEN <Gebäude> (create construction).
 *
 * The command lets units build constructions. If the unit is inside a construction, that construction is built further.
 *
 * - MACHEN Burg|Gebäude|Gebaeude
 * - MACHEN Burg|Gebäude|Gebaeude <size>
 * - MACHEN <Building>
 * - MACHEN <Building> <size>
 */
final class Construction extends AbstractProduct
{
	private int $size;

	protected function run(): void {
		$construction     = $this->unit->Construction();
		$building         = $construction?->Building() ?? $this->getBuilding();
		$this->size       = $construction?->Size() ?? 0;
		$demand           = $this->job->Count();
		$talent           = $building->getCraft()->Talent();
		$this->capability = $this->calculateProduction($building->getCraft());
		$reserve          = $this->calculateResources($building->getMaterial());
		$production       = min($this->capability, $reserve);
		if ($production > 0) {
			$yield = min($production, $demand);
			foreach ($building->getMaterial() as $quantity /* @var Quantity $quantity */) {
				$consumption = new Quantity($quantity->Commodity(), $yield * $quantity->Count());
				$this->unit->Inventory()->remove($consumption);
			}

			if ($construction) {
				$construction->setSize($construction->Size() + $yield);
				if ($this->job->hasCount() && $demand > $production) {
					$this->message(ConstructionOnlyMessage::class)->e($construction)->p($yield);
				} else {
					$this->message(ConstructionBuildMessage::class)->e($construction)->p($yield);
				}
			} else {
				$id           = Lemuria::Catalog()->nextId(Catalog::CONSTRUCTIONS);
				$construction = new ConstructionModel();
				$construction->setName('Gebäude ' . $id)->setId($id);
				$construction->Inhabitants()->add($this->unit);
				$this->unit->Region()->Estate()->add($construction);
				$construction->setBuilding($building)->setSize($yield);
				if ($this->job->hasCount() && $demand > $production) {
					$this->message(ConstructionOnlyMessage::class)->e($construction)->p($yield);
				} else {
					$this->message(ConstructionMessage::class)->s($construction->Building());
				}
			}
		} else {
			if ($this->capability > 0) {
				if ($construction) {
					$this->message(ConstructionResourcesMessage::class)->e($construction);
				} else {
					$this->message(ConstructionCreateMessage::class)->s($building);
				}
			} else {
				if ($construction) {
					$this->message(ConstructionExperienceMessage::class)->e($construction)->s($talent);
				} else {
					$this->message(ConstructionUnableMessage::class)->s($building);
				}
			}
		}
	}

	/**
	 * Get maximum amount that can be produced by knowledge.
	 */
	protected function calculateProduction(Requirement $craft): int {
		if ($this->job->getObject() instanceof Castle) {
			return $this->calculateCastleProduction($this->size);
		}
		return parent::calculateProduction($craft);
	}

	private function calculateCastleProduction(int $size, int $pointsUsed = 0): int {
		$castle = AbstractCastle::forSize($size);
		$craft  = $castle->getCraft();
		$talent = $craft->Talent();
		$cost   = $craft->Level();
		$level  = $this->calculus()->knowledge($talent::class)->Level();
		if ($level < $cost) {
			return 0;
		}

		$points     = $this->unit->Size() * $level - $pointsUsed;
		$production = (int)floor($points / $cost);
		$newSize    = $size + $production;
		$maxSize    = $castle->MaxSize();
		if ($newSize <= $maxSize) {
			return $production;
		}

		$production  = $maxSize - $size;
		$delta       = $production * $cost;
		$points     -= $delta;
		$pointsUsed += $delta;
		$castle      = $castle->Upgrade();
		$cost        = $castle->getCraft()->Level();
		if ($level < $cost || $points < $cost) {
			return $production;
		}

		$production++;
		$newSize++;
		$pointsUsed += $cost;
		return $production + $this->calculateCastleProduction($newSize, $pointsUsed);
	}

	private function getBuilding(): Building {
		$resource = $this->job->getObject();
		if ($resource instanceof Building) {
			return $resource;
		}
		throw new LemuriaException('Expected a building resource.');
	}
}
