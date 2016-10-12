<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Tests\Worker;

use Exception;
use Gendoria\CommandQueue\Command\CommandInterface;
use Gendoria\CommandQueue\CommandProcessor\CommandProcessorInterface;
use Gendoria\CommandQueue\ProcessorFactory\Exception\ProcessorNotFoundException;
use Gendoria\CommandQueue\ProcessorFactory\ProcessorFactoryInterface;
use Gendoria\CommandQueue\Serializer\Exception\UnserializeErrorException;
use Gendoria\CommandQueue\Serializer\SerializedCommandData;
use Gendoria\CommandQueue\Serializer\SerializerInterface;
use Gendoria\CommandQueueBundle\Event\QueueEvents;
use Gendoria\CommandQueueBundle\Event\QueueProcessErrorEvent;
use Gendoria\CommandQueueRabbitMqDriverBundle\Worker\RabbitMqWorker;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit_Framework_MockObject_Generator;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Description of RabbitMqWorkerTest
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class RabbitMqWorkerTest extends PHPUnit_Framework_TestCase
{
    public function testCorrectPass()
    {
        $command = $this->getCommand();
        $processor = $this->getProcessor();
        $processorFactory = $this->getProcessorFactory();
        $serializer = $this->getSerializer();
        $eventDispatcher = $this->getEventDispatcher();
        $rescheduleProducer = $this->getRescheduleProducer();

        $msg = new AMQPMessage("Test", array(
            'application_headers' => new AMQPTable(
                array(
                'x-class-name' => get_class($command),
                )
            ),
        ));
        $msg->delivery_info = array(
            'channel' => null,
            'consumer_tag' => 't',
            'delivery_tag' => 't',
            'redelivered' => true,
            'exchange' => 't',
            'routing_key' => 't'
        );

        $eventDispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                array($this->equalTo(QueueEvents::WORKER_RUN_BEFORE_TRANSLATE)),
                array($this->equalTo(QueueEvents::WORKER_RUN_BEFORE_GET_PROCESSOR)),
                array($this->equalTo(QueueEvents::WORKER_RUN_BEFORE_PROCESS)),
                array($this->equalTo(QueueEvents::WORKER_RUN_AFTER_PROCESS))
            );

        $serializer->expects($this->once())->method('unserialize')
            ->with($this->equalTo(new SerializedCommandData("Test", get_class($command))))
            ->will($this->returnValue($command));

        $processorFactory->expects($this->once())
            ->method('getProcessor')
            ->will($this->returnValue($processor));
        
        $processor->expects($this->once())
            ->method('process')
            ->with($command)
            ;
        
        $worker = new RabbitMqWorker($eventDispatcher, $processorFactory, $serializer, $rescheduleProducer);

        $worker->execute($msg);
    }
    
    public function testTranslateErrorNoHeaders()
    {
        $processorFactory = $this->getProcessorFactory();
        $serializer = $this->getSerializer();
        $eventDispatcher = $this->getEventDispatcher();
        $rescheduleProducer = $this->getRescheduleProducer();

        $msg = new AMQPMessage("Test", array(
            'application_headers' => new AMQPTable(
                array(
                )
            ),
        ));
        $msg->delivery_info = array(
            'channel' => null,
            'consumer_tag' => 't',
            'delivery_tag' => 't',
            'redelivered' => true,
            'exchange' => 't',
            'routing_key' => 't'
        );

        $eventDispatcher->expects($this->exactly(1))
            ->method('dispatch')
            ->withConsecutive(
                array($this->equalTo(QueueEvents::WORKER_RUN_BEFORE_TRANSLATE))
            );

        $serializer->expects($this->never())->method('unserialize');
        $rescheduleProducer
            ->expects($this->once())
            ->method('publish');

        $worker = new RabbitMqWorker($eventDispatcher, $processorFactory, $serializer, $rescheduleProducer);

        $this->assertEquals(ConsumerInterface::MSG_REJECT, $worker->execute($msg));
    }
    
    public function testTranslateErrorSerializer()
    {
        $command = $this->getCommand();
        $processorFactory = $this->getProcessorFactory();
        $serializer = $this->getSerializer();
        $eventDispatcher = $this->getEventDispatcher();
        $rescheduleProducer = $this->getRescheduleProducer();

        $msg = new AMQPMessage("Test", array(
            'application_headers' => new AMQPTable(
                array(
                    'x-class-name' => get_class($command),
                )
            ),
        ));
        $msg->delivery_info = array(
            'channel' => null,
            'consumer_tag' => 't',
            'delivery_tag' => 't',
            'redelivered' => true,
            'exchange' => 't',
            'routing_key' => 't'
        );

        $eventDispatcher->expects($this->exactly(1))
            ->method('dispatch')
            ->withConsecutive(
                array($this->equalTo(QueueEvents::WORKER_RUN_BEFORE_TRANSLATE))
            );

        $serializer->expects($this->once())->method('unserialize')
            ->with($this->equalTo(new SerializedCommandData("Test", get_class($command))))
            ->will($this->throwException(new UnserializeErrorException("Test")))
            ;
        $rescheduleProducer
            ->expects($this->once())
            ->method('publish');

        $worker = new RabbitMqWorker($eventDispatcher, $processorFactory, $serializer, $rescheduleProducer);

        $this->assertEquals(ConsumerInterface::MSG_REJECT, $worker->execute($msg));
    }
    
    public function testGetProcessorError()
    {
        $command = $this->getCommand();
        $processor = $this->getProcessor();
        $processorFactory = $this->getProcessorFactory();
        $serializer = $this->getSerializer();
        $eventDispatcher = $this->getEventDispatcher();
        $rescheduleProducer = $this->getRescheduleProducer();

        $msg = new AMQPMessage("Test", array(
            'application_headers' => new AMQPTable(
                array(
                'x-class-name' => get_class($command),
                )
            ),
        ));
        $msg->delivery_info = array(
            'channel' => null,
            'consumer_tag' => 't',
            'delivery_tag' => 't',
            'redelivered' => true,
            'exchange' => 't',
            'routing_key' => 't'
        );

        $eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                array($this->equalTo(QueueEvents::WORKER_RUN_BEFORE_TRANSLATE)),
                array($this->equalTo(QueueEvents::WORKER_RUN_BEFORE_GET_PROCESSOR))
            );

        $serializer->expects($this->once())->method('unserialize')
            ->with($this->equalTo(new SerializedCommandData("Test", get_class($command))))
            ->will($this->returnValue($command));

        $processorFactory->expects($this->once())
            ->method('getProcessor')
            ->will($this->throwException(new ProcessorNotFoundException("ProcessorNotFound")));
        
        $processor->expects($this->never())
            ->method('process')
            ->with($command)
            ;
        
        $rescheduleProducer
            ->expects($this->once())
            ->method('publish');        
        
        $worker = new RabbitMqWorker($eventDispatcher, $processorFactory, $serializer, $rescheduleProducer);

        $this->assertEquals(ConsumerInterface::MSG_REJECT, $worker->execute($msg));
    }
    
    public function testProcessError()
    {
        $command = $this->getCommand();
        $processor = $this->getProcessor();
        $processorFactory = $this->getProcessorFactory();
        $serializer = $this->getSerializer();
        $eventDispatcher = $this->getEventDispatcher();
        $rescheduleProducer = $this->getRescheduleProducer();

        $msg = new AMQPMessage("Test", array(
            'application_headers' => new AMQPTable(
                array(
                'x-class-name' => get_class($command),
                )
            ),
        ));
        $msg->delivery_info = array(
            'channel' => null,
            'consumer_tag' => 't',
            'delivery_tag' => 't',
            'redelivered' => true,
            'exchange' => 't',
            'routing_key' => 't'
        );

        $eventDispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                array($this->equalTo(QueueEvents::WORKER_RUN_BEFORE_TRANSLATE)),
                array($this->equalTo(QueueEvents::WORKER_RUN_BEFORE_GET_PROCESSOR)),
                array($this->equalTo(QueueEvents::WORKER_RUN_BEFORE_PROCESS)),
                array($this->equalTo(QueueEvents::WORKER_RUN_PROCESSOR_ERROR), $this->callback(function(QueueProcessErrorEvent $event) {
                    return $event->getException()->getMessage() == "DummyException";
                }))
            );

        $serializer->expects($this->once())->method('unserialize')
            ->with($this->equalTo(new SerializedCommandData("Test", get_class($command))))
            ->will($this->returnValue($command));

        $processorFactory->expects($this->once())
            ->method('getProcessor')
            ->will($this->returnValue($processor));
        
        $processor->expects($this->once())
            ->method('process')
            ->with($command)
            ->will($this->throwException(new Exception("DummyException")))
            ;
        
        $rescheduleProducer
            ->expects($this->once())
            ->method('publish');        
        
        $worker = new RabbitMqWorker($eventDispatcher, $processorFactory, $serializer, $rescheduleProducer);

        $this->assertEquals(ConsumerInterface::MSG_REJECT, $worker->execute($msg));
    }
    
    public function testErrorNoRepublish1()
    {
        $command = $this->getCommand();
        $processorFactory = $this->getProcessorFactory();
        $serializer = $this->getSerializer();
        $eventDispatcher = $this->getEventDispatcher();
        $rescheduleProducer = $this->getRescheduleProducer();

        $msg = new AMQPMessage("Test", array(
            'application_headers' => new AMQPTable(
                array(
                    'x-death' => array(array('count' => 10)),
                )
            ),
        ));
        $msg->delivery_info = array(
            'channel' => null,
            'consumer_tag' => 't',
            'delivery_tag' => 't',
            'redelivered' => true,
            'exchange' => 't',
            'routing_key' => 't'
        );

        $eventDispatcher->expects($this->exactly(1))
            ->method('dispatch')
            ->withConsecutive(
                array($this->equalTo(QueueEvents::WORKER_RUN_BEFORE_TRANSLATE))
            );

        $serializer->expects($this->never())->method('deserialize')
            ->with("Test", get_class($command), 'json')
            ->will($this->returnValue($command));
        $rescheduleProducer
            ->expects($this->never())
            ->method('publish');

        $worker = new RabbitMqWorker($eventDispatcher, $processorFactory, $serializer, $rescheduleProducer);

        $this->assertEquals(ConsumerInterface::MSG_REJECT, $worker->execute($msg));
    }    

    public function testErrorNoRepublish2()
    {
        $command = $this->getCommand();
        $processorFactory = $this->getProcessorFactory();
        $serializer = $this->getSerializer();
        $eventDispatcher = $this->getEventDispatcher();
        $rescheduleProducer = $this->getRescheduleProducer();

        $msg = new AMQPMessage("Test", array(
            'application_headers' => new AMQPTable(
                array(
                    'x-death' => array_fill(0, 10, "ttt"),
                )
            ),
        ));
        $msg->delivery_info = array(
            'channel' => null,
            'consumer_tag' => 't',
            'delivery_tag' => 't',
            'redelivered' => true,
            'exchange' => 't',
            'routing_key' => 't'
        );

        $eventDispatcher->expects($this->exactly(1))
            ->method('dispatch')
            ->withConsecutive(
                array($this->equalTo(QueueEvents::WORKER_RUN_BEFORE_TRANSLATE))
            );

        $serializer->expects($this->never())->method('deserialize')
            ->with("Test", get_class($command), 'json')
            ->will($this->returnValue($command));
        $rescheduleProducer
            ->expects($this->never())
            ->method('publish');

        $worker = new RabbitMqWorker($eventDispatcher, $processorFactory, $serializer, $rescheduleProducer);

        $this->assertEquals(ConsumerInterface::MSG_REJECT, $worker->execute($msg));
    }
    
    /**
     * 
     * @return $processorFactory PHPUnit_Framework_MockObject_MockObject|PHPUnit_Framework_MockObject_Generator|ProcessorFactoryInterface
     */
    private function getProcessorFactory()
    {
        /* @var $processorFactory PHPUnit_Framework_MockObject_MockObject|PHPUnit_Framework_MockObject_Generator|ProcessorFactoryInterface */
        $processorFactory = $this->getMockBuilder(ProcessorFactoryInterface::class)->getMock();
        return $processorFactory;
    }

    /**
     * 
     * @return PHPUnit_Framework_MockObject_Generator|PHPUnit_Framework_MockObject_MockObject|CommandProcessorInterface
     */
    private function getProcessor()
    {
        return $this->getMockBuilder(CommandProcessorInterface::class)->getMock();
    }

    /**
     * 
     * @return PHPUnit_Framework_MockObject_Generator|PHPUnit_Framework_MockObject_MockObject|CommandInterface
     */
    private function getCommand()
    {
        return $this->getMockBuilder(CommandInterface::class)->getMock();
    }

    /**
     * @return PHPUnit_Framework_MockObject_Generator|PHPUnit_Framework_MockObject_MockObject|SerializerInterface
     */
    private function getSerializer()
    {
        return $this->getMockBuilder(SerializerInterface::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * 
     * @return PHPUnit_Framework_MockObject_Generator|PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    private function getEventDispatcher()
    {
        return $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
    }

    /**
     * 
     * @return PHPUnit_Framework_MockObject_Generator|PHPUnit_Framework_MockObject_MockObject|ProducerInterface
     */
    private function getRescheduleProducer()
    {
        return $this->getMockBuilder(ProducerInterface::class)->getMock();
    }

}
