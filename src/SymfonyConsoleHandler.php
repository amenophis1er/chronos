<?php

namespace Amenophis\Chronos;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;

class SymfonyConsoleHandler extends AbstractProcessingHandler
{
    private $output;

    public function __construct(OutputInterface $output, $level = Logger::DEBUG, bool $bubble = true)
    {
        $this->output = $output;
        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void
    {
        $this->output->writeln($record['formatted']);
    }
}
