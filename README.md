# Command Queue RabbitMQ Bundle

[![Build Status](https://img.shields.io/travis/Gendoria/command-queue-rabbitmq-bundle/master.svg)](https://travis-ci.org/Gendoria/command-queue-rabbitmq-bundle)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/Gendoria/command-queue-rabbitmq-bundle.svg)](https://scrutinizer-ci.com/g/Gendoria/command-queue-rabbitmq-bundle/?branch=master)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/Gendoria/command-queue-rabbitmq-bundle.svg)](https://scrutinizer-ci.com/g/Gendoria/command-queue-rabbitmq-bundle/?branch=master)
[![Downloads](https://img.shields.io/packagist/dt/gendoria/command-queue-rabbitmq-bundle.svg)](https://packagist.org/packages/gendoria/command-queue-rabbitmq-bundle)
[![Latest Stable Version](https://img.shields.io/packagist/v/gendoria/command-queue-rabbitmq-bundle.svg)](https://packagist.org/packages/gendoria/command-queue-rabbitmq-bundle)

RabbitMQ driver bundle for [Gendoria Command Queue Bundle](https://github.com/Gendoria/command-queue-bundle).

Bundle created in cooperation with [Isobar Poland](http://www.isobar.com/pl/).

![Isobar Poland](doc/images/isobar.jpg "Isobar Poland logo") 

## Installation

### Step 1: Download the Bundle


Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require gendoria/command-queue-rabbitmq-bundle "dev-master"
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle


Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Gendoria\CommandQueueRabbitMqDriverBundle\GendoriaCommandQueueRabbitMqDriverBundle(),
        );

        // ...
    }

    // ...
}
```

[Gendoria Command Queue Bundle](https://github.com/Gendoria/command-queue-bundle) and `php-amqplib/rabbitmq-bundle`
bundles should also be enabled and configured.


### Step 3: Add bundle configuration

The example bundle configuration looks as one below.

```yaml
gendoria_command_queue_rabbit_mq_driver:
    serializer: '@gendoria_command_queue.serializer.jms'
    drivers:
        poolname:
            rabbitmq_connection: default
            exchange_name: poolname-command-queue-bus
            consumer_name: poolname_command_queue_worker
            consumer_queue_name: poolname-command-queue-worker-consumer
            producer_name: poolname_command_queue
```

`serializer` parameter is used to specify serializer driver used by the driver. 
You should use `jms` or `symfony` driver here, where `jms` is preferred.

Some serializer drivers are provided by [Gendoria Command Queue Bundle](https://github.com/Gendoria/command-queue-bundle).

`drivers` key defines RabbitMQ command queue drivers. They can be then used by Gendoria Command Queue Bundle
as pool drivers. For each Command Queue pool using RabbitMQ transport, one driver should be defined.

Driver configuration consists of several fields, describing connection and worker details.
One driver entry has following keys:

- `rabbitmq_connection` - Connection name, as definned in rabbitmq bundle configuration.
- `exchange_name` - Exchange name for RabbitMQ.
- `consumer_name` - consumer name for RabbitMQ bundle.
- `consumer_queue_name` - Queue name for RabbitMQ.
- `producer_name` - Producer name for RabbitMQ bundle.

This bundle appends appropriate consumers and producers to `php-amqplib/rabbitmq-bundle`,
so no additional consumer / producer configuration is needed.

For each defined driver, service `gendoria_command_queue_rabbit_mq_driver.driver.driverName` will be created,
where `driverName` is the key in drivers configuration. So for above configuration, one service with ID 
`gendoria_command_queue_rabbit_mq_driver.driver.poolname` will be present.

### Step 4: Add a driver to Command Queue Bundle configuration

For each command queue pool you want to use rabbitmq driver on, you should set it as send_driver.

So for `gendoria_command_queue_rabbit_mq_driver.driver.poolname`, your configuration should look similar 
to code below.

```yaml
gendoria_command_queue:
    ...
    pools:
        ...
        poolname:
            send_driver: '@gendoria_command_queue_rabbit_mq_driver.driver.poolname'
```

### Step 5: Setup RabbitMQ fabric

This step is done by `php-amqplib/rabbitmq-bundle` command.

```
app/console rabbitmq:setup-fabric
```

It is optional, if you start your consumers before starting sending commands to queue.

## Usage

To start receiving commands for your pool, you have to start one rabbitmq bundle worker process.

The command to do that is:

```sh
app/console rabbitmq:consumer -w worker-name
```

where `worker-name` is the name you defined in key `consumer_name` of your driver configuration.

For the configuration from step 3, it will look like that:

```sh
app/console rabbitmq:consumer -w poolname_command_queue_worker
```

You should use services like [supervisord](http://supervisord.org/) to control running and restarting your workers.