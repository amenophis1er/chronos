<?php

namespace Amenophis\Chronos;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;

class EnhancedLineFormatter extends LineFormatter
{
    private $levelColorMap = [
        // Mapping Monolog levels to Symfony Console colors
        Logger::DEBUG => 'blue',
        Logger::INFO => 'green',
        Logger::NOTICE => 'cyan',
        Logger::WARNING => 'yellow',
        Logger::ERROR => 'red',
        Logger::CRITICAL => 'bright-red',
        Logger::ALERT => 'bright-magenta',
        Logger::EMERGENCY => 'bright-red'
    ];

    public function format(array $record): string
    {
        // Add a valid Symfony Console color based on the log level
        $record['level_color'] = $this->levelColorMap[$record['level']] ?? 'default';
        return parent::format($record);
    }
}