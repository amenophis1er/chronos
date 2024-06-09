#!/usr/bin/env php
<?php
// bin/chronos.php

use Amenophis\Chronos\commands\GitCommand;
use Amenophis\Chronos\commands\GptDumpCommand;
use Amenophis\Chronos\commands\GptUpdateCodeCommand;
use Amenophis\Chronos\VersionChecker;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;

// Constants for configuration
define('REQUIRED_PHP_VERSION', '8.0.2');
define('AUTOLOAD_FILE', __DIR__ . '/../vendor/autoload.php');
define('VERSION_FILE_PATH', __DIR__ . '/../VERSION');


// Check prerequisites before running the application
checkPhpVersion();
checkAutoloadFile();

// Autoload dependencies
require AUTOLOAD_FILE;

// Create the application
$application = new Application("Chronos", getCurrentVersion());
$output = new ConsoleOutput();

// Perform version check
$versionChecker = new VersionChecker(getCurrentVersion(), "https://github.com/amenophis1er/chronos");
$versionChecker->checkForUpdates($output);

try {
    // Register commands
    registerCommands($application);

    // Run the application
    $application->run();
} catch (Exception $e) {
    fwrite(STDERR, "An unexpected error occurred: " . $e->getMessage() . "\n");
    exit(1);
}

/**
 * Check if the current PHP version meets the requirement.
 */
function checkPhpVersion()
{
    if (version_compare(PHP_VERSION, REQUIRED_PHP_VERSION, '<')) {
        fwrite(STDERR, "Error: This application requires PHP " . REQUIRED_PHP_VERSION . " or higher. Your PHP version is " . PHP_VERSION . ".\n");
        exit(1);
    }
}

/**
 * Check if the autoload file exists.
 */
function checkAutoloadFile()
{
    if (!file_exists(AUTOLOAD_FILE)) {
        fwrite(STDERR, "Error: Autoload file not found. Please run \"composer install\" first.\n");
        exit(1);
    }
}

/**
 * Get the current version of the application from the VERSION file.
 *
 * @return string The current version or 'unknown' if the file does not exist.
 */
function getCurrentVersion()
{
    if (file_exists(VERSION_FILE_PATH)) {
        return trim(file_get_contents(VERSION_FILE_PATH));
    }
    return 'unknown';
}

/**
 * Register application commands.
 *
 * @param Application $application The Symfony Console application instance.
 */
function registerCommands(Application $application)
{
    $application->add(new GptUpdateCodeCommand());
    $application->add(new GptDumpCommand());
    $application->add(new GitCommand());
}
