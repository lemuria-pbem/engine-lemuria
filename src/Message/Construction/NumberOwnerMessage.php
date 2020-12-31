<?php
declare(strict_types = 1);
namespace Lemuria\Engine\Lemuria\Message\Construction;

use Lemuria\Engine\Lemuria\Message\LemuriaMessage;
use Lemuria\Engine\Message;
use Lemuria\Id;

class NumberOwnerMessage extends AbstractConstructionMessage
{
	public const OWNER = 'owner';

	protected string $level = Message::FAILURE;

	protected Id $owner;

	protected function create(): string {
		return 'Unit ' . $this->owner . ' is not owner of construction ' . $this->id . ' and thus cannot change its ID.';
	}

	protected function getData(LemuriaMessage $message): void {
		parent::getData($message);
		$this->owner = $message->get(self::OWNER);
	}
}
