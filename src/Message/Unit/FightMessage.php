<?php
declare(strict_types = 1);
namespace Lemuria\Engine\Lemuria\Message\Unit;

use Lemuria\Engine\Lemuria\Message\LemuriaMessage;
use Lemuria\Engine\Message;

class FightMessage extends AbstractUnitMessage
{
	protected string $level = Message::SUCCESS;

	protected int $position;

	protected function create(): string {
		return 'Unit ' . $this->id . ' will fight at position ' . $this->position . '.';
	}

	protected function getData(LemuriaMessage $message): void {
		parent::getData($message);
		$this->position = $message->getParameter();
	}

	protected function getTranslation(string $name): string {
		if ($name === 'position') {
			$position = $this->translateKey('combat.battleRow.position_' . $this->position);
			if ($position) {
				return $position;
			}
		}
		return parent::getTranslation($name);
	}
}
