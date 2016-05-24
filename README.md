SimpleQueue
===========

[![Build Status](https://travis-ci.org/fguillot/simple-queue.svg?branch=master)](https://travis-ci.org/fguillot/simple-queue)

SimpleQueue is a queue abstraction library written in PHP.

This library allows you to use the same interface for these drivers:

- SPL Queue
- Beanstalk
- Disque
- RabbitMQ
- AWS SQS

Requirements
------------

The library requires only PHP 5.3 but the different drivers may require another version of PHP.

Usage
-----

Choose the queue adapter:

```php
$queue = new Queue(new MyQueueAdapter());
```

Creating a new job:

```php
$job = new Job('MyPayload'); // The payload is automatically serialized in Json
```

Push a job to a queue:

```php
$queue->push($job);
```

Pull a job from a queue:

```php
$job = $queue->pull();
```

Acknowledge a job:

```php
$queue->completed($job);
```

Mark a job as failed:

```php
$queue->failed($job);
```

Wait for new jobs:

```php
while ($job = $queue->pull()) {
    echo $job->getBody(); // Do something with $job
    $queue->completed($job);
}
```

In-Memory Queue
---------------

Require PHP 5.3.

```php
<?php

use SimpleQueue\Adapter\MemoryQueueAdapter;
use SimpleQueue\Job;
use SimpleQueue\Queue;

require __DIR__.'/vendor/autoload.php';

$queue = new Queue(new MemoryQueueAdapter());
$queue
    ->push(new Job('JobA'))
    ->push(new Job('JobB'))
;

$job = $queue->pull();
echo $job->getBody();
```

Beanstalk
---------

Require PHP 5.3 and the library `pda/pheanstalk`.

```php
<?php

use Pheanstalk\Pheanstalk;
use SimpleQueue\Adapter\BeanstalkQueueAdapter;
use SimpleQueue\Queue;

require __DIR__.'/vendor/autoload.php';

$queue = new Queue(new BeanstalkQueueAdapter(new Pheanstalk('127.0.0.1'), 'myTube'));
$queue->push(new Job('foobar'));

while ($job = $queue->pull()) {
    echo $job->getBody(); // Do something with $job
    $queue->completed($job);
}
```

Disque
------

Require PHP 5.5 and the library `mariano/disque-php`.

```php
<?php

use Disque\Client;
use Disque\Connection\Credentials;
use SimpleQueue\Adapter\DisqueQueueAdapter;
use SimpleQueue\Job;
use SimpleQueue\Queue;

require __DIR__.'/vendor/autoload.php';

$queue = new Queue(new DisqueQueueAdapter(new Client([new Credentials('127.0.0.1', 7711)]), 'myQueue'));
$queue->push(new Job('foobar'));

while ($job = $queue->pull()) {
    $queue->completed($job);
}
```

RabbitMQ
--------

Require PHP 5.3 and the library `php-amqplib/php-amqplib`.

```php
<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use SimpleQueue\Adapter\AmqpQueueAdapter;
use SimpleQueue\Job;
use SimpleQueue\Queue;

require __DIR__.'/vendor/autoload.php';

$connection = new AMQPStreamConnection('127.0.0.1', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('myQueue', false, true, false, false);
$channel->exchange_declare('myExchange', 'direct', false, true, false);
$channel->queue_bind('myQueue', 'myExchange');

$queue = new Queue(new AmqpQueueAdapter($channel, 'myQueue', 'myExchange'));
$queue->push(new Job('failed'));

while ($job = $queue->pull()) {
    $queue->failed($job);
}
```

AWS SQS
-------

Require PHP 5.5 and the library `aws/aws-sdk-php`.

```php
<?php

use Aws\Sqs\SqsClient;
use SimpleQueue\Adapter\AwsSqsQueueAdapter;
use SimpleQueue\Job;
use SimpleQueue\Queue;

require __DIR__.'/vendor/autoload.php';

$sqsClient = new SqsClient(array(
    'version'     => 'latest',
    'region' => 'us-east-1',
    'credentials' => array(
        'key' => 'my-aws-key',
        'secret' => 'my-aws-secret'
    )
));

$config = array(
    'LongPollingTime' => 10 // Optional long polling of SQS HTTP request (default 0)
);

$queue = new Queue(new AwsSqsQueueAdapter('MyQueueName', $sqsClient, $config));
$queue->push(new Job('foobar'));

while ($job = $queue->pull()) {
    echo $job->getBody(); // Do something with $job
    $queue->completed($job);
}
```
