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
            ->setDescription('Generate a commit message for uncommitted changes.')
            ->setHelp('This command analyzes the current Git repository and generates a commit message for uncommitted changes, optionally using an LLM provider.')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'LLM provider to use (default: none)');
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

        $provider = $input->getOption('provider');

        if ($provider) {
            return $this->generateLLMCommitMessage($input, $output, $diff, $provider);
        } else {
            return $this->generateSimpleCommitMessage($output, $diff);
        }
    }

    private function generateSimpleCommitMessage(OutputInterface $output, string $diff): int
    {
        $changes = $this->analyzeChanges($diff);
        $summary = count($changes) > 1 ? "Multiple changes" : $changes[0];
        $details = implode("\n", $changes);

        $commitMessage = "$summary\n\nDetails:\n$details";

        $output->writeln('<info>Generated Commit Message:</info>');
        $output->writeln($commitMessage);

        return Command::SUCCESS;
    }

    private function generateLLMCommitMessage(InputInterface $input, OutputInterface $output, string $diff, string $provider): int
    {
        $changes = $this->analyzeChanges($diff);

        $output->writeln('<info>Summary of Changes:</info>');
        $categorizedChanges = [
            'Added' => [],
            'Modified' => [],
            'Deleted' => []
        ];

        foreach ($changes as $change) {
            $type = explode(' ', $change)[0];
            $file = implode(' ', array_slice(explode(' ', $change), 1));
            $categorizedChanges[$type][] = $file;
        }

        foreach ($categorizedChanges as $type => $files) {
            if (!empty($files)) {
                $output->writeln("$type:");
                foreach ($files as $file) {
                    $output->writeln("  - $file");
                }
            }
        }
        $output->writeln('');

        $config = require __DIR__ . '/../../config/llm_config.php';

        if (!isset($config['providers'][$provider])) {
            $output->writeln("<error>Provider '$provider' not configured.</error>");
            return Command::FAILURE;
        }

        try {
            $llm = LLMFactory::create($provider, $config['providers'][$provider]);
            $commitMessage = $llm->generateCommitMessage($diff, $categorizedChanges);

            $this->displayCommitMessage($output, $commitMessage);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error generating commit message: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    private function displayCommitMessage(OutputInterface $output, string $commitMessage)
    {
        $output->writeln('');
        $terminal = new Terminal();
        $width = min($terminal->getWidth(), 100); // Max width of 100 or terminal width, whichever is smaller

        $output->writeln('<bg=blue;fg=white;options=bold>' . str_pad(' Generated Commit Message ', $width, ' ', STR_PAD_BOTH) . '</>');
        $output->writeln(str_repeat('-', $width));

        $lines = explode("\n", $commitMessage);
        $subject = array_shift($lines);
        $body = implode("\n", $lines);

        $output->writeln("<fg=yellow;options=bold>" . $this->wordwrap($subject, $width) . "</>");
        $output->writeln('');
        $output->writeln($this->wordwrap($body, $width));

        $output->writeln(str_repeat('-', $width));
        $output->writeln('');
    }

    private function wordwrap($string, $width)
    {
        return implode("\n", array_map(function($line) use ($width) {
            return wordwrap($line, $width, "\n", true);
        }, explode("\n", $string)));
    }

    private function isGitRepository(): bool
    {
        exec('git rev-parse --is-inside-work-tree 2>/dev/null', $output, $returnCode);
        return $returnCode === 0;
    }

    private function getUncommittedDiff(): string
    {
        exec('git diff HEAD', $output);
        return implode("\n", $output);
    }

    private function analyzeChanges(string $diff): array
    {
        $lines = explode("\n", $diff);
        $changes = [];
        $modifiedFiles = [];

        foreach ($lines as $line) {
            if (strpos($line, 'diff --git') === 0) {
                $parts = explode(' ', $line);
                $file = substr($parts[3], 2); // Remove 'b/' prefix
                if (!in_array($file, $modifiedFiles)) {
                    $modifiedFiles[] = $file;
                }
            } elseif (strpos($line, 'new file mode') === 0) {
                $file = $modifiedFiles[count($modifiedFiles) - 1];
                $changes[] = "Added $file";
                array_pop($modifiedFiles);
            } elseif (strpos($line, 'deleted file mode') === 0) {
                $file = $modifiedFiles[count($modifiedFiles) - 1];
                $changes[] = "Deleted $file";
                array_pop($modifiedFiles);
            }
        }

        // Any remaining files in $modifiedFiles were modified, not added or deleted
        foreach ($modifiedFiles as $file) {
            $changes[] = "Modified $file";
        }

        return array_unique($changes);
    }
}