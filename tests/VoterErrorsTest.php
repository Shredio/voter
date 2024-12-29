<?php declare(strict_types = 1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Shredio\Voter\Attribute\VoteOnAttribute;
use Shredio\Voter\EnhancedVoter;
use Shredio\Voter\EnhancedVoterServices;
use Shredio\Voter\Exception\InvalidEnhancedVoterException;
use Shredio\Voter\Metadata\VoterMetadataFactory;
use Shredio\Voter\Resolver\VoterParameterResolver;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;

final class VoterErrorsTest extends TestCase
{

	private VoterMetadataFactory $metadataFactory;

	private EnhancedVoterServices $services;

	protected function setUp(): void
	{
		$this->metadataFactory = new VoterMetadataFactory('vote');
		$this->services = new EnhancedVoterServices(new VoterParameterResolver(new AccessDecisionManager()));
	}

	public function testMissingAttributes(): void
	{
		$this->expectException(InvalidEnhancedVoterException::class);

		$this->process(new class($this->services) extends EnhancedVoter {});
	}

	public function testMissingAttributeByConvention(): void
	{
		$this->expectException(InvalidEnhancedVoterException::class);

		$this->process(new class($this->services) extends EnhancedVoter {
			#[VoteOnAttribute('subject')]
			protected function voteOnSubject(): bool
			{
				return true;
			}

			protected function voteOnRead(): bool
			{
				return true;
			}
		});
	}

	public function testInvalidReturnType(): void
	{
		$this->expectException(InvalidEnhancedVoterException::class);

		$this->process(new class($this->services) extends EnhancedVoter {
			#[VoteOnAttribute('subject')]
			protected function voteOnSubject(): string
			{
				return '';
			}
		});
	}

	private function process(EnhancedVoter $voter): void
	{
		$this->metadataFactory->create($voter::class);
	}

}
