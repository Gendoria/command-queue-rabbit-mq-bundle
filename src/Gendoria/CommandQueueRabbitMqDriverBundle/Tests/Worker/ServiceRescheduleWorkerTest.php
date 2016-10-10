<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Tests\Worker;

use Gendoria\CommandQueueRabbitMqDriverBundle\Worker\ServiceRescheduleWorker;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit_Framework_TestCase;

/**
 * Description of ServiceRescheduleWorkerTest
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class ServiceRescheduleWorkerTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $msg = $this
            ->getMockBuilder(AMQPMessage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $worker = new ServiceRescheduleWorker();
        $this->assertEquals(ConsumerInterface::MSG_REJECT_REQUEUE, $worker->execute($msg));
    }
}
