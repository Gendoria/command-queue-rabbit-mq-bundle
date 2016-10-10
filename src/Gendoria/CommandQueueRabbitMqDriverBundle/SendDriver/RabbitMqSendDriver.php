<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\SendDriver;

use Gendoria\CommandQueue\Command\CommandInterface;
use Gendoria\CommandQueue\SendDriver\SendDriverInterface;
use Gendoria\CommandQueue\Serializer\SerializerInterface;
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
     * @var SerializerInterface
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
     * @param SerializerInterface $serializer
     * @param ProducerInterface   $producer
     */
    public function __construct(SerializerInterface $serializer, ProducerInterface $producer)
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
        $serialized = $this->serializer->serialize($command);
        $this->producer->publish(
            $serialized->getSerializedCommand(),
            $serialized->getCommandClass(),
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
