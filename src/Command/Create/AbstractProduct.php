<?php
declare (strict_types = 1);
namespace Lemuria\Engine\Lemuria\Command\Create;

use Lemuria\Engine\Lemuria\Activity;
use Lemuria\Engine\Lemuria\Command\UnitCommand;
use Lemuria\Engine\Lemuria\Context;
use Lemuria\Engine\Lemuria\Factory\Model\Job;
use Lemuria\Engine\Lemuria\Message\Unit\AllocationTakeMessage;
use Lemuria\Engine\Lemuria\Phrase;
use Lemuria\Model\Lemuria\Quantity;
use Lemuria\Model\Lemuria\Requirement;
use Lemuria\Model\Lemuria\Resources;

/**
 * Implementation of command MACHEN <amount> <product> (create product).
 *
 * The command creates new products from inventory and adds them to the executing unit's inventory.
 *
 * - MACHEN <product>
 * - MACHEN <amount> <product>
 */
abstract class AbstractProduct extends UnitCommand implements Activity
{
	protected int $capability = 0;

	public function __construct(Phrase $phrase, Context $context, protected Job $job) {
		parent::__construct($phrase, $context);
	}

	/**
	 * Get maximum amount that can be produced by knowledge.
	 */
	protected function calculateProduction(Requirement $craft): int {
		$production = 0;
		$talent     = $craft->Talent();
		$cost       = $craft->Level();
		$level      = $this->calculus()->knowledge(get_class($talent))->Level();
		if ($level >= $cost) {
			$production = (int)floor($this->unit->Size() * $level / $cost);
		}
		return $production;
	}

	/**
	 * Get maximum amount that can be produced by resources.
	 */
	protected function calculateResources(Resources $resources): int {
		$reserves   = $this->unit->Inventory();
		$production = PHP_INT_MAX;
		foreach ($resources as $quantity /* @var Quantity $quantity */) {
			$commodity = $quantity->Commodity();
			$needed    = $this->capability * $quantity->Count();
			$reserve   = $reserves->offsetGet($commodity)->Count();
			if ($reserve < $needed) {
				$taking       = new Quantity($commodity, $needed - $reserve);
				$resourcePool = $this->context->getResourcePool($this->unit);
				$resourcePool->take($this->unit, $taking);
				$this->message(AllocationTakeMessage::class)->i($taking);
			}
			$reserve    = $reserves->offsetGet($commodity);
			$amount     = (int)floor($reserve->Count() / $quantity->Count());
			$production = min($production, $amount);
			if ($production <= 0) {
				break;
			}
		}
		return $production;
	}
}
