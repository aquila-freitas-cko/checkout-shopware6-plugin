<?php

namespace Checkoutcom\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;
use monolog\Logger;
use Monolog\Formatter\JsonFormatter;
use RuntimeException;
use Checkoutcom\Handler\DatadogHandler;
use Checkoutcom\Helper\Url;
use Checkoutcom\Helper\ckoException;
use Checkoutcom\Config\Config;

class CkoExceptionSubscriber implements EventSubscriberInterface {

    protected $config;
    
    /**
     *  @var LoggerInterface
     */
    public static $logger;

    /**
     *  get the substribed events
     */
    public static function getSubscribedEvents()
    {
        // return the subscribed events, their methods and priorities
        return [
            KernelEvents::EXCEPTION => [
                ['logCkoException', 10]
            ],
        ];
    }

    public function __construct(LoggerInterface $logger)
    {
        self::$logger = $logger;
    }

    public function logCkoException(ExceptionEvent $event)
    {
        $exception = $event->getException();

        // check if exception is an instance of ckoException
        if ($exception instanceof ckoException) {
            
            $body = $exception->getLogBody();
            $logLevel = $exception->getLogLevel();
            $url = Url::getCloudEventUrl();

            // print_r($body);
            // die();
            $formatter = new JsonFormatter();
            $datadogLogs = new DatadogHandler($url, $logLevel, true);
            $datadogLogs->setFormatter($formatter);

            self::$logger->pushHandler($datadogLogs);

            self::$logger->$logLevel(
                json_encode($body)
                );

                throw new RuntimeException('cko getCkoContext fail: ' . json_encode($body));
        }
    }

    
}