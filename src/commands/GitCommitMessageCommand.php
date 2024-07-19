<?php

namespace Amenophis\Chronos\commands;

use Amenophis\Chronos\BaseCommand;
use Amenophis\Chronos\LLM\LLMFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

class GitCommitMessageCommand extends BaseCommand
{
    protected static $defaultName = 'git:commit-message';

    protected function configure()
    {
        $this
            ->setDescription('Generate a commit message for uncommitted changes using an LLM.')
            ->setHelp('This command analyzes the current Git repository and generates a commit message for uncommitted changes using a configured LLM provider.')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'LLM provider to use (default: openai)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->isGitRepository()) {
            $output->writeln('<error>Not a git repository.</error>');
            return Command::FAILURE;
        }

        $diff = $this->getUncommittedDiff();

        if (empty($diff)) {
            $output->writeln('<info>No uncommitted changes found.</info>');
            return Command::SUCCESS;
        }

        $changes = $this->analyzeChanges($diff);
        $output->writeln('<info>Summary of Changes:</info>');
        foreach ($changes as $type => $files) {
            if (!empty($files)) {
                $output->writeln("$type:");
                foreach ($files as $file) {
                    $output->writeln("  - $file");
                }
            }
        }
        $output->writeln('');

        $config = require __DIR__ . '/../../config/llm_config.php';
        $provider = $input->getOption('provider') ?? $config['default_provider'];

        try {
            $llm = LLMFactory::create($provider, $config['providers'][$provider]);
            $commitMessage = $llm->generateCommitMessage($diff, $changes);

            $this->displayCommitMessage($output, $commitMessage);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error generating commit message: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    private function isGitRepository(): bool
    {
        exec('git rev-parse --is-inside-work-tree 2>/dev/null', $output, $returnCode);
        return $returnCode === 0;
    }

    private function getUncommittedDiff(): string
    {
        exec('git add -N .', $output, $returnCode); // Stage new files without adding them
        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to stage new files');
        }

        exec('git diff HEAD', $output);
        $diff = implode("\n", $output);

        exec('git reset', $output, $returnCode); // Unstage the new files
        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to unstage new files');
        }

        return $diff;
    }

    private function analyzeChanges(string $diff): array
    {
        $lines = explode("\n", $diff);
        $changes = [
            'Added' => [],
            'Modified' => [],
            'Deleted' => []
        ];
        $currentFile = null;

        foreach ($lines as $line) {
            if (strpos($line, 'diff --git') === 0) {
                $parts = explode(' ', $line);
                $currentFile = substr($parts[3], 2); // Remove 'b/' prefix
                // Reset the file status for each new file encountered
                $fileStatus = 'Modified';
            } elseif (strpos($line, 'new file mode') === 0) {
                $fileStatus = 'Added';
            } elseif (strpos($line, 'deleted file mode') === 0) {
                $fileStatus = 'Deleted';
            }

            if ($currentFile && !in_array($currentFile, $changes[$fileStatus])) {
                $changes[$fileStatus][] = $currentFile;
            }
        }

        // Remove duplicates and empty categories
        foreach ($changes as $status => $files) {
            $changes[$status] = array_unique($files);
            if (empty($changes[$status])) {
                unset($changes[$status]);
            }
        }

        return $changes;
    }

    private function displayCommitMessage(OutputInterface $output, string $commitMessage)
    {
        $output->writeln('');
        $terminal = new Terminal();
        $width = min($terminal->getWidth(), 100);

        $output->writeln('<bg=blue;fg=white;options=bold>' . str_pad(' Generated Commit Message ', $width, ' ', STR_PAD_BOTH) . '</>');
        $output->writeln(str_repeat('-', $width));

        $output->writeln($this->wordwrap($commitMessage, $width));

        $output->writeln(str_repeat('-', $width));
        $output->writeln('');
    }

    private function wordwrap($string, $width)
    {
        return implode("\n", array_map(function($line) use ($width) {
            return wordwrap($line, $width, "\n", true);
        }, explode("\n", $string)));
    }
}