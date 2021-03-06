<?php

namespace DDTrace\Log;

/**
 * An implementation of the DDTrace\LoggerInterface that uses Psr\Log under the hood.
 */
final class PsrLogger implements LoggerInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $psrLogger = null;

    /**
     * @param \Psr\Log\LoggerInterface $psrLogger
     */
    public function __construct($psrLogger)
    {
        if (!is_a($psrLogger, '\Psr\Log\LoggerInterface')) {
            throw new \InvalidArgumentException(
                '\DDTrace\Log\PsrLogger constructor arg must implement \Psr\Log\LoggerInterface'
            );
        }
        $this->psrLogger = $psrLogger;
    }

    /**
     * Emits a debug level message.
     *
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = array())
    {
        $this->psrLogger->debug($message, $context);
    }

    /**
     * Logs a warning at the debug level.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function warning($message, array $context = [])
    {
        $this->psrLogger->warning($message, $context);
    }

    /**
     * Logs a error at the debug level.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function error($message, array $context = array())
    {
        $this->psrLogger->error($message, $context);
    }
}
