<?php
declare(strict_types = 1);
namespace Lemuria\Engine\Lemuria\Message\Unit;

use Lemuria\Engine\Lemuria\Message\LemuriaMessage;

class TeachBonusMessage extends AbstractUnitMessage
{
	public const STUDENTS = 's';

	public const BONUS = 'b';

	private int $students;

	private float $bonus;

	/**
	 * @return string
	 */
	protected function create(): string {
		return 'Unit ' . $this->id . ' has ' . $this->students . ' students (bonus: ' . $this->bonus . ').';
	}

	/**
	 * @param LemuriaMessage $message
	 */
	protected function getData(LemuriaMessage $message): void {
		parent::getData($message);
		$this->students = $message->getParameter(self::STUDENTS);
		$this->bonus = $message->getParameter(self::BONUS);
	}
}
