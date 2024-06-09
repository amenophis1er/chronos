<?php

namespace Amenophis\Chronos\commands;

use Amenophis\Chronos\BaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class GptDumpCommand extends BaseCommand
{
    protected static $defaultName = 'gpt:dump';
    private $filesystem;

    public function __construct()
    {
        // Call the parent constructor to initialize BaseCommand
        parent::__construct();
        $this->filesystem = new Filesystem();
//        $this->loadEnv();
    }

    protected function configure()
    {
        $this
            ->setDescription('Generates a file of the current directory structure and file contents.')
            ->setHelp('This command generates a file containing the directory structure and the contents of all files in the current directory, excluding specified paths, files, or types.')
            ->addOption('exclude', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'List of additional paths, files, or file types to exclude, comma-separated')
            ->addOption('extension', null, InputOption::VALUE_REQUIRED, 'File extension to use for the output file', 'md');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentPath = getcwd();
        $directoryName = basename($currentPath);
        $fileExtension = $input->getOption('extension');
        $outputFileName = $directoryName . '.' . $fileExtension;

        // Default exclude patterns
        $defaultExclusions = [
            '.git',
            'node_modules',
            'vendor',
            '*.log',
            'composer.lock',
            'package-lock.json'
        ];

        // Parse exclude patterns from input and .gitignore
        $userExclusions = $this->parseExclusions($input->getOption('exclude'));
        $gitignoreExclusions = $this->parseGitignore($currentPath);

        $excludePatterns = array_merge($defaultExclusions, $userExclusions, $gitignoreExclusions);

        $this->logger->debug("Exclusion patterns: " . json_encode($excludePatterns));

        $output->writeln([
            '<fg=green>Generating dump for ' . $directoryName . ' as ' . $outputFileName . '</>',
            '--------------------------------------------',
        ]);

        if ($this->filesystem->exists($outputFileName)) {
            $this->filesystem->remove($outputFileName);
        }

        // Exclude the output file from the dump
        $excludePatterns[] = $outputFileName;

        $markdownContent = $this->generateMarkdown($currentPath, $excludePatterns, $output);

        file_put_contents($outputFileName, $markdownContent);

        $output->writeln('<fg=green>Dump created: ' . $outputFileName . '</>');

        return Command::SUCCESS;
    }

    private function parseExclusions(array $excludeOptions): array
    {
        $patterns = [];

        foreach ($excludeOptions as $option) {
            $patterns = array_merge($patterns, array_map('trim', explode(',', $option)));
        }

        return array_filter($patterns); // Remove empty values
    }

    private function parseGitignore(string $path): array
    {
        $gitignorePath = $path . '/.gitignore';
        $patterns = [];

        if ($this->filesystem->exists($gitignorePath)) {
            $lines = file($gitignorePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                // Ignore comments and negated patterns
                if ($line && $line[0] !== '#' && $line[0] !== '!') {
                    $patterns[] = $line;
                }
            }
        }

        return $patterns;
    }

    private function generateMarkdown(string $path, array $excludePatterns, OutputInterface $output): string
    {
        $finder = new Finder();
        $finder->in($path)->sortByName();

        // Apply exclusions
        foreach ($excludePatterns as $pattern) {
            if ($this->isDirectoryPattern($pattern)) {
                $finder->exclude($pattern);
            } else {
                $finder->notName($pattern);
            }
        }

        $markdown = "# Directory Structure and File Contents for `" . basename($path) . "`\n\n";

        // Section 1: Directory Structure
        $markdown .= "## Directory Structure\n\n";
        $markdown .= "```\n";
        $markdown .= $this->generateTree($path, $excludePatterns, $output);
        $markdown .= "```\n\n";

        // Section 2: File Contents
        $markdown .= "## File Contents\n\n";

        foreach ($finder->files() as $file) {
            $relativePath = str_replace($path . '/', '', $file->getRealPath());
            if ($this->isExcluded($relativePath, $excludePatterns)) {
                $this->logger->debug("Explicitly excluded file: " . $relativePath);
                continue;
            }
            if ($this->isPlainTextFile($file->getRealPath())) {
                $markdown .= "### `" . $relativePath . "`\n";
                $markdown .= "```\n";
                $markdown .= $file->getContents();
                $markdown .= "\n```\n\n";
            } else {
                $this->logger->debug("Skipped non-text file: " . $relativePath);
            }
        }

        return $markdown;
    }

    private function generateTree(string $path, array $excludePatterns, OutputInterface $output, string $prefix = ''): string
    {
        $finder = new Finder();
        $finder->depth('== 0')->in($path)->sortByName();

        // Apply exclusions
        foreach ($excludePatterns as $pattern) {
            if ($this->isDirectoryPattern($pattern)) {
                $finder->exclude($pattern);
            } else {
                $finder->notName($pattern);
            }
        }

        $tree = '';
        foreach ($finder as $item) {
            $relativePath = str_replace($path . '/', '', $item->getRealPath());
            if ($this->isExcluded($relativePath, $excludePatterns)) {
                $this->logger->debug("Explicitly excluded path: " . $relativePath);
                continue;
            }
            $tree .= $prefix . $item->getFilename() . "\n";
            if ($item->isDir()) {
                $tree .= $this->generateTree($item->getRealPath(), $excludePatterns, $output, $prefix . '    ');
            }
        }

        return $tree;
    }

    private function isDirectoryPattern(string $pattern): bool
    {
        return !preg_match('/[*?]/', $pattern) && strpos($pattern, '/') === false;
    }

    private function isPlainTextFile(string $filePath): bool
    {
        $mimeType = \mime_content_type($filePath);
        return strpos($mimeType, 'text/') === 0 || $mimeType === 'application/json' || $mimeType === 'application/javascript' || $mimeType === 'application/xml';
    }

    private function isExcluded(string $relativePath, array $excludePatterns): bool
    {
        foreach ($excludePatterns as $pattern) {
            if (fnmatch($pattern, $relativePath)) {
                return true;
            }
        }
        return false;
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

//    private function debugLog(OutputInterface $output, string $message): void
//    {
//        if ($this->debug) {
//            $output->writeln('<fg=yellow>[DEBUG]</> ' . $message);
//        }
//    }
}
