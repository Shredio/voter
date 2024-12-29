<?php declare(strict_types = 1);

namespace Shredio\Voter;

use Shredio\Voter\Metadata\VoterMetadataFactory;
use Shredio\Voter\Resolver\VoterParameterResolver;

final readonly class EnhancedVoterServices
{

	public VoterMetadataFactory $metadataFactory;

	public function __construct(
		public VoterParameterResolver $voterParameterResolver,
		?VoterMetadataFactory $metadataFactory = null,
	)
	{
		$this->metadataFactory = $metadataFactory ?? new VoterMetadataFactory();
	}

}
