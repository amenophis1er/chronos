<?php

namespace Amenophis\Chronos\commands;

use Amenophis\Chronos\BaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GitDiffCommand extends BaseCommand
{
    protected static $defaultName = 'git:diff';

    protected function configure()
    {
        $this
            ->setDescription('Create a diff file of the project.')
            ->setHelp('This command creates a diff file of the project based on specified commits or branches.')
            ->addArgument('from', InputArgument::OPTIONAL, 'The starting commit or branch', 'HEAD~1')
            ->addArgument('to', InputArgument::OPTIONAL, 'The ending commit or branch', 'HEAD')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file name', 'project_diff.patch');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $from = $input->getArgument('from');
        $to = $input->getArgument('to');
        $outputFile = $input->getOption('output');

        $command = "git diff {$from} {$to} > {$outputFile}";

        $output->writeln("<info>Running command:</info> $command");
        exec($command, $cmdOutput, $returnVar);

        if ($returnVar !== 0) {
            $output->writeln("<error>Error creating diff file:</error>");
            $output->writeln(implode("\n", $cmdOutput));
            return Command::FAILURE;
        }

        $output->writeln("<info>Diff file created successfully: {$outputFile}</info>");
        return Command::SUCCESS;
    }
}