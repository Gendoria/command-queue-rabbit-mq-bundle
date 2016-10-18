<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Tests\Worker;

use Gendoria\CommandQueueRabbitMqDriverBundle\Worker\RabbitMqWorkerRunner;
use InvalidArgumentException;
use OldSound\RabbitMqBundle\Command\ConsumerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Description of RabbitMqWorkerRunnerTest
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class RabbitMqWorkerRunnerTest extends BaseTestClass
{
    public function testRun()
    {
        $eventDispatcher = $this->getEventDispatcher();
        $dummyRabbitCommand = $this->getMockBuilder(ConsumerCommand::class)
            ->setMethods(array('run'))
            ->getMock();
        
        $container = new ContainerBuilder();
        $container->setParameter('console.command.ids', array(
            'rabbitmq:consumer'
        ));
        $container->set('event_dispatcher', $eventDispatcher);
        $container->set('rabbitmq:consumer', $dummyRabbitCommand);
        $kernel = $this->getMockBuilder(KernelInterface::class)->getMock();
        $kernel->expects($this->any())->method('getBundles')->will($this->returnValue(array()));
        $kernel->expects($this->any())->method('getContainer')->will($this->returnValue($container));
        
        $container->set('kernel', $kernel);
        
        $dummyRabbitCommand->expects($this->once())
            ->method('run')
            ->with($this->callback(function(InputInterface $input) {
                if ($input->getOption('without-signals') == false) {
                    return false;
                }
                if ($input->getArgument('name') != 'consumer') {
                    return false;
                }
                return true;
            }))
            ->will($this->returnValue(null));
        $worker = new RabbitMqWorkerRunner();
        $worker->run(array('consumer_name' => 'consumer',), $container);
    }
    
    public function testRunNoParametersException()
    {
        $this->setExpectedException(InvalidArgumentException::class, 'Options array has to contain consumer_name.');
        $container = new ContainerBuilder();
        
        $worker = new RabbitMqWorkerRunner();
        $worker->run(array(), $container);
    }
    
}
