<?php declare(strict_types = 1);

namespace Shredio\Voter\Service;

use Shredio\Voter\Context\VoterContext;

abstract class VoterService
{

	final public function __construct(
		protected readonly VoterContext $context,
	)
	{
	}

}
