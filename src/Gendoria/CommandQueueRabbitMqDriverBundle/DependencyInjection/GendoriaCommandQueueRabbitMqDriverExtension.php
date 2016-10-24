<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\DependencyInjection;

use Gendoria\CommandQueueBundle\DependencyInjection\Pass\WorkerRunnersPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class GendoriaCommandQueueRabbitMqDriverExtension extends Extension implements PrependExtensionInterface
{
    /**
     * Get extension alias.
     *
     * @return string
     */
    public function getAlias()
    {
        return 'gendoria_command_queue_rabbit_mq_driver';
    }

    /**
     * Load extension.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration($this->getAlias());
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->loadDrivers($config, $container);
        $container->removeDefinition('gendoria_command_queue_rabbit_mq_driver.send_driver');
        $container->removeDefinition('gendoria_command_queue_rabbit_mq_driver.external_data_worker');
    }
    
    private function loadDrivers(array $config, ContainerBuilder $container)
    {
        $serializer = substr($config['serializer'], 1);
        $workerRunner = $container->getDefinition('gendoria_command_queue_rabbit_mq_driver.worker_runner');
        foreach ($config['drivers'] as $driverId => $driver) {
            $producerName = sprintf('old_sound_rabbit_mq.%s_producer', $driver['producer_name']);
            $delayedProducerName = sprintf('old_sound_rabbit_mq.%s_reschedule_delayed_producer', $driver['producer_name']);
            
            $newDriver = clone $container->getDefinition('gendoria_command_queue_rabbit_mq_driver.send_driver');
            $newDriver->replaceArgument(0, new Reference($serializer));
            $newDriver->replaceArgument(1, new Reference($producerName));
            $container->setDefinition('gendoria_command_queue_rabbit_mq_driver.driver.'.$driverId, $newDriver);

            $newWorker = clone $container->getDefinition('gendoria_command_queue_rabbit_mq_driver.external_data_worker');
            $newWorker->replaceArgument(2, new Reference($serializer));
            $newWorker->replaceArgument(3, new Reference($delayedProducerName));
            $workerRunner->addTag(WorkerRunnersPass::WORKER_RUNNER_TAG, array('name' => 'rmq.'.$driverId, 'options' => json_encode($driver)));
            $workerRunner->addTag(WorkerRunnersPass::WORKER_RUNNER_TAG, array('name' => 'rmq.'.$driverId.'.reschedule', 'options' => json_encode(array_merge($driver, array('reschedule' => true)))));
            $container->setDefinition('gendoria_command_queue_rabbit_mq_driver.worker.'.$driverId, $newWorker);
        }
    }

    /**
     * Prepend configuration.
     *
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        $this->prependRabbitMq($container);
    }
    
    /**
     * Prepend configuration to OldSoundRabbitMQBundle.
     *
     * @param ContainerBuilder $container
     */
    private function prependRabbitMq(ContainerBuilder $container) {
        $configs = $container->getExtensionConfig($this->getAlias());
        $currentConfig = $this->processConfiguration(new Configuration($this->getAlias()), $configs);

        $rabbitMQConfig = array(
            'consumers' => array(),
            'producers' => array(),
        );

        foreach ($currentConfig['drivers'] as $driverId => $driver) {
            $exchangeOptions = array(
                'name' => $driver['exchange_name'],
                'type' => 'topic',
            );

            $rabbitMQConfig['producers'][$driver['producer_name']] = array(
                'connection' => $driver['rabbitmq_connection'],
                'exchange_options' => $exchangeOptions,
            );

            $rabbitMQConfig['consumers'][$driver['consumer_name']] = array(
                'connection' => $driver['rabbitmq_connection'],
                'exchange_options' => $exchangeOptions,
                'queue_options' => array(
                    'name' => $driver['consumer_queue_name'],
                    'durable' => true,
                    'auto_delete' => false,
                    'routing_keys' => array('*'),
                ),
                'qos_options' => array(
                    'prefetch_size' => 0,
                    'prefetch_count' => 1,
                    'global' => false,
                ),
                'callback' => 'gendoria_command_queue_rabbit_mq_driver.worker.'.$driverId,
            );

            $rabbitMQConfig['producers'][$driver['producer_name'].'_reschedule_delayed'] = array(
                'connection' => $driver['rabbitmq_connection'],
                'exchange_options' => array_merge($exchangeOptions, array(
                    'name' => $driver['exchange_name'].'-reschedule-delayed',
                )),
            );

            $rabbitMQConfig['consumers'][$driver['consumer_name'].'_reschedule_delayed'] = array(
                'connection' => $driver['rabbitmq_connection'],
                'exchange_options' => $rabbitMQConfig['producers'][$driver['producer_name'].'_reschedule_delayed']['exchange_options'],
                'queue_options' => array(
                    'name' => $driver['consumer_queue_name'].'-reschedule-delayed',
                    'durable' => true,
                    'auto_delete' => false,
                    'routing_keys' => array('*'),
                    'arguments' => array(
                        'x-dead-letter-exchange' => array('S', $exchangeOptions['name']),
                        'x-message-ttl' => array('I', 600000), //10 minutes
                    ),
                ),
                'callback' => 'gendoria_command_queue_rabbit_mq_driver.services_reschedule_worker',
            );
        }

        $container->prependExtensionConfig('old_sound_rabbit_mq', $rabbitMQConfig);
    }    
}
