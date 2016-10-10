<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Tests\DependencyInjection;

use Gendoria\CommandQueueRabbitMqDriverBundle\DependencyInjection\GendoriaCommandQueueRabbitMqDriverExtension;
use PHPUnit_Framework_TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Description of GendoriaCommandQueueRabbitMqDriverExtension
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class GendoriaCommandQueueRabbitMqDriverExtensionTest extends PHPUnit_Framework_TestCase
{

    public function testLoad()
    {
        $container = new ContainerBuilder();
        $extension = new GendoriaCommandQueueRabbitMqDriverExtension();
        $config = array(
            'serializer' => '@gendoria_command_queue.serializer.symfony',
            'drivers' => array(
                'default' => array(
                    'rabbitmq_connection' => 'default',
                    'exchange_name' => 'default',
                    'consumer_name' => 'default',
                    'consumer_queue_name' => 'default',
                    'producer_name' => 'default',
                ),
            ),
        );
        $extension->load(array($config), $container);
        $this->assertFalse($container->hasDefinition('gendoria_command_queue_rabbit_mq_driver.send_driver'));
        $this->assertFalse($container->hasDefinition('gendoria_command_queue_rabbit_mq_driver.external_data_worker'));
        $this->assertTrue($container->hasDefinition('gendoria_command_queue_rabbit_mq_driver.driver.default'));
        $this->assertTrue($container->hasDefinition('gendoria_command_queue_rabbit_mq_driver.worker.default'));
        $defaultDriver = $container->getDefinition('gendoria_command_queue_rabbit_mq_driver.driver.default');
        $this->assertEquals(new Reference('gendoria_command_queue.serializer.symfony'), $defaultDriver->getArgument(0));
        $this->assertEquals(new Reference('old_sound_rabbit_mq.default_producer'), $defaultDriver->getArgument(1));
        $defaultWorker = $container->getDefinition('gendoria_command_queue_rabbit_mq_driver.worker.default');
        $this->assertEquals(new Reference('gendoria_command_queue.serializer.symfony'), $defaultWorker->getArgument(2));
        $this->assertEquals(new Reference('old_sound_rabbit_mq.default_reschedule_delayed_producer'), $defaultWorker->getArgument(3));
    }

    public function testPrepend()
    {
        $container = new ContainerBuilder();
        $extension = new GendoriaCommandQueueRabbitMqDriverExtension();
        $config = array(
            'serializer' => '@gendoria_command_queue.serializer.symfony',
            'drivers' => array(
                'default' => array(
                    'rabbitmq_connection' => 'default',
                    'exchange_name' => 'default',
                    'consumer_name' => 'default',
                    'consumer_queue_name' => 'queue_default',
                    'producer_name' => 'default',
                ),
            ),
        );
        $container->prependExtensionConfig('gendoria_command_queue_rabbit_mq_driver', $config);
        $extension->prepend($container);
        $rabbitMqConfig = $container->getExtensionConfig('old_sound_rabbit_mq');
        $this->assertArrayHasKey('consumers', $rabbitMqConfig[0]);
        $this->assertArrayHasKey('producers', $rabbitMqConfig[0]);
        $this->assertArrayHasKey('default', $rabbitMqConfig[0]['consumers']);
        $this->assertArrayHasKey('default_reschedule_delayed', $rabbitMqConfig[0]['consumers']);
        $this->assertArrayHasKey('default', $rabbitMqConfig[0]['producers']);
        $this->assertArrayHasKey('default_reschedule_delayed', $rabbitMqConfig[0]['producers']);

        $this->assertEquals(array(
            'connection' => 'default',
            'exchange_options' => array(
                'name' => 'default',
                'type' => 'topic',
            ),
            'queue_options' => array(
                'name' => 'queue_default',
                'durable' => true,
                'auto_delete' => false,
                'routing_keys' => array('*'),
            ),
            'qos_options' => array(
                'prefetch_size' => 0,
                'prefetch_count' => 1,
                'global' => false,
            ),
            'callback' => 'gendoria_command_queue_rabbit_mq_driver.worker.default'
            ), $rabbitMqConfig[0]['consumers']['default']);

        $this->assertEquals(array(
            'connection' => 'default',
            'exchange_options' => array(
                'name' => 'default-reschedule-delayed',
                'type' => 'topic',
            ),
            'queue_options' => array(
                'name' => 'queue_default-reschedule-delayed',
                'durable' => true,
                'auto_delete' => false,
                'routing_keys' => array('*'),
                'arguments' => array(
                    'x-dead-letter-exchange' => array('S', 'default'),
                    'x-message-ttl' => array('I', 600000), //10 minutes
                ),
            ),
            'callback' => 'gendoria_command_queue_rabbit_mq_driver.services_reschedule_worker',
            ), $rabbitMqConfig[0]['consumers']['default_reschedule_delayed']
        );

        $this->assertEquals(array(
            'connection' => 'default',
            'exchange_options' => array(
                'name' => 'default',
                'type' => 'topic',
            ),
            ), $rabbitMqConfig[0]['producers']['default']);
        $this->assertEquals(array(
            'connection' => 'default',
            'exchange_options' => array(
                'name' => 'default-reschedule-delayed',
                'type' => 'topic',
            ),
            ), $rabbitMqConfig[0]['producers']['default_reschedule_delayed']);
    }

}
