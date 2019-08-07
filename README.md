# linkorb/app-event

This library provides a way for applications to integrate a standard scheme for
logging Application Events.  Specifically, it provides:

-   AppEventFormatter, which:

    - normalises Monolog log records to the structure required for LinkORB
      Application Events and

    - formats the log records as Newline Delimited JSON (ndjson)

-   AppEventLoggerAwareInterface which describes a method (`setAppEventLogger`)
    by which an Application Event Logger may be injected into services and
controllers which need to log Application Events

-   AppEventLoggerInterface which describes a method (`log`) by which services
    may log Application Events

-   AppEventLoggerTrait which provides implementations of
    AppEventLoggerAwareInterface and AppEventLoggerInterface

An example:-

```php

class MyService implements AppEventLoggerAwareInterface,
    AppEventLoggerInterface
{
    use AppEventLoggerTrait;

    public function doSomething()
    {
        // ... do something ...
        $this->log('something.was.done', ['some-info' => '...', 'more' => ...]);
    }
}
```
