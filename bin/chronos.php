#!/usr/bin/env php
<?php
// bin/chronos.php

use Amenophis\Chronos\commands\GitCommand;
use Amenophis\Chronos\commands\GptDumpCommand;
use Amenophis\Chronos\commands\GptUpdateCodeCommand;
use Amenophis\Chronos\VersionChecker;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;

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

$versionFilePath = __DIR__ . '/../VERSION';// Read the version from the VERSION file
$currentVersion = file_exists($versionFilePath) ? trim(file_get_contents($versionFilePath)) : 'unknown';

// Create the application
$application = new Application("Chronos", $currentVersion);

$output = new ConsoleOutput();

# Perform version check
$versionChecker = new VersionChecker("$currentVersion", "https://github.com/amenophis1er/chronos");
$versionChecker->checkForUpdates($output);


try {
    // Add commands
    $application->add(new GptUpdateCodeCommand());
    $application->add(new GptDumpCommand());
    $application->add(new GitCommand());

    // Run the application
    $application->run();
} catch (Exception $e) {
    $output = new ConsoleOutput();
    $output->writeln('<error>An unexpected error occurred: ' . $e->getMessage() . '</error>');
    exit(1);
}
