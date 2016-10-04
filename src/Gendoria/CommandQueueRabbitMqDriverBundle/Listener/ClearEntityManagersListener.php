<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Listener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Isobar\Bundle\CommandQueueBundle\Event\QueueEvents;
use Isobar\Bundle\CommandQueueBundle\Event\QueueWorkerRunEvent;
use Gendoria\CommandQueueRabbitMqDriverBundle\Worker\RabbitMqWorker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Clears entity managers before and after processor run.
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class ClearEntityManagersListener implements EventSubscriberInterface
{
    /**
     * Doctrine manager registry.
     *
     * @var ManagerRegistry
     */
    private $managerRegistry;
    
    /**
     * Class constructor.
     * 
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Get subscribed events.
     * 
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            QueueEvents::WORKER_RUN_BEFORE => 'beforeQueueRun',
            QueueEvents::WORKER_RUN_AFTER => 'afterQueueRun',
        );
    }

    /**
     * Invoke before queue run.
     * 
     * @param QueueWorkerRunEvent $e
     */
    public function beforeQueueRun(QueueWorkerRunEvent $e)
    {
        if ($e->getSubsystem() !== RabbitMqWorker::SUBSYSTEM_NAME) {
            return;
        }
        $this->clearEntityManagers();
    }

    /**
     * Invoke after queue run.
     * 
     * @param QueueWorkerRunEvent $e
     */
    public function afterQueueRun(QueueWorkerRunEvent $e)
    {
        if ($e->getSubsystem() !== RabbitMqWorker::SUBSYSTEM_NAME) {
            return;
        }
        $this->clearEntityManagers();
    }

    /**
     * Clear entity managers and close broken managers.
     */
    private function clearEntityManagers()
    {
        foreach ($this->managerRegistry->getManagers() as /* @var $manager EntityManager */ $manager) {
            $manager->clear();
            if (!$manager->getConnection()->ping()) {
                $manager->getConnection()->close();
                $manager->close();
            }
        }
    }
}
