<?php declare(strict_types = 1);

namespace Shredio\Voter\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class VoteOnAttribute extends VoteAttribute
{

}
