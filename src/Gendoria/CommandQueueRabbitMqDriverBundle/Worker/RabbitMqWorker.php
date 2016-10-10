<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Worker;

use Exception;
use Gendoria\CommandQueue\CommandProcessor\CommandProcessorInterface;
use Gendoria\CommandQueue\ProcessorFactoryInterface;
use Gendoria\CommandQueue\ProcessorNotFoundException;
use Gendoria\CommandQueue\Serializer\SerializedCommandData;
use Gendoria\CommandQueue\Serializer\SerializerInterface;
use Gendoria\CommandQueue\Worker\Exception\ProcessorErrorException;
use Gendoria\CommandQueue\Worker\Exception\TranslateErrorException;
use Gendoria\CommandQueueBundle\Worker\BaseSymfonyWorker;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Command queue worker listening on commands form RabbitMQ channel.
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class RabbitMqWorker extends BaseSymfonyWorker implements ConsumerInterface
{

    /**
     * Rabbit MQ worker subsystem name.
     * 
     * @var string
     */
    const SUBSYSTEM_NAME = "RabbitMqWorker";

    /**
     * Reschedule producer instance.
     *
     * @var ProducerInterface
     */
    private $rescheduleProducer;

    /**
     * Class constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher    Symfony event dispatcher.
     * @param ProcessorFactoryInterface         $processorFactory
     * @param SerializerInterface               $serializer
     * @param ProducerInterface                 $rescheduleProducer
     * @param LoggerInterface          $logger             Logger instance.
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, ProcessorFactoryInterface $processorFactory, SerializerInterface $serializer, ProducerInterface $rescheduleProducer, LoggerInterface $logger = null)
    {
        parent::__construct($processorFactory, $serializer, $eventDispatcher, $logger);

        $this->rescheduleProducer = $rescheduleProducer;
    }

    /**
     * Process single message received from RabbitMq server.
     *
     * @param AMQPMessage $msg
     *
     * @return null|integer Return code, dictating further message status.
     */
    public function execute(AMQPMessage $msg)
    {
        //We try to process message. On known errors we try to reschedule. On unknown - we simply reject.
        try {
            $this->process($msg);
        } catch (ProcessorErrorException $e) {
            $this->maybeReschedule($msg, $e, $e->getProcessor());
            return self::MSG_REJECT;
        } catch (ProcessorNotFoundException $e) {
            $this->maybeReschedule($msg, $e);
            return self::MSG_REJECT;
        } catch (TranslateErrorException $e) {
            $this->maybeReschedule($msg, $e);
            return self::MSG_REJECT;
        }
        return self::MSG_ACK;
    }

    /**
     * @param AMQPMessage $commandData
     * {@inheritdoc}
     */
    protected function getSerializedCommandData($commandData)
    {
        /* @var $commandData AMQPMessage */
        $headers = $commandData->get('application_headers')->getNativeData();
        if (empty($headers['x-class-name'])) {
            throw new TranslateErrorException($commandData, "Class name header 'x-class-name' not found");
        }
        return new SerializedCommandData($commandData->body, $headers['x-class-name']);
    }    

    public function getSubsystemName()
    {
        return self::SUBSYSTEM_NAME;
    }

    /**
     * Send message for rescheduler, if maximum number of tries has not been exceeded.
     *
     * @param AMQPMessage               $msg
     * @param Exception                 $e
     * @param CommandProcessorInterface $processor
     */
    private function maybeReschedule(AMQPMessage $msg, Exception $e, CommandProcessorInterface $processor = null)
    {
        $triesNum = 10;
        $headers = $msg->get('application_headers')->getNativeData();
        $retryCount = $this->getRetryCount($headers);
        $retry = ($retryCount < $triesNum - 1);
        $resheduleInS = (5 * $retryCount + 10);

        $this->logger->error(
            sprintf(
                'Error while executing processor (retry count: %d - %s): %s', $retryCount + 1, $retry ? 'retry in ' . $resheduleInS . 's' : 'reject', $e->getMessage()
            ), array($e->getTraceAsString(), $this, $processor)
        );

        if ($retry) {
            $this->rescheduleProducer->publish(
                $msg->body, (string)$msg->delivery_info['routing_key'], array_merge(
                    $msg->get_properties(), array('expiration' => $resheduleInS * 1000)
                )
            );
        }
    }

    /**
     * Get retry count.
     * 
     * @param array $headers
     * @return integer
     */
    private function getRetryCount($headers)
    {
        if (!empty($headers['x-death'])) {
            if (!empty($headers['x-death'][0]['count'])) {
                $retryCount = $headers['x-death'][0]['count'];
            } else {
                $retryCount = count($headers['x-death']);
            }
        } else {
            $retryCount = 0;
        }
        return $retryCount;
    }
}
