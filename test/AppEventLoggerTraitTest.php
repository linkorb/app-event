<?php

namespace LinkORB\AppEvent\Test;

use LinkORB\AppEvent\AppEventLoggerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AppEventLoggerTraitTest extends TestCase
{
    private $logger;
    private $service;

    protected function setUp(): void
    {
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->service = $this->getMockForTrait(AppEventLoggerTrait::class);

        $this->service->setAppEventLogger($this->logger);
    }

    public function testLogWillLogWhenLoggerIsPresent()
    {
        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $this->identicalTo('info'),
                $this->identicalTo('my.event'),
                $this->identicalTo(['my-key' => 'my-val'])
            )
        ;

        $this->service->log('my.event', ['my-key' => 'my-val']);
    }

    public function testLogWillLogAtConfiguredDefaultLevelWhenCalledWithoutALevel()
    {
        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $this->identicalTo('error'),
                $this->identicalTo('my.event'),
                $this->identicalTo(['my-key' => 'my-val'])
            )
        ;

        $this->service->setDefaultLogLevel('error');

        $this->service->log('my.event', ['my-key' => 'my-val']);
    }

    /**
     * @dataProvider logLevels
     */
    public function testLogWillLogAtAllLogLevels($level)
    {
        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $this->identicalTo($level),
                $this->identicalTo('my.event'),
                $this->identicalTo(['my-key' => 'my-val'])
            )
        ;

        $this->service->log('my.event', ['my-key' => 'my-val'], $level);
    }

    public function logLevels()
    {
        $levels = [
            'debug',
            'info',
            'notice',
            'warning',
            'error',
            'critical',
            'alert',
            'emergency',
        ];

        foreach ($levels as $level) {
            yield "Log Level {$level}" => [$level];
        }
    }
}
