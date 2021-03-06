<?php
declare(strict_types = 1);
namespace Lemuria\Engine\Lemuria\Message\Party;

use JetBrains\PhpStorm\Pure;

use Lemuria\Engine\Lemuria\Message\AbstractMessage;
use Lemuria\Engine\Report;

abstract class AbstractPartyMessage extends AbstractMessage
{
	#[Pure] public function Report(): int {
		return Report::PARTY;
	}
}
