<?php
declare(strict_types = 1);
namespace Lemuria\Engine\Lemuria\Message\Unit;

use Lemuria\Engine\Lemuria\Message\LemuriaMessage;
use Lemuria\Engine\Message;
use Lemuria\Singleton;

class LearnProgressMessage extends AbstractUnitMessage
{
	protected string $level = Message::SUCCESS;

	protected Singleton $talent;

	protected int $experience;

	protected function create(): string {
		return 'Unit ' . $this->id . ' learns ' . $this->talent . ' with ' . $this->experience . ' experience.';
	}

	protected function getData(LemuriaMessage $message): void {
		parent::getData($message);
		$this->talent = $message->getSingleton();
		$this->experience = $message->getParameter();
	}

	protected function getTranslation(string $name): string {
		$experience = $this->number($name, 'experience');
		if ($experience) {
			return $experience;
		}
		$talent = $this->talent($name, 'talent');
		if ($talent) {
			return $talent;
		}
		return parent::getTranslation($name);
	}
}
