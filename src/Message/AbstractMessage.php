<?php
declare(strict_types = 1);
namespace Lemuria\Engine\Lemuria\Message;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Pure;

use function Lemuria\getClass;
use function Lemuria\number;
use Lemuria\Engine\Report;
use Lemuria\Engine\Message;
use Lemuria\Id;
use Lemuria\Model\Dictionary;
use Lemuria\SingletonTrait;

abstract class AbstractMessage implements MessageType
{
	use SingletonTrait;

	protected string $level = Message::DEBUG;

	protected Id $id;

	#[ExpectedValues(valuesFromClass: Report::class)]
	#[Pure] public function Level(): string {
		return $this->level;
	}

	public function render(LemuriaMessage $message): string {
		$this->getData($message);
		return $this->translate() ?? $this->create();
	}

	abstract protected function create(): string;

	protected function getData(LemuriaMessage $message): void {
		$this->id = $message->Assignee();
	}

	protected function translate(): ?string {
		$translation = $this->translateKey('message.' . getClass($this));
		if ($translation === null) {
			return null;
		}
		foreach ($this->getVariables() as $name) {
			$translation = str_replace('$' . $name, $this->getTranslation($name), $translation);
		}
		return $translation;
	}

	protected function translateKey(string $keyPath, ?int $index = null): ?string {
		$dictionary  = new Dictionary();
		$translation = $dictionary->get($keyPath, $index);
		if ($index !== null) {
			$keyPath .= '.' . $index;
		}
		return $translation === $keyPath ? null : $translation;
	}

	protected function getVariables(): array {
		$properties = [];
		$reflection = new \ReflectionClass($this);
		foreach ($reflection->getProperties() as $property) {
			$name = $property->getName();
			if ($name !== 'level') {
				$properties[] = $name;
			}
		}
		return $properties;
	}

	protected function getTranslation(string $name): string {
		return (string)$this->$name;
	}

	protected function building(string $property, string $name): ?string {
		return $this->getTranslatedName($property, $name, 'building');
	}

	protected function commodity(string $property, string $name): ?string {
		return $this->getTranslatedName($property, $name, 'resource', 1);
	}

	protected function item(string $property, string $name): ?string {
		if ($property === $name) {
			$commodity = getClass($this->$name->Commodity());
			$count     = $this->$name->Count();
			$item      = $this->translateKey('resource.' . $commodity, $count > 1 ? 1 : 0);
			if ($item) {
				return number($count) . ' ' . $item;
			}
		}
		return null;
	}

	protected function landscape(string $property, string $name): ?string {
		return $this->getTranslatedName($property, $name, 'landscape');
	}

	protected function ship(string $property, string $name): ?string {
		return $this->getTranslatedName($property, $name, 'ship');
	}

	protected function talent(string $property, string $name): ?string {
		return $this->getTranslatedName($property, $name, 'talent');
	}

	protected function number(string $property, string $name): ?string {
		return $property === $name ? number($this->$name) : null;
	}

	private function getTranslatedName(string $property, string $name, string $prefix, ?int $index = null): ?string {
		if ($property === $name) {
			$class = getClass($this->$name);
			$class = $this->translateKey($prefix . '.' . $class, $index);
			if ($class) {
				return $class;
			}
		}
		return null;
	}
}
