<?php

namespace Amenophis\Chronos;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GitCommand extends Command
{
    protected static $defaultName = 'git:tag-push';

    protected function configure()
    {
        $this
            ->setDescription('Create a Git tag and push it to the origin.')
            ->setHelp('This command allows you to create a Git tag and push it to the remote origin.')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to tag')
            ->addArgument('message', InputArgument::OPTIONAL, 'The message for the tag', 'Release version')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force push the tag');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = escapeshellarg($input->getArgument('version'));
        $message = escapeshellarg($input->getArgument('message') . ' ' . $version);
        $force = $input->getOption('force') ? '--force' : '';

        $commands = [
            "git tag -a $version -m $message",
            "git push origin $force $version"
        ];

        foreach ($commands as $command) {
            $output->writeln("<info>Running command:</info> $command");
            exec($command, $cmdOutput, $returnVar);
            if ($returnVar !== 0) {
                $output->writeln("<error>Error running command:</error> $command");
                $output->writeln(implode("\n", $cmdOutput));
                return Command::FAILURE;
            }
            $output->writeln(implode("\n", $cmdOutput));
        }

        $output->writeln("<info>Tag $version created and pushed successfully.</info>");
        return Command::SUCCESS;
    }
}
