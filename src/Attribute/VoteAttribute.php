<?php declare(strict_types = 1);

namespace Shredio\Voter\Attribute;

abstract readonly class VoteAttribute
{

	/** @var non-empty-array<string> */
	public array $attributes;

	/**
	 * @param string|non-empty-array<string> $attributes
	 */
	public function __construct(
		string|array $attributes,
	)
	{
		$this->attributes = (array) $attributes;
	}

}
