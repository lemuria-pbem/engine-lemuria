<?php
declare(strict_types = 1);
namespace Lemuria\Engine\Lemuria\Message\Construction;

use Lemuria\Engine\Lemuria\Message\LemuriaMessage;
use Lemuria\Engine\Message;
use Lemuria\Id;

class NameCastleMessage extends AbstractConstructionMessage
{
	public const OWNER = 'owner';

	protected string $level = Message::FAILURE;

	private Id $owner;

	/**
	 * @return string
	 */
	protected function create(): string {
		return 'Unit ' . $this->owner . ' is not owner of the biggest castle in region '. $this->id . ' and thus cannot rename it.';
	}

	/**
	 * @param LemuriaMessage $message
	 */
	protected function getData(LemuriaMessage $message): void {
		parent::getData($message);
		$this->owner = $message->get(self::OWNER);
	}
}
