<?php declare(strict_types = 1);

namespace Shredio\Voter\Resolver;

use LogicException;
use Shredio\Voter\Context\VoterContext;
use Shredio\Voter\Metadata\ParameterScope;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class VoterParameterResolver
{

	public function __construct(
		private AccessDecisionManagerInterface $accessDecisionManager,
	)
	{
	}

	/**
	 * @param array{ scope: value-of<ParameterScope>, classType?: class-string, nullable?: bool }[] $parameters
	 * @return mixed[]
	 */
	public function resolve(TokenInterface $token, mixed $subject, string $attribute, array $parameters): ?array
	{
		$args = [];

		foreach ($parameters as $parameter) {
			$scope = ParameterScope::from($parameter['scope']);

			if ($scope === ParameterScope::Subject) {
				$args[] = $subject;
			} else if ($scope === ParameterScope::CustomUser || $scope === ParameterScope::User) {
				$user = $this->resolveUser($token->getUser(), $parameter['nullable'] ?? false);

				if ($user === false) {
					return null;
				}

				$args[] = $user;
			} else if ($scope === ParameterScope::Token) {
				$args[] = $token;
			} else if ($scope === ParameterScope::Attribute) {
				$args[] = $attribute;
			} else if ($scope === ParameterScope::Custom) {
				$classType = $parameter['classType'] ?? null;

				if (!$classType) {
					throw new LogicException('Unexpected error, classType is required for custom parameter scope.');
				}

				$context ??= new VoterContext($subject, $attribute, $token->getUser(), $token, $this->accessDecisionManager);
				$args[] = new $classType($context);
			} else {
				$args[] = $this->accessDecisionManager;
			}
		}

		return $args;
	}

	private function resolveUser(?UserInterface $user, bool $nullable): UserInterface|null|false
	{
		if ($nullable) {
			return $user;
		}

		return $user === null ? false : $user;
	}

}
