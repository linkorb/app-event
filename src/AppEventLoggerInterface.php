<?php

namespace LinkORB\AppEvent;

interface AppEventLoggerInterface
{
    public function log(string $name, array $data, $level = null): void;

    public function setDefaultLogLevel(string $level): void;
}
