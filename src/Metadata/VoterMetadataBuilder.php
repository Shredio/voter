<?php declare(strict_types = 1);

namespace Shredio\Voter\Metadata;

use Symfony\Component\DependencyInjection\Definition;

final class VoterMetadataBuilder
{

	/** @var array<string, array<string, array{
	 * subjectType: class-string|null,
	 * parameters: array{ scope: value-of<ParameterScope>, classType?: class-string, nullable?: bool }[]
	 * }>>
	 */
	private array $metadata = [];

	/**
	 * @param class-string $voterClass
	 */
	public function __construct(
		private readonly string $voterClass,
	)
	{
	}

	/**
	 * @param string[] $attributes
	 * @param array{ scope: value-of<ParameterScope>, classType?: class-string, nullable?: bool }[] $parameters
	 * @param class-string|null $subjectType
	 */
	public function addMetadata(string $method, array $attributes, array $parameters, ?string $subjectType): void
	{
		foreach ($attributes as $attribute) {
			$this->metadata[$attribute][$method] = [
				'subjectType' => $subjectType,
				'parameters' => $parameters,
			];
		}
	}

	public function build(): VoterMetadata
	{
		return new VoterMetadata(
			$this->voterClass,
			$this->metadata,
		);
	}

	public function buildDefinition(): Definition
	{
		return new Definition(VoterMetadata::class, [
			$this->voterClass,
			$this->metadata,
		]);
	}

	public function isEmpty(): bool
	{
		return !$this->metadata;
	}

}
