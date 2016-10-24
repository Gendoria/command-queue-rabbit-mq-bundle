<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Worker;

use Gendoria\CommandQueue\Worker\WorkerRunnerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Description of RabbitMqWorkerRunner
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class RabbitMqWorkerRunner implements WorkerRunnerInterface
{
    /**
     * Container.
     * 
     * @var ContainerInterface
     */
    private $container;
    
    /**
     * Set container.
     * 
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * Get container.
     * 
     * @return ContainerInterface|null
     */
    public function getContainer()
    {
        return $this->container;
    }
    
    /**
     * {@inheritdoc}
     */
    public function run(array $options, OutputInterface $output = null)
    {
        if (empty($options['consumer_name'])) {
            throw new InvalidArgumentException("Options array has to contain consumer_name.");
        }
        if (!$output) {
            $output = new NullOutput();
        }
        /* @var $kernel \Symfony\Component\HttpKernel\KernelInterface */
        $kernel = $this->container->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput(array(
            'command' => 'rabbitmq:consumer',
            '-w' => null,
            'name' => !empty($options['reschedule']) ? $options['consumer_name'].'_reschedule_delayed' : $options['consumer_name'],
        ));
        $application->run($input, $output);
    }

}
