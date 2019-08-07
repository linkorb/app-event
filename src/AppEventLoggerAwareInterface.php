<?php

namespace LinkORB\AppEvent;

use Psr\Log\LoggerInterface;

interface AppEventLoggerAwareInterface
{
    public function setAppEventLogger(LoggerInterface $logger): void;
}
