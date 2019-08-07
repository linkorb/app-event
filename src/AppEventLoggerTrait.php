<?php

namespace LinkORB\AppEvent;

use Psr\Log\LoggerInterface;

trait AppEventLoggerTrait
{
    protected $appEventLogger;
    protected $defaultLogLevel = 'info';

    public function setAppEventLogger(LoggerInterface $logger): void
    {
        $this->appEventLogger = $logger;
    }

    public function setDefaultLogLevel(string $level): void
    {
        $this->defaultLogLevel = $level;
    }

    public function log(string $name, array $data, $level = null): void
    {
        if (!$this->appEventLogger) {
            return;
        }
        if (null === $level) {
            $level = $this->defaultLogLevel;
        }

        $this->appEventLogger->log($level, $name, $data);
    }
}
