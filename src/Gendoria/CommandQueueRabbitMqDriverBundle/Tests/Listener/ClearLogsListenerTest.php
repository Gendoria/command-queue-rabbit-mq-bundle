<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Tests\Listener;

use Doctrine\ORM\EntityManager;
use Gendoria\CommandQueue\Command\CommandInterface;
use Gendoria\CommandQueue\CommandProcessor\CommandProcessorInterface;
use Gendoria\CommandQueue\Worker\WorkerInterface;
use Gendoria\CommandQueueBundle\Event\QueueBeforeTranslateEvent;
use Gendoria\CommandQueueBundle\Event\QueueEvents;
use Gendoria\CommandQueueBundle\Event\QueueProcessEvent;
use Gendoria\CommandQueueBundle\Event\QueueWorkerRunEvent;
use Gendoria\CommandQueueRabbitMqDriverBundle\Listener\ClearLogsListener;
use Gendoria\CommandQueueRabbitMqDriverBundle\Worker\RabbitMqWorker;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Logger;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

/**
 * Description of ClearEntityManagersListenerTest
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class ClearLogsListenerTest extends PHPUnit_Framework_TestCase
{
    public function testGetSubscribedEvents()
    {
        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $listener = new ClearLogsListener($logger);
        $this->assertEquals(array(
            QueueEvents::WORKER_RUN_BEFORE_TRANSLATE => 'beforeQueueRun',
            QueueEvents::WORKER_RUN_AFTER_PROCESS => 'afterQueueRun',
        ), $listener->getSubscribedEvents());
    }
    
    /**
     * 
     * @param QueueWorkerRunEvent $e
     * @param string $functionName
     * @param PHPUnit_Framework_MockObject_MockObject[]|EntityManager[] $managers
     * @param boolean $shouldRun
     * 
     * @dataProvider getEventData
     */
    public function testEvents(QueueWorkerRunEvent $e, $functionName, $shouldRun = true)
    {
        $logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $handler = $this->getMockBuilder(FingersCrossedHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $listener = new ClearLogsListener($logger);
        
        if ($shouldRun) {
            $logger->expects($this->once())
                ->method('getHandlers')
                ->will($this->returnValue(array($handler)));
            $handler->expects($this->once())
                ->method('clear');
        } else {
            $logger->expects($this->never())
                ->method('getHandlers');
        }
        
        $listener->$functionName($e);
    }
    
    public function getEventData()
    {
        $worker = $this->getMockBuilder(WorkerInterface::class)->getMock();
        $command = $this->getMockBuilder(CommandInterface::class)->getMock();
        $processor = $this->getMockBuilder(CommandProcessorInterface::class)->getMock();
        return array(
            array(new QueueBeforeTranslateEvent($worker, "ttt", RabbitMqWorker::SUBSYSTEM_NAME), "beforeQueueRun", true),
            array(new QueueBeforeTranslateEvent($worker, "ttt", "dummySubsystem"), "beforeQueueRun", false),
            
            array(new QueueProcessEvent($worker, $command, $processor, RabbitMqWorker::SUBSYSTEM_NAME), "afterQueueRun", true),
            array(new QueueProcessEvent($worker, $command, $processor, "dummySubsystem"), "afterQueueRun", false),
        );
    }
}
