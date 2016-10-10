<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Tests\SendDriver;

use Gendoria\CommandQueue\Command\CommandInterface;
use Gendoria\CommandQueue\Serializer\SerializedCommandData;
use Gendoria\CommandQueue\Serializer\SerializerInterface;
use Gendoria\CommandQueueRabbitMqDriverBundle\SendDriver\RabbitMqSendDriver;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PHPUnit_Framework_TestCase;

/**
 * Description of RabbitMqSendDriverTest
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class RabbitMqSendDriverTest extends PHPUnit_Framework_TestCase
{
    public function testSend()
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $producer = $this->getMockBuilder(ProducerInterface::class)->getMock();
        $sendDriver = new RabbitMqSendDriver($serializer, $producer);
        $command = $this->getMockBuilder(CommandInterface::class)->getMock();
        $serializer->expects($this->once())
            ->method('serialize')
            ->with($command)
            ->will($this->returnValue(new SerializedCommandData('a', 'b')));
        
        $producer->expects($this->once())
            ->method('publish')
            ->with('a',
                'b',
                array(
                'application_headers' => array(
                    'x-class-name' => array(
                        'S',
                        get_class($command),
                    ),
                ),
            ))
            ;
        
        $sendDriver->send($command);
    }
}
