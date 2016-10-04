<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Worker;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * "Dummy" class needed to create RabbitMQ reschedule queue.
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class ServiceRescheduleWorker implements ConsumerInterface
{
    /**
     * Class constructor - does nothing.
     */
    public function __construct()
    {
    }

    /**
     * Process message, sending it back to queue.
     *
     * @param AMQPMessage $msg
     *
     * @return int Return self::MSG_REJECT_REQUEUE value to enforce message requeue.
     */
    public function execute(AMQPMessage $msg)
    {
        return self::MSG_REJECT_REQUEUE;
    }
}
