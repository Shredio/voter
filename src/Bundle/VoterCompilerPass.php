<?php declare(strict_types = 1);

namespace Shredio\Voter\Bundle;

use LogicException;
use Shredio\Voter\Metadata\VoterMetadataFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final readonly class VoterCompilerPass implements CompilerPassInterface
{

	public function process(ContainerBuilder $container): void
	{
		$config = $container->getExtensionConfig('voter')[0] ?? [];
		$refresh = $container->getParameterBag()->resolveValue($config['refresh'] ?? false);

		if ($refresh) {
			return;
		}

		$metadataFactory = new VoterMetadataFactory($config['name_convention_for_methods'] ?? null);

		foreach ($container->findTaggedServiceIds('voter.enhance') as $serviceId => $tags) {
			$definition = $container->getDefinition($serviceId);
			$className = $definition->getClass();

			if ($className === null) {
				throw new LogicException(sprintf('Cannot get class of %s voter to enhance.', $serviceId));
			}

			if (!class_exists($className)) {
				throw new LogicException(sprintf('Voter class %s does not exist.', $className));
			}

			$definition->addMethodCall('setMetadata', [$metadataFactory->createDefinition($className)]);
		}
	}

}
