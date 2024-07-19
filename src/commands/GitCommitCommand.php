<?php

namespace Amenophis\Chronos\commands;

use Amenophis\Chronos\BaseCommand;
use Amenophis\Chronos\LLM\LLMFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Terminal;

class GitCommitCommand extends BaseCommand
{
    protected static $defaultName = 'git:commit';

    protected function configure()
    {
        $this
            ->setDescription('Generate a commit message and create a commit')
            ->setHelp('This command generates a commit message for uncommitted changes, shows it to the user, and creates a commit if confirmed.')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'LLM provider to use (default: none)')
            ->addOption('no-verify', null, InputOption::VALUE_NONE, 'Bypass pre-commit and commit-msg hooks');
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
        $commitMessage = $provider ?
            $this->generateLLMCommitMessage($input, $output, $diff, $provider) :
            $this->generateSimpleCommitMessage($diff);

        if ($commitMessage === false) {
            return Command::FAILURE;
        }

        $this->displayCommitMessage($output, $commitMessage);

        $this->displayChangeSummary($output);

        if ($this->confirmCommit($input, $output)) {
            return $this->createCommit($input, $output, $commitMessage);
        }

        $output->writeln('<comment>Commit aborted.</comment>');
        return Command::SUCCESS;
    }

    private function generateLLMCommitMessage(InputInterface $input, OutputInterface $output, string $diff, string $provider): string|false
    {
        $config = require __DIR__ . '/../../config/llm_config.php';

        if (!isset($config['providers'][$provider])) {
            $output->writeln("<error>Provider '$provider' not configured.</error>");
            return false;
        }

        try {
            $llm = LLMFactory::create($provider, $config['providers'][$provider]);
            $changes = $this->analyzeChanges($diff);
            return $llm->generateCommitMessage($diff, $changes);
        } catch (\Exception $e) {
            $output->writeln('<error>Error generating commit message: ' . $e->getMessage() . '</error>');
            return false;
        }
    }

    private function generateSimpleCommitMessage(string $diff): string
    {
        $changes = $this->analyzeChanges($diff);
        $summary = count($changes) > 1 ? "Multiple changes" : $changes[0];
        $details = implode("\n", $changes);

        return "$summary\n\nDetails:\n$details";
    }

    private function displayCommitMessage(OutputInterface $output, string $commitMessage)
    {
        $output->writeln('');
        $terminal = new Terminal();
        $width = min($terminal->getWidth(), 100);

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

    private function displayChangeSummary(OutputInterface $output)
    {
        $output->writeln('<info>Changes to be committed:</info>');
        exec('git status --short', $statusOutput);
        $output->writeln(implode("\n", $statusOutput));
        $output->writeln('');
    }

    private function confirmCommit(InputInterface $input, OutputInterface $output): bool
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Do you want to create this commit? (y/N) ', false);

        return $helper->ask($input, $output, $question);
    }

    private function createCommit(InputInterface $input, OutputInterface $output, string $commitMessage): int
    {
        // Stage all changes
        exec('git add -A', $addOutput, $addReturnVar);
        if ($addReturnVar !== 0) {
            $output->writeln('<error>Failed to stage changes.</error>');
            return Command::FAILURE;
        }

        $noVerify = $input->getOption('no-verify') ? '--no-verify' : '';
        $command = sprintf('git commit -m %s %s', escapeshellarg($commitMessage), $noVerify);

        exec($command, $commitOutput, $commitReturnVar);

        if ($commitReturnVar === 0) {
            $output->writeln('<info>Commit created successfully:</info>');
            $output->writeln(implode("\n", $commitOutput));
            return Command::SUCCESS;
        } else {
            $output->writeln('<error>Failed to create commit:</error>');
            $output->writeln(implode("\n", $commitOutput));
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

        foreach ($modifiedFiles as $file) {
            $changes[] = "Modified $file";
        }

        return array_unique($changes);
    }
}
