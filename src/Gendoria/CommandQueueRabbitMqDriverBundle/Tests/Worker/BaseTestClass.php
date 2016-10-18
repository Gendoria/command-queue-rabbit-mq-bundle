<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Tests\Worker;

use Gendoria\CommandQueue\Command\CommandInterface;
use Gendoria\CommandQueue\CommandProcessor\CommandProcessorInterface;
use Gendoria\CommandQueue\ProcessorFactory\ProcessorFactoryInterface;
use Gendoria\CommandQueue\Serializer\SerializerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PHPUnit_Framework_MockObject_Generator;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Description of BaseRabbitMqTestClass
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class BaseTestClass extends PHPUnit_Framework_TestCase
{
    /**
     * 
     * @return $processorFactory PHPUnit_Framework_MockObject_MockObject|PHPUnit_Framework_MockObject_Generator|ProcessorFactoryInterface
     */
    protected function getProcessorFactory()
    {
        /* @var $processorFactory PHPUnit_Framework_MockObject_MockObject|PHPUnit_Framework_MockObject_Generator|ProcessorFactoryInterface */
        $processorFactory = $this->getMockBuilder(ProcessorFactoryInterface::class)->getMock();
        return $processorFactory;
    }

    /**
     * 
     * @return PHPUnit_Framework_MockObject_Generator|PHPUnit_Framework_MockObject_MockObject|CommandProcessorInterface
     */
    protected function getProcessor()
    {
        return $this->getMockBuilder(CommandProcessorInterface::class)->getMock();
    }

    /**
     * 
     * @return PHPUnit_Framework_MockObject_Generator|PHPUnit_Framework_MockObject_MockObject|CommandInterface
     */
    protected function getCommand()
    {
        return $this->getMockBuilder(CommandInterface::class)->getMock();
    }

    /**
     * @return PHPUnit_Framework_MockObject_Generator|PHPUnit_Framework_MockObject_MockObject|SerializerInterface
     */
    protected function getSerializer()
    {
        return $this->getMockBuilder(SerializerInterface::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * 
     * @return PHPUnit_Framework_MockObject_Generator|PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected function getEventDispatcher()
    {
        return $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
    }

    /**
     * 
     * @return PHPUnit_Framework_MockObject_Generator|PHPUnit_Framework_MockObject_MockObject|ProducerInterface
     */
    protected function getRescheduleProducer()
    {
        return $this->getMockBuilder(ProducerInterface::class)->getMock();
    }

}
