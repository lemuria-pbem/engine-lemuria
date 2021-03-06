<?php
declare (strict_types = 1);
namespace Lemuria\Engine\Lemuria\Command\Create;

use function Lemuria\getClass;
use Lemuria\Engine\Lemuria\Activity;
use Lemuria\Engine\Lemuria\Command\AllocationCommand;
use Lemuria\Engine\Lemuria\Context;
use Lemuria\Engine\Lemuria\Factory\DefaultActivityTrait;
use Lemuria\Engine\Lemuria\Factory\Model\Job;
use Lemuria\Engine\Lemuria\Message\Unit\RawMaterialCanMessage;
use Lemuria\Engine\Lemuria\Message\Unit\RawMaterialCannotMessage;
use Lemuria\Engine\Lemuria\Message\Unit\RawMaterialExperienceMessage;
use Lemuria\Engine\Lemuria\Message\Unit\RawMaterialGuardedMessage;
use Lemuria\Engine\Lemuria\Message\Unit\RawMaterialNoDemandMessage;
use Lemuria\Engine\Lemuria\Message\Unit\RawMaterialOnlyMessage;
use Lemuria\Engine\Lemuria\Message\Unit\RawMaterialOutputMessage;
use Lemuria\Engine\Lemuria\Message\Unit\RawMaterialResourcesMessage;
use Lemuria\Engine\Lemuria\Message\Unit\RawMaterialWantsMessage;
use Lemuria\Engine\Lemuria\Phrase;
use Lemuria\Exception\LemuriaException;
use Lemuria\Model\Lemuria\Ability;
use Lemuria\Model\Lemuria\Commodity;
use Lemuria\Model\Lemuria\Party;
use Lemuria\Model\Lemuria\Quantity;
use Lemuria\Model\Lemuria\RawMaterial as RawMaterialInterface;
use Lemuria\Model\Lemuria\Relation;
use Lemuria\Model\Lemuria\Resources;
use Lemuria\Model\Lemuria\Talent;

/**
 * Implementation of command MACHEN <amount> <RawMaterial> (create raw material).
 *
 * The command creates new resources from region reserve and adds them to the executing unit's inventory.
 *
 * - MACHEN <RawMaterial>
 * - MACHEN <amount> <RawMaterial>
 */
final class RawMaterial extends AllocationCommand implements Activity
{
	use DefaultActivityTrait;

	private Ability $knowledge;

	private ?int $demand = null;

	private int $production = 0;

	public function __construct(Phrase $phrase, Context $context, private Job $job) {
		parent::__construct($phrase, $context);
	}

	public function allocate(Resources $resources): void {
		parent::allocate($resources);

		$resource   = $this->job->getObject();
		$talent     = $this->knowledge->Talent();
		$production = $this->getResource(getClass($resource))->Count();
		if ($production <= 0) {
			if ($this->knowledge->Level() <= 0) {
				$this->message(RawMaterialExperienceMessage::class)->s($talent, RawMaterialExperienceMessage::TALENT)->s($resource, RawMaterialExperienceMessage::MATERIAL);
			} else {
				$guardParties = $this->checkBeforeAllocation();
				if (!empty($guardParties)) {
					$this->message(RawMaterialGuardedMessage::class)->s($resource);
				} else {
					$this->message(RawMaterialResourcesMessage::class)->s($resource);
				}
			}
		} else {
			$this->resources->rewind();
			/* @var Quantity $quantity */
			$quantity = $this->resources->current();
			$this->unit->Inventory()->add($quantity);
			if ($quantity->Count() < $this->production || $this->production < $this->demand) {
				$this->message(RawMaterialOnlyMessage::class)->i($quantity)->s($talent);
			} else {
				$this->message(RawMaterialOutputMessage::class)->i($quantity)->s($talent);
			}
		}
	}

	/**
	 * Check region guards before allocation.
	 *
	 * If region is guarded by other parties and there are no RESOURCES relations, this unit may only produce if it is
	 * not in a building and has better camouflage than all the blocking guards' perception.
	 *
	 * @return Party[]
	 */
	protected function getCheckBeforeAllocation(): array {
		return $this->getCheckByAgreement(Relation::RESOURCES);
	}

	/**
	 * Determine the demand.
	 */
	protected function createDemand(): void {
		$resource         = $this->job->getObject();
		$this->knowledge  = $this->calculus()->knowledge($this->getRequiredTalent());
		$this->production = $this->unit->Size() * $this->knowledge->Level();
		if ($this->production > 0) {
			if (count($this->phrase) === 2) {
				$this->demand = (int)$this->phrase->getParameter(1);
				if ($this->demand <= $this->production) {
					$this->production = (int)$this->demand;
					$this->message(RawMaterialWantsMessage::class)->p($this->production)->s($resource);
				} else {
					$this->message(RawMaterialCannotMessage::class)->p($this->production)->s($resource);
				}
			} else {
				$this->message(RawMaterialCanMessage::class)->p($this->production)->s($resource);
			}
			$this->resources->add(new Quantity($this->getCommodity(), $this->production));
		} else {
			$this->message(RawMaterialNoDemandMessage::class)->s($resource);
		}
	}

	private function getCommodity(): Commodity {
		$resource = $this->job->getObject();
		if ($resource instanceof Commodity) {
			return $resource;
		}
		throw new LemuriaException($resource . ' is not a commodity.');
	}
	/**
	 * Determine the required talent.
	 */
	private function getRequiredTalent(): Talent {
		$resource = $this->job->getObject();
		if ($resource instanceof RawMaterialInterface) {
			return $resource->getTalent();
		}
		throw new LemuriaException($resource . ' is not a raw material.');
	}
}
