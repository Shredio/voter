<?php declare(strict_types = 1);

namespace Tests;

use Symfony\Component\Security\Core\User\UserInterface;

final readonly class StubUser implements UserInterface
{

	public function __construct(
		private string $id = '1',
	)
	{
	}

	public function getRoles(): array
	{
		return ['ROLE_USER'];
	}

	public function eraseCredentials(): void
	{
	}

	public function getUserIdentifier(): string
	{
		return $this->id;
	}

}
