<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Master configuration key.
     *
     * @var string
     */
    private $alias;

    /**
     * Class constructor.
     *
     * @param string $alias
     */
    public function __construct($alias)
    {
        $this->alias = $alias;
    }

    /**
     * Get config tree builder instance.
     *
     * {@inheritdoc}
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->alias);
        $rootNode->children()
            ->scalarNode('serializer')
                ->cannotBeEmpty()
                ->isRequired()
                ->validate()
                    ->ifTrue(function($v) {
                        return strpos($v, '@') !== 0;
                    })
                    ->thenInvalid('Serializer has to be in form "@service.id"')
                ->end()
            ->end()
            ->booleanNode('clear_entity_managers_listener_enabled')
                ->defaultTrue()
            ->end()
            ->arrayNode('drivers')
                ->prototype('array')
                    ->children()
                        ->scalarNode('rabbitmq_connection')
                            ->cannotBeEmpty()
                            ->isRequired()
                        ->end()
                        ->scalarNode('exchange_name')
                            ->cannotBeEmpty()
                            ->isRequired()
                        ->end()
                        ->scalarNode('consumer_name')
                            ->cannotBeEmpty()
                            ->isRequired()
                        ->end()
                        ->scalarNode('consumer_queue_name')
                            ->cannotBeEmpty()
                            ->isRequired()
                        ->end()
                        ->scalarNode('producer_name')
                            ->cannotBeEmpty()
                            ->isRequired()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
