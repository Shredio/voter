<?php declare(strict_types = 1);

namespace Shredio\Voter\Context;

use RuntimeException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class VoterContext
{

	public function __construct(
		public mixed $subject,
		public string $attribute,
		public ?UserInterface $user,
		public TokenInterface $token,
		public AccessDecisionManagerInterface $accessDecisionManager,
	)
	{
	}

	/**
	 * @template T of UserInterface
	 * @param class-string<T> $class
	 * @return T
	 */
	public function getUser(string $class): UserInterface
	{
		assert($this->user instanceof $class);

		return $this->user;
	}

	public function getSubjectAsObject(): object
	{
		if (!is_object($this->subject)) {
			throw new RuntimeException(sprintf('Subject is not an object, got %s.', get_debug_type($this->subject)));
		}

		return $this->subject;
	}

}
