<?php declare(strict_types = 1);

namespace Tests;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use Shredio\Voter\EnhancedVoterServices;
use Shredio\Voter\Resolver\VoterParameterResolver;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class VoterTest extends TestCase
{

	private EnhancedVoterServices $services;

	private AccessDecisionManager $accessDecisionManager;

	private VoterForTests $voter;

	protected function setUp(): void
	{
		$iterator = new ArrayIterator();
		$this->accessDecisionManager = new AccessDecisionManager($iterator);
		$this->services = new EnhancedVoterServices(
			new VoterParameterResolver($this->accessDecisionManager),
		);
		$this->voter = $iterator[0] =  new VoterForTests($this->services);
	}

	public function testAttributeWithoutUser(): void
	{
		$this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(new NullToken(), null, ['emptyAttribute']));

		$this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote(new NullToken(), null, ['attributeValue']));
		$this->assertSame(['attributeValue'], $this->voter->values);

		$this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote(new NullToken(), null, ['denyAttribute']));
	}

	public function testSubjectWithoutUser(): void
	{
		$token = new NullToken();

		$this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $s = new SubjectForTests(), ['subject']));
		$this->assertSame([$s], $this->voter->values);

		$this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $s = new SubjectForTests(), ['subjectOptionalParams']));
		$this->assertSame([$s, null], $this->voter->values);

		$this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $s = new SubjectForTests(), ['subjectParams']));
	}

	public function testSubjectWithUser(): void
	{
		$token = new UsernamePasswordToken($user = new StubUser(), 'firewall', ['ROLE_USER']);

		$this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $s = new SubjectForTests(), ['subjectParams']));
		$this->assertEquals([$s, 'subjectParams', $this->accessDecisionManager, $user, $token], $this->voter->values);
	}

	public function testAccessDecisionManager(): void
	{
		$this->assertTrue($this->accessDecisionManager->decide(new NullToken(), ['emptyAttribute']));
	}

}
