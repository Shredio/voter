<?php declare(strict_types = 1);

namespace Shredio\Voter\Metadata;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Shredio\Voter\Attribute\VoteAttribute;
use Shredio\Voter\Attribute\VoteOnSubject;
use Shredio\Voter\Exception\InvalidEnhancedVoterException;
use Shredio\Voter\Service\VoterService;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class VoterMetadataFactory
{

	private const array MethodsToSkip = [
		'setMetadata' => true,
		'supportsAttribute' => true,
		'supportsType' => true,
		'vote' => true,
	];

	public function __construct(
		private readonly ?string $nameConventionForMethods = null,
	)
	{
	}

	/**
	 * @param class-string $voter
	 */
	public function createDefinition(string $voter): Definition
	{
		return $this->createBuilder($voter)->buildDefinition();
	}

	/**
	 * @param class-string $voter
	 */
	public function create(string $voter): VoterMetadata
	{
		return $this->createBuilder($voter)->build();
	}

	/**
	 * @param class-string $voter
	 */
	private function createBuilder(string $voter): VoterMetadataBuilder
	{
		$reflection = new ReflectionClass($voter);
		$builder = new VoterMetadataBuilder($voter);

		foreach ($reflection->getMethods() as $method) {
			if (str_starts_with($method->name, '__')) {
				continue;
			}

			if ($method->isStatic() || $method->isAbstract()) {
				continue;
			}

			if (isset(self::MethodsToSkip[$method->name])) {
				continue;
			}

			$attributes = $method->getAttributes(VoteAttribute::class, ReflectionAttribute::IS_INSTANCEOF);

			if (!$attributes) {
				if ($this->nameConventionForMethods && str_starts_with($method->name, $this->nameConventionForMethods)) {
					$this->throwMethodException($method, 'probably missing vote attribute');
				}

				continue;
			}

			if (count($attributes) > 1) {
				$this->throwMethodException($method, 'must have only one attribute');
			}

			if ($method->isPrivate()) {
				$this->throwMethodException($method, 'must not be private');
			}

			$attribute = $attributes[0]->newInstance();

			$this->extract($builder, $method, $attribute);

			$this->checkMethodReturnType($method);
		}

		if ($builder->isEmpty()) {
			$this->throwClassException($reflection, 'missing any vote method with attributes');
		}

		return $builder;
	}

	private function extract(VoterMetadataBuilder $builder, ReflectionMethod $method, VoteAttribute $attribute): void
	{
		$hasSubject = $attribute instanceof VoteOnSubject;
		$parameters = $this->extractMethodParameters($method, $method->getParameters(), $hasSubject);

		if ($hasSubject) {
			$subjectType = $this->getSubjectType($parameters);

			if (!$subjectType) {
				$this->throwMethodException($method, 'must have subject parameter');
			}
		} else {
			$subjectType = null;
		}

		$builder->addMetadata($method->name, $attribute->attributes, $parameters, $subjectType);
	}

	/**
	 * @param ReflectionParameter[] $parameters
	 * @return array{ scope: value-of<ParameterScope>, classType?: class-string, nullable?: bool }[]
	 */
	private function extractMethodParameters(ReflectionMethod $method, array $parameters, bool $expectSubject = false): array
	{
		$values = [];

		foreach ($parameters as $parameter) {
			$type = $parameter->getType();

			if (!$type) {
				$this->throwParameterException($method, $parameter, 'must have type-hint to resolve');
			}

			if (!$type instanceof ReflectionNamedType) {
				$this->throwParameterException($method, $parameter, 'complex type-hint cannot be resolved');
			}

			if ($type->isBuiltin()) { // probably attribute
				if ($parameter->getName() !== 'attribute' || $type->getName() !== 'string') {
					$this->throwParameterException($method, $parameter, 'only string $attribute parameter is allowed as scalar');
				}

				if ($type->allowsNull()) {
					$this->throwParameterException($method, $parameter, 'unnecessary nullable type-hint');
				}

				$values[] = [
					'scope' => ParameterScope::Attribute->value,
				];

				continue;
			}

			$scope = $this->resolveParameterType($method, $parameter, $type);

			if (!$scope && $expectSubject) { // probably subject
				if ($type->allowsNull()) {
					$this->throwParameterException($method, $parameter, 'unnecessary nullable type-hint');
				}

				$scope = ParameterScope::Subject;
				$expectSubject = false;
			} else if ($scope && $expectSubject) {
				$this->throwMethodException($method, 'subject parameter must be the first parameter');
			} else if (!$scope) {
				$this->throwParameterException($method, $parameter, sprintf('type %s cannot be resolved', $type->getName()));
			}

			$value = [
				'scope' => $scope->value,
			];

			if (in_array($scope, [ParameterScope::CustomUser, ParameterScope::Subject], true)) {
				/** @var class-string $classType */
				$classType = $type->getName();

				$value['classType'] = $classType;
			} else if ($scope === ParameterScope::Custom) {
				/** @var class-string $classType */
				$classType = $type->getName();

				$value['classType'] = $classType;
			}

			if ($type->allowsNull()) {
				$value['nullable'] = true;
			}

			$values[] = $value;
		}

		return $values;
	}

	/**
	 * @param array{ scope: value-of<ParameterScope>, classType?: class-string }[] $parameters
	 * @return class-string|null
	 */
	private function getSubjectType(array $parameters): ?string
	{
		foreach ($parameters as $parameter) {
			if ($parameter['scope'] === ParameterScope::Subject->value) {
				return $parameter['classType'] ?? null;
			}
		}

		return null;
	}

	/**
	 * @param ReflectionClass<object> $class
	 */
	private function throwClassException(ReflectionClass $class, string $message): never
	{
		throw new InvalidEnhancedVoterException(sprintf(
			'Class %s %s',
			$class->name,
			$message,
		));
	}

	private function throwMethodException(ReflectionMethod $method, string $message): never
	{
		throw new InvalidEnhancedVoterException(sprintf(
			'Method %s::%s() %s',
			$method->getDeclaringClass()->name,
			$method->name,
			$message,
		));
	}

	private function throwParameterException(ReflectionMethod $method, ReflectionParameter $parameter, string $message): never
	{
		throw new InvalidEnhancedVoterException(sprintf(
			'Parameter $%s of %s::%s() %s',
			$parameter->name,
			$method->getDeclaringClass()->name,
			$method->name,
			$message,
		));
	}

	private function resolveParameterType(ReflectionMethod $method, ReflectionParameter $parameter, ReflectionNamedType $type): ?ParameterScope
	{
		$name = $type->getName();
		if ($name === TokenInterface::class) {
			if ($type->allowsNull()) {
				$this->throwParameterException($method, $parameter, 'unnecessary nullable type-hint');
			}

			return ParameterScope::Token;
		}

		if ($name === AccessDecisionManagerInterface::class) {
			if ($type->allowsNull()) {
				$this->throwParameterException($method, $parameter, 'unnecessary nullable type-hint');
			}

			return ParameterScope::AccessDecisionManager;
		}

		if ($name === UserInterface::class) {
			return ParameterScope::User;
		}

		if (is_a($name, UserInterface::class, true)) {
			return ParameterScope::CustomUser;
		}

		if (is_a($name, VoterService::class, true)) {
			if ($type->allowsNull()) {
				$this->throwParameterException($method, $parameter, 'unnecessary nullable type-hint');
			}

			$reflectionService = new ReflectionClass($name);

			if ($reflectionService->isAbstract()) {
				$this->throwParameterException($method, $parameter, 'service must not be abstract');
			}

			return ParameterScope::Custom;
		}

		return null;
	}

	private function checkMethodReturnType(ReflectionMethod $method): void
	{
		$type = $method->getReturnType();

		if (!$type) {
			$this->throwMethodException($method, 'must have return type-hint');
		}

		if (!$type instanceof ReflectionNamedType || ($type->getName() !== 'bool' && $type->getName() !== 'null')) {
			$this->throwMethodException($method, 'must returns bool or null');
		}
	}

}
