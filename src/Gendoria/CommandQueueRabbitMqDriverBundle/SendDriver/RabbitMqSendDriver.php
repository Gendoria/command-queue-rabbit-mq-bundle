<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\SendDriver;

use Isobar\CommandQueue\SendDriver\SendDriverInterface;
use Isobar\CommandQueue\Command\CommandInterface;
use JMS\Serializer\Serializer;
use OldSound\RabbitMqBundle\RabbitMq\Producer;

/**
 * Command queue send driver using RabbitMQ server.
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class RabbitMqSendDriver implements SendDriverInterface
{
    /**
     * Serializer instance.
     *
     * @var Serializer
     */
    private $serializer;

    /**
     * Producer instance.
     *
     * @var Producer
     */
    private $producer;

    /**
     * Class constructor.
     *
     * @param Serializer $serializer
     * @param Producer   $producer
     */
    public function __construct(
        Serializer $serializer,
        Producer $producer
    ) {
        $this->serializer = $serializer;
        $this->producer = $producer;
    }

    /**
     * Send command using RabbitMQ server.
     *
     * {@inheritdoc}
     */
    public function send(CommandInterface $command)
    {
        $this->producer->publish(
            $this->serializer->serialize($command, 'json'),
            get_class($command),
            array(
                'application_headers' => array(
                    'x-class-name' => array(
                        'S',
                        get_class($command),
                    ),
                ),
            )
        );
    }
}
