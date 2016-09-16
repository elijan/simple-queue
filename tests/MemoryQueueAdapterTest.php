<?php

require_once __DIR__.'/../vendor/autoload.php';

use SimpleQueue\Adapter\MemoryQueueAdapter;
use SimpleQueue\Job;
use SimpleQueue\Queue;

class MemoryQueueAdapterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Queue
     */
    protected $queue;

    public function setUp()
    {
        $this->queue = new Queue(new MemoryQueueAdapter());
    }

    public function testPushPull()
    {
        $this->queue
            ->push(new Job('JobA'))
            ->push(new Job('JobB'))
        ;

        $job = $this->queue->pull();
        $this->assertEquals('JobA', $job->getBody());

        $job = $this->queue->pull();
        $this->assertEquals('JobB', $job->getBody());

        $this->assertNull($this->queue->pull());
    }

    public function testSchedule()
    {
        $this->setExpectedException('SimpleQueue\Exception\NotSupportedException');
        $this->queue->schedule(new Job(), new DateTime('+1 day'));
    }

    public function testFailedJob()
    {
        $this->assertNull($this->queue->pull());

        $this->queue->failed(new Job('FailedJob'));

        $job = $this->queue->pull();
        $this->assertEquals('FailedJob', $job->getBody());
    }
}
