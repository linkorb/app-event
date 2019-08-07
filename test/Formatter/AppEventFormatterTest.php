<?php

namespace LinkORB\AppEvent\Test\Formatter;

use LinkORB\AppEvent\Formatter\AppEventFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class AppEventFormatterTest extends TestCase
{
    private $decoratedFormatter;
    private $formatter;

    protected function setUp()
    {
        $this->decoratedFormatter = $this->getMockBuilder(FormatterInterface::class)
            ->getMock()
        ;
        $this->formatter = new AppEventFormatter($this->decoratedFormatter);
    }

    /**
     * @dataProvider appEvents
     */
    public function testFormatWillNormaliseARecordAndPassTheResultToFormatter(array $record, array $normalised)
    {
        $this->decoratedFormatter
            ->expects($this->once())
            ->method('format')
            ->with($this->identicalTo($normalised))
        ;

        $this->formatter->format($record);
    }

    public function appEvents()
    {
        $record = [
            'message' => 'login.success',
            'context' => ['username' => 'william'],
            'level' => Logger::NOTICE,
            'level_name' => Logger::getLevelName(Logger::NOTICE),
            'channel' => 'app_event',
            'datetime' => new \DateTime('2000-01-01T01:01:01+00:00'),
            'extra' => [],
        ];
        $normalised = [
            'event_name' => 'login.success',
            '@timestamp' => '2000-01-01T01:01:01+00:00',
            'log_level' => Logger::getLevelName(Logger::NOTICE),
            'event' => ['username' => 'william'],
        ];

        yield 'basic event' => [$record, $normalised];

        $record['extra']['tags'] = $normalised['tags'] = ['one-tag', 'two-tag'];
        yield 'event with tags' => [$record, $normalised];

        unset($record['extra']['tags'], $normalised['tags']);

        $record['extra']['token'] = $normalised['user'] = ['username' => 'james'];
        yield 'event with user info' => [$record, $normalised];

        $record['extra']['tags'] = $normalised['tags'] = ['one-tag', 'two-tag'];
        yield 'event with tags and user info' => [$record, $normalised];

        $record['extra']['some-processor'] = $normalised['extra']['some-processor'] = 'some-info';
        yield 'event with tags, user info and extra info' => [$record, $normalised];

        unset(
            $record['extra']['tags'],
            $record['extra']['token'],
            $record['extra']['some-processor'],
            $normalised['tags'],
            $normalised['user'],
            $normalised['extra']['some-processor']
        );

        $record['extra']['some-resource'] = fopen('php://temp', 'r');
        $normalised['extra']['some-resource'] = '(scrubbed a resource of type stream)';
        yield 'event with a resource in the extra info' => [$record, $normalised];
    }

    /**
     * @dataProvider appEventBatches
     */
    public function testFormatBatchWillNormaliseMultipleRecordsAndPassResultToFormatter(array $records, array $normalisedBatch)
    {
        $this->decoratedFormatter
            ->expects($this->once())
            ->method('formatBatch')
            ->with($this->identicalTo($normalisedBatch))
        ;

        $this->formatter->formatBatch($records);
    }

    public function appEventBatches()
    {
        $record1 = [
            'message' => 'login.failure',
            'context' => ['username' => 'william'],
            'level_name' => Logger::getLevelName(Logger::ERROR),
            'datetime' => new \DateTime('2000-01-01T01:01:01+00:00'),
        ];
        $record2 = [
            'message' => 'login.success',
            'context' => ['username' => 'william'],
            'level_name' => Logger::getLevelName(Logger::NOTICE),
            'datetime' => new \DateTime('2000-01-01T01:01:01+00:01'),
        ];
        $normalised1 = [
            'event_name' => 'login.failure',
            '@timestamp' => '2000-01-01T01:01:01+00:00',
            'log_level' => Logger::getLevelName(Logger::ERROR),
            'event' => ['username' => 'william'],
        ];
        $normalised2 = [
            'event_name' => 'login.success',
            '@timestamp' => '2000-01-01T01:01:01+00:01',
            'log_level' => Logger::getLevelName(Logger::NOTICE),
            'event' => ['username' => 'william'],
        ];

        return [
            'batch of one event' => [[$record1], [$normalised1]],
            'batch of two events' => [[$record1, $record2], [$normalised1, $normalised2]],
        ];
    }
}
