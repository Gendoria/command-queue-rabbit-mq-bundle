<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Tests\Listener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Gendoria\CommandQueue\Command\CommandInterface;
use Gendoria\CommandQueue\CommandProcessor\CommandProcessorInterface;
use Gendoria\CommandQueue\Worker\WorkerInterface;
use Gendoria\CommandQueueBundle\Event\QueueBeforeTranslateEvent;
use Gendoria\CommandQueueBundle\Event\QueueEvents;
use Gendoria\CommandQueueBundle\Event\QueueProcessEvent;
use Gendoria\CommandQueueBundle\Event\QueueWorkerRunEvent;
use Gendoria\CommandQueueRabbitMqDriverBundle\Listener\ClearEntityManagersListener;
use Gendoria\CommandQueueRabbitMqDriverBundle\Worker\RabbitMqWorker;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

/**
 * Description of ClearEntityManagersListenerTest
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class ClearEntityManagersListenerTest extends PHPUnit_Framework_TestCase
{
    public function testGetSubscribedEvents()
    {
        $managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->getMock();
        $listener = new ClearEntityManagersListener($managerRegistry);
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
    public function testEvents(QueueWorkerRunEvent $e, $functionName, array $managers, $shouldRun = true)
    {
        $managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->getMock();
        $connection = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $listener = new ClearEntityManagersListener($managerRegistry);
        
        if ($shouldRun) {
            $managerRegistry->expects($this->once())
                ->method('getManagers')
                ->will($this->returnValue($managers));
            $cnt = 0;
            foreach ($managers as $manager) {
                if (!$manager instanceof EntityManager) {
                    continue;
                }
                $cnt++;
                $manager->expects($this->once())
                    ->method('clear')
                    ;
                $manager->expects($this->exactly(2))
                    ->method('getConnection')
                    ->will($this->returnValue($connection));
                $manager->expects($this->once())->method('close');
            }
            $connection->expects($this->exactly($cnt))
                ->method('ping')
                ->will($this->returnValue(false));
            $connection->expects($this->exactly($cnt))
                ->method('close');
        } else {
            $managerRegistry->expects($this->never())
                ->method('getManagers');
        }
        
        $listener->$functionName($e);
    }
    
    public function getEventData()
    {
        $worker = $this->getMockBuilder(WorkerInterface::class)->getMock();
        $command = $this->getMockBuilder(CommandInterface::class)->getMock();
        $processor = $this->getMockBuilder(CommandProcessorInterface::class)->getMock();
        return array(
            array(new QueueBeforeTranslateEvent($worker, "ttt", RabbitMqWorker::SUBSYSTEM_NAME), "beforeQueueRun", $this->getEventDataCreateManagers(0), true),
            array(new QueueBeforeTranslateEvent($worker, "ttt", RabbitMqWorker::SUBSYSTEM_NAME), "beforeQueueRun", $this->getEventDataCreateManagers(4), true),
            array(new QueueBeforeTranslateEvent($worker, "ttt", "dummySubsystem"), "beforeQueueRun", $this->getEventDataCreateManagers(), false),
            
            array(new QueueProcessEvent($worker, $command, $processor, RabbitMqWorker::SUBSYSTEM_NAME), "afterQueueRun", $this->getEventDataCreateManagers(0), true),
            array(new QueueProcessEvent($worker, $command, $processor, RabbitMqWorker::SUBSYSTEM_NAME), "afterQueueRun", $this->getEventDataCreateManagers(4), true),
            array(new QueueProcessEvent($worker, $command, $processor, "dummySubsystem"), "afterQueueRun", $this->getEventDataCreateManagers(), false),
        );
    }
    
    private function getEventDataCreateManagers($count = 0)
    {
        $return = array();
        for ($k=0; $k < $count; $k++) {
            if ($k % 3 === 0) {
                $return[] = $this->getMockBuilder(ObjectManager::class)
                    ->disableOriginalConstructor()
                    ->getMock();
            } else {
                $return[] = $this->getMockBuilder(EntityManager::class)
                    ->disableOriginalConstructor()
                    ->getMock();
            }
        }
        return $return;
    }
}
