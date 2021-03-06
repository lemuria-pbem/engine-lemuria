<?php
declare(strict_types = 1);
namespace Lemuria\Engine\Lemuria\Message\Unit;

use Lemuria\Engine\Lemuria\Message\LemuriaMessage;
use Lemuria\Singleton;

class RawMaterialWantsMessage extends AbstractUnitMessage
{
	protected Singleton $commodity;

	protected int $production;

	protected function create(): string {
		return 'Unit ' . $this->id . ' wants to produce ' . $this->production . ' ' . $this->commodity . '.';
	}

	protected function getData(LemuriaMessage $message): void {
		parent::getData($message);
		$this->commodity = $message->getSingleton();
		$this->production = $message->getParameter();
	}

	protected function getTranslation(string $name): string {
		$commodity = $this->commodity($name, 'commodity');
		if ($commodity) {
			return $commodity;
		}
		$production = $this->number($name, 'production');
		if ($production) {
			return $production;
		}
		return parent::getTranslation($name);
	}
}
