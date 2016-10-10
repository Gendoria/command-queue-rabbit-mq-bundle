<?php

namespace Gendoria\CommandQueueRabbitMqDriverBundle\Tests;

use Gendoria\CommandQueueRabbitMqDriverBundle\DependencyInjection\GendoriaCommandQueueRabbitMqDriverExtension;
use Gendoria\CommandQueueRabbitMqDriverBundle\GendoriaCommandQueueRabbitMqDriverBundle;
use PHPUnit_Framework_TestCase;

/**
 * Description of GendoriaCommandQueueRabbitMqDriverBundleTest
 *
 * @author Tomasz Struczyński <t.struczynski@gmail.com>
 */
class GendoriaCommandQueueRabbitMqDriverBundleTest extends PHPUnit_Framework_TestCase
{
    public function testGetContainerExtension()
    {
        $bundle = new GendoriaCommandQueueRabbitMqDriverBundle();
        $this->assertInstanceOf(GendoriaCommandQueueRabbitMqDriverExtension::class, $bundle->getContainerExtension());
    }
}
