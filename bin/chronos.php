#!/usr/bin/env php
<?php
// bin/chronos.php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

$autoloadFile = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoloadFile)) {
    $output = new ConsoleOutput();
    $output->writeln('<error>Error: Autoload file not found. Please run "composer install" first.</error>');
    exit(1);
}

// Autoload dependencies
require $autoloadFile;

// PHP version check
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    $output = new ConsoleOutput();
    $output->writeln('<error>Error: This application requires PHP 7.4 or higher.</error>');
    exit(1);
}

// Create the application
$application = new Application("Chronos", "1.0");

try {
    // Add commands
    $application->add(new \Amenophis\Chronos\GptCommand());
    $application->add(new \Amenophis\Chronos\GptDumpCommand());
    $application->add(new \Amenophis\Chronos\GitCommand());

    // Run the application
    $application->run();
} catch (Exception $e) {
    $output = new ConsoleOutput();
    $output->writeln('<error>An unexpected error occurred: ' . $e->getMessage() . '</error>');
    exit(1);
}
