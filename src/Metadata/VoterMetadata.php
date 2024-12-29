<?php declare(strict_types = 1);

namespace Shredio\Voter\Metadata;

use OutOfBoundsException;

final readonly class VoterMetadata
{

	/**
	 * @param array<string, array<string, array{
	 *     subjectType: class-string|null,
	 *     parameters: array{ scope: value-of<ParameterScope>, classType?: class-string, nullable?: bool }[]
	 * }>> $metadata [attribute => [method => [subjectType, parameters]]
	 */
	public function __construct(
		public string $className,
		private array $metadata,
	)
	{
	}

	public function hasAttribute(string $attribute): bool
	{
		return isset($this->metadata[$attribute]);
	}

	public function getMethodFor(string $attribute, mixed $subject): ?string
	{
		foreach ($this->metadata[$attribute] ?? [] as $method => $metadata) {
			if ($subject === null) {
				if ($metadata['subjectType'] === null) {
					return $method;
				}

				continue;
			}

			if ($metadata['subjectType'] === null) {
				continue;
			}

			if ($subject instanceof $metadata['subjectType']) {
				return $method;
			}
		}

		return null;
	}

	/**
	 * @return array{ scope: value-of<ParameterScope>, classType?: class-string, nullable?: bool }[]
	 */
	public function getParameterSchema(string $attribute, string $method): array
	{
		return $this->metadata[$attribute][$method]['parameters'] ?? throw new OutOfBoundsException(sprintf('Method %s does not exists.', $method));
	}

}
