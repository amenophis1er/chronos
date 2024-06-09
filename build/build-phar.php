<?php

// build-phar.php
$pharFile = __DIR__ . '/../dist/chronos.phar';
$sourceDir = realpath(__DIR__ . '/../');

// clean up
if (file_exists($pharFile)) {
    unlink($pharFile);
}

$phar = new Phar($pharFile);

// Custom iterator to exclude paths
$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
        function ($file, $key, $iterator) {
            // Exclude directories or files you don't want
            $excludedPaths = [
                '/dist',  // Exclude dist
                '/build', // Exclude build
                '/tests',
                '/docs',
                '/FileSystemOps.md',
                '/FileSystemOps.md',
            ];

            foreach ($excludedPaths as $excludedPath) {
                if (strpos($file->getRealPath(), $excludedPath) !== false) {
                    return false;
                }
            }
            return true;
        }
    )
);

// Build PHAR from filtered files
$phar->buildFromIterator($iterator, $sourceDir);

// Set the stub
$phar->setStub($phar->createDefaultStub('bin/chronos.php'));

echo "PHAR file created successfully.\n";
