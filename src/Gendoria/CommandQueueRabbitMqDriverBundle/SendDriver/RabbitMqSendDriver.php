<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\SendDriver;

use Gendoria\CommandQueue\Command\CommandInterface;
use Gendoria\CommandQueue\SendDriver\SendDriverInterface;
use JMS\Serializer\Serializer;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

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
     * @var ProducerInterface
     */
    private $producer;

    /**
     * Class constructor.
     *
     * @param Serializer        $serializer
     * @param ProducerInterface $producer
     */
    public function __construct(Serializer $serializer, ProducerInterface $producer)
    {
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
