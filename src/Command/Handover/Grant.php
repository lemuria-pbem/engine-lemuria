<?php
declare (strict_types = 1);
namespace Lemuria\Engine\Lemuria\Command\Handover;

use Lemuria\Engine\Lemuria\Exception\InvalidCommandException;
use Lemuria\Engine\Lemuria\Command\UnitCommand;
use Lemuria\Engine\Lemuria\Message\Unit\GrantFromOutsideMessage;
use Lemuria\Engine\Lemuria\Message\Unit\GrantMessage;
use Lemuria\Engine\Lemuria\Message\Unit\GrantNothingMessage;
use Lemuria\Engine\Lemuria\Message\Unit\GrantNotInsideMessage;
use Lemuria\Id;
use Lemuria\Lemuria;
use Lemuria\Model\Catalog;
use Lemuria\Model\Lemuria\Unit;

/**
 * A unit who is owner of a construction or vessel grants another unit inside the command over it.
 *
 * - GIB <Unit> Kommando
 * - KOMMANDO <Unit>
 */
final class Grant extends UnitCommand
{
	protected function run(): void {
		if ($this->phrase->count() < 1) {
			throw new InvalidCommandException($this, 'Missing unit parameter.');
		}
		$id = Id::fromId($this->phrase->getParameter());

		$construction = $this->unit->Construction();
		if ($construction) {
			$inhabitants = $construction->Inhabitants();
			if ($inhabitants->Owner()->Id()->Id() === $this->unit->Id()->Id()) {
				if ($inhabitants->has($id)) {
					$unit = Lemuria::Catalog()->get($id, Catalog::UNITS); /* @var Unit $unit */
					$inhabitants->setOwner($unit);
					$this->message(GrantMessage::class)->e($unit);
				} else {
					$this->message(GrantNotInsideMessage::class)->p($id->Id());
				}
			} else {
				$this->message(GrantNothingMessage::class);
			}
		} else {
			$this->message(GrantFromOutsideMessage::class);
		}
	}
}
