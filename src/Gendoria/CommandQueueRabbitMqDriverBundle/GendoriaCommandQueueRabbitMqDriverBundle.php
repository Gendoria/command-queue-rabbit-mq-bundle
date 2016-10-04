<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle;

use Gendoria\CommandQueueRabbitMqDriverBundle\DependencyInjection\GendoriaCommandQueueRabbitMqDriverExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * RabbitMQ integration bundle for Gendoria Command Queue.
 */
class GendoriaCommandQueueRabbitMqDriverBundle extends Bundle
{
    /**
     * Get bundle extension instance.
     *
     * @return GendoriaCommandQueueRabbitMqDriverExtension
     */
    public function getContainerExtension()
    {
        if (null === $this->extension || false === $this->extension) {
            $this->extension = new GendoriaCommandQueueRabbitMqDriverExtension();
        }

        return $this->extension;
    }
}
