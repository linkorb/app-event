<?php

namespace LinkORB\AppEvent\Formatter;

use Monolog\Formatter\FormatterInterface;

/**
 * Reformat Monolog log records as LinkORB Application Events.
 *
 * The keys in the log records are mapped to those used for App Events, the
 * values are normalised and the resulting data formatted according to
 * the decorated FormatterInterface (e.g. JsonFormatter).
 */
class AppEventFormatter implements FormatterInterface
{
    protected $dateFormat;
    protected $formatter;

    public function __construct(FormatterInterface $formatter, $dateFormat = 'c')
    {
        $this->dateFormat = $dateFormat;
        $this->formatter = $formatter;
    }

    public function format(array $record)
    {
        return $this->formatter->format($this->normalize($this->map($record)));
    }

    public function formatBatch(array $records)
    {
        $batch = [];
        foreach ($records as $record) {
            $batch[] = $this->normalize($this->map($record));
        }

        return $this->formatter->formatBatch($batch);
    }

    /**
     * Normalise log records, recursively.
     *
     * Instances of DateTime are formatted as strings according to dateFormat.
     *
     * Resources (which cannot be encoded as Json) are scrubbed from the log
     * record, leaving just the name of the resource type.
     */
    protected function normalize($data, $depth = 0)
    {
        if ($depth > 9) {
            return 'Over 9 levels deep, aborting normalization';
        }

        if (\is_array($data)) {
            $normalized = [];

            $count = 0;
            foreach ($data as $key => $value) {
                if (++$count > 1000) {
                    $normalized['...'] = 'Over 1000 items (' . \count($data) . ' total), aborting normalization';
                    break;
                }
                $normalized[$key] = $this->normalize($value, $depth + 1);
            }

            return $normalized;
        }

        if ($data instanceof \DateTime) {
            return $data->format($this->dateFormat);
        }

        if (\is_resource($data)) {
            return sprintf('(scrubbed a resource of type %s)', \get_resource_type($data));
        }

        return $data;
    }

    /*
     * Monolog\Logger::addRecord will produce a record with the following fields
     *
     *   'message' => by LinkORB convention the "event_name", e.g. login.success
     *   'context' => by LinkORB convention an array of "event" data e.g. [username => jah, ...]
     *   'level' => // e.g. 250 (Logger::NOTICE)
     *   'level_name' => // e.g. NOTICE will become "log_level"
     *   'channel' => // name of the log channel
     *   'datetime' => // DateTime object will become "@timestamp"
     *   'extra' => // info added by monolog processors (e.g. TagProcessor)
     */
    private function map(array $record)
    {
        $mapped = $this->mapEventData(
            $this->mapLogLevel(
                $this->mapTimestamp(
                    $this->mapEventName([], $record),
                    $record
                ),
                $record
            ),
            $record
        );

        if (isset($record['extra'])) {
            $mapped = $this->mapExtraData($mapped, $record['extra']);
        }

        return $mapped;
    }

    /*
     * by convention, log() is called with the name of the event as the $message param
     */
    private function mapEventName(array $mapped, array $record)
    {
        $mapped['event_name'] = $record['message'];

        return $mapped;
    }

    /*
     * map @timestamp for filebeat/logstash
     */
    private function mapTimestamp(array $mapped, array $record)
    {
        $mapped['@timestamp'] = $record['datetime'];

        return $mapped;
    }

    /*
     * map log_level as the RFC 5424 name of the log level
     */
    private function mapLogLevel(array $mapped, array $record)
    {
        $mapped['log_level'] = $record['level_name'];

        return $mapped;
    }

    /*
     * map the event data
     */
    private function mapEventData(array $mapped, array $record)
    {
        $mapped['event'] = $record['context'];

        return $mapped;
    }

    /*
     * map data provided by various Processors
     */
    private function mapExtraData(array $mapped, array $extraData)
    {
        return $this->mapRemainingExtraData(
            $this->mapTagProcessorData(
                $this->mapTokenProcessorData(
                    $mapped,
                    $extraData
                ),
                $extraData
            ),
            $extraData
        );
    }

    /*
     * map the authentic user info provided by TokenProcessor
     */
    private function mapTokenProcessorData(array $mapped, array $extraData)
    {
        if (!isset($extraData['token'])) {
            return $mapped;
        }

        $mapped['user'] = $extraData['token'];

        return $mapped;
    }

    /*
     * map the tags provided by TagProcessor
     */
    private function mapTagProcessorData(array $mapped, array $extraData)
    {
        if (!isset($extraData['tags'])) {
            return $mapped;
        }

        $mapped['tags'] = $extraData['tags'];

        return $mapped;
    }

    /*
     * grab everything else, as is, from any remaining processors
     */
    private function mapRemainingExtraData(array $mapped, array $extraData)
    {
        unset($extraData['tags']);
        unset($extraData['token']);

        if (empty($extraData)) {
            return $mapped;
        }

        $mapped['extra'] = $extraData;

        return $mapped;
    }
}
