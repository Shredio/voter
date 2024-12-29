<?php declare(strict_types = 1);

namespace Tests;

use Shredio\Voter\Attribute\VoteOnAttribute;
use Shredio\Voter\Attribute\VoteOnSubject;
use Shredio\Voter\EnhancedVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class VoterForTests extends EnhancedVoter
{

	/** @var mixed[] */
	public array $values = [];

	#[VoteOnSubject('subjectOptionalParams')]
	public function subjectOptionalParams(SubjectForTests $subject, ?UserInterface $user): bool
	{
		$this->values = [$subject, $user];

		return true;
	}

	#[VoteOnSubject('subjectParams')]
	public function subjectParams(
		SubjectForTests $subject,
		string $attribute,
		AccessDecisionManagerInterface $accessDecisionManager,
		UserInterface $user,
		TokenInterface $token,
	): bool
	{
		$this->values = [$subject, $attribute, $accessDecisionManager, $user, $token];

		return true;
	}

	#[VoteOnSubject('subject')]
	public function subject(SubjectForTests $subject): bool
	{
		$this->values = [$subject];

		return true;
	}

	#[VoteOnAttribute('attributeValue')]
	public function attributeValue(string $attribute): bool
	{
		$this->values = [$attribute];

		return true;
	}

	#[VoteOnAttribute('emptyAttribute')]
	public function emptyAttribute(): bool
	{
		return true;
	}

	#[VoteOnAttribute('denyAttribute')]
	public function denyAttribute(): bool
	{
		return false;
	}

	#[VoteOnAttribute('null')]
	public function nullAttribute(): null
	{
		return null;
	}

}
