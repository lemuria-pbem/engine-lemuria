<?php
declare(strict_types = 1);
namespace Lemuria\Engine\Lemuria\Message\Unit;

use Lemuria\Engine\Lemuria\Message\LemuriaMessage;
use Lemuria\Engine\Message;
use Lemuria\Id;

class DisguiseKnownPartyMessage extends AbstractUnitMessage
{
	protected string $level = Message::SUCCESS;

	protected Id $party;

	protected function create(): string {
		return 'Unit ' . $this->id . ' claims belonging to party ' . $this->party . '.';
	}

	protected function getData(LemuriaMessage $message): void {
		parent::getData($message);
		$this->party = $message->get();
	}
}