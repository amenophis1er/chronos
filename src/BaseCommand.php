<?php

namespace Amenophis\Chronos;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;

abstract class BaseCommand extends Command
{
    protected          $debug = false;
    protected          $logger;
    private Filesystem $filesystem;

    public function __construct()
    {
        parent::__construct();
        $this->filesystem = new Filesystem();
        $this->loadEnv();
    }
    private function loadEnv(): void
    {
        $dotenv = new Dotenv();
        $envPath = getcwd() . '/.env';

        if ($this->filesystem->exists($envPath)) {
            $dotenv->usePutenv(true)->loadEnv($envPath);
            $this->debug = getenv('DEBUG') === 'true';
        } else {
            $this->debug = false;
        }
    }
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->initializeLogger($output);
    }


    private function initializeLogger(OutputInterface $output)
    {
        $this->logger = new Logger('app');

        $logLevel = $this->debug ? Logger::DEBUG : Logger::INFO;

        // Create the custom handler using Symfony's OutputInterface
        $handler = new SymfonyConsoleHandler($output, $logLevel);

        // Apply the enhanced formatter
        $formatter = $this->getCustomFormatter();
        $handler->setFormatter($formatter);

        $this->logger->pushHandler($handler);
    }



    private function getCustomFormatter(): EnhancedLineFormatter
    {
        $outputFormat = "[%datetime%] <fg=magenta>%channel%</>: <fg=%level_color%>%level_name%</>: %message% %context% %extra%\n";
        $formatter = new EnhancedLineFormatter($outputFormat, 'H:i:s');

        $formatter->includeStacktraces(true);

        return $formatter;
    }
}
