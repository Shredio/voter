<?php declare(strict_types = 1);

namespace Shredio\Voter;

use Shredio\Voter\Metadata\VoterMetadata;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;

abstract class EnhancedVoter implements CacheableVoterInterface
{

	private ?VoterMetadata $metadata = null;

	public function __construct(
		private readonly EnhancedVoterServices $services,
	)
	{
	}

	/**
	 * @internal
	 */
	final public function setMetadata(?VoterMetadata $metadata): void
	{
		$this->metadata = $metadata;
	}

	final public function supportsAttribute(string $attribute): bool
	{
		return $this->getMetadata()->hasAttribute($attribute);
	}

	final public function supportsType(string $subjectType): bool
	{
		return true;
	}

	/**
	 * @param string[] $attributes
	 */
	final public function vote(TokenInterface $token, mixed $subject, array $attributes): int
	{
		$metadata = $this->getMetadata();
		$vote = self::ACCESS_ABSTAIN;

		foreach ($attributes as $attribute) {
			$method = $metadata->getMethodFor($attribute, $subject);

			if (!$method) {
				continue;
			}

			$args = $this->services->voterParameterResolver->resolve($token, $subject, $attribute, $metadata->getParameterSchema($attribute, $method));

			$vote = self::ACCESS_DENIED;

			if ($args === null) { // early access denied
				continue;
			}

			// @phpstan-ignore-next-line
			if (call_user_func_array([$this, $method], $args)) {
				return self::ACCESS_GRANTED;
			}
		}

		return $vote;
	}

	private function getMetadata(): VoterMetadata
	{
		return $this->metadata ??= $this->services->metadataFactory->create($this::class);
	}

}
