<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Worker;

use Gendoria\CommandQueueBundle\Worker\WorkerRunnerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of RabbitMqWorkerRunner
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class RabbitMqWorkerRunner implements WorkerRunnerInterface
{
    /**
     * {@inheritdoc}
     */
    public function run(array $options, ContainerInterface $container, OutputInterface $output = null)
    {
        if (empty($options['consumer_name'])) {
            throw new InvalidArgumentException("Options array has to contain consumer_name.");
        }
        if (!$output) {
            $output = new NullOutput();
        }
        $kernel = $container->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput(array(
            'command' => 'rabbitmq:consumer',
            '-w' => null,
            'name' => $options['consumer_name'],
        ));
        $application->run($input, $output);
    }

}
