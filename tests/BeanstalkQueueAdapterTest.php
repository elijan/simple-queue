<?php

require_once __DIR__.'/../vendor/autoload.php';

use Pheanstalk\Pheanstalk;
use SimpleQueue\Job;
use SimpleQueue\Queue;

class BeanstalkQueueAdapterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var Pheanstalk
     */
    protected $beanstalk;

    public function setUp()
    {
        $this->beanstalk = $this
            ->getMockBuilder('Pheanstalk\Pheanstalk')
            ->setConstructorArgs(array('localhost'))
            ->setMethods(array('putInTube', 'reserveFromTube', 'delete', 'bury'))
            ->getMock();

        $this->queue = new Queue(new \SimpleQueue\Adapter\BeanstalkQueueAdapter($this->beanstalk, 'MyQueue'));
    }

    public function testPush()
    {
        $this->beanstalk
            ->expects($this->at(0))
            ->method('putInTube')
            ->with('MyQueue', '"JobA"');

        $this->beanstalk
            ->expects($this->at(1))
            ->method('putInTube')
            ->with('MyQueue', '"JobB"');

        $this->queue
            ->push(new Job('JobA'))
            ->push(new Job('JobB'))
        ;
    }

    public function testSchedule()
    {
        $this->beanstalk
            ->expects($this->once())
            ->method('putInTube')
            ->with('MyQueue', '"JobA"', Pheanstalk::DEFAULT_PRIORITY, 3600);

        $this->queue->schedule(new Job('JobA'), new DateTime('+1hour'));
    }

    public function testPull()
    {
        $this->beanstalk
            ->expects($this->once())
            ->method('reserveFromTube')
            ->with('MyQueue')
            ->will($this->returnValue(new \Pheanstalk\Job(123, '"SomeData"')))
        ;

        $job = $this->queue->pull();
        $this->assertInstanceOf(Job::class, $job);
        $this->assertEquals(123, $job->getId());
        $this->assertEquals('SomeData', $job->getBody());
    }

    public function testCompleted()
    {
        $this->beanstalk
            ->expects($this->once())
            ->method('delete')
            ->with(new \Pheanstalk\Job(1234, '"JobA"'));

        $job = new Job('JobA');
        $job->setId(1234);

        $this->queue->completed($job);
    }

    public function testFailed()
    {
        $this->beanstalk
            ->expects($this->once())
            ->method('bury')
            ->with(new \Pheanstalk\Job(1234, '"JobA"'));

        $this->queue->failed(new Job('JobA', 1234));
    }
}
