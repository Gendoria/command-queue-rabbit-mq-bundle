<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Tests\SendDriver;

use Gendoria\CommandQueue\Command\CommandInterface;
use Gendoria\CommandQueueRabbitMqDriverBundle\SendDriver\RabbitMqSendDriver;
use JMS\Serializer\SerializerBuilder;
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
        $serializer = SerializerBuilder::create()->build();
        $producer = $this->getMockBuilder(ProducerInterface::class)->getMock();
        $sendDriver = new RabbitMqSendDriver($serializer, $producer);
        $command = $this->getMockBuilder(CommandInterface::class)->getMock();
        
        $producer->expects($this->once())
            ->method('publish')
            ->with($serializer->serialize($command, 'json'),
                get_class($command),
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
