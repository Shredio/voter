<?php declare(strict_types = 1);

namespace Shredio\Voter\Bundle;

use Shredio\Voter\EnhancedVoter;
use Shredio\Voter\EnhancedVoterServices;
use Shredio\Voter\Metadata\VoterMetadataFactory;
use Shredio\Voter\Resolver\VoterParameterResolver;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class VoterBundle extends AbstractBundle
{

	/**
	 * @param mixed[] $config
	 */
	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
	{
		$container->services()
			->set(VoterParameterResolver::class)
			->autowire();

		$container->services()
			->set(EnhancedVoterServices::class)
			->autowire();

		$container->services()
			->set(VoterMetadataFactory::class)
			->args([$config['name_convention_for_methods'] ?? null]);

		$builder->registerForAutoconfiguration(EnhancedVoter::class)
			->addTag('voter.enhance');
	}

	public function build(ContainerBuilder $container): void
	{
		$container->addCompilerPass(new VoterCompilerPass());
	}

	public function configure(DefinitionConfigurator $definition): void
	{
		$definition->rootNode() // @phpstan-ignore-line
			->children()
				->booleanNode('refresh')->defaultTrue()->end()
				->stringNode('name_convention_for_methods')->defaultNull()->end()
			->end();
	}

}
