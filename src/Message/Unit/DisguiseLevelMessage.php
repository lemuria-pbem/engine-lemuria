<?php
declare(strict_types = 1);
namespace Lemuria\Engine\Lemuria\Message\Unit;

use Lemuria\Engine\Lemuria\Message\LemuriaMessage;

class DisguiseLevelMessage extends DisguiseMessage
{
	protected int $camouflage;

	protected function create(): string {
		return 'Unit ' . $this->id . ' will camouflage up to level ' . $this->camouflage . '.';
	}

	protected function getData(LemuriaMessage $message): void {
		parent::getData($message);
		$this->camouflage = $message->getParameter();
	}
}
