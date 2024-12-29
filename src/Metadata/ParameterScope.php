<?php

namespace Shredio\Voter\Metadata;

enum ParameterScope: string
{

	case Attribute = 'attribute';
	case Subject = 'subject';
	case Token = 'token';
	case User = 'user';
	case CustomUser = 'custom-user';
	case AccessDecisionManager = 'access-decision-manager';
	case Custom = 'custom';

}
