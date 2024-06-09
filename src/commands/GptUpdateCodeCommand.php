<?php

namespace Amenophis\Chronos\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints\Json as JsonConstraint;
use Symfony\Component\Validator\Validation;

class GptUpdateCodeCommand extends Command
{
    protected static $defaultName = 'gpt:update-code';
    private $filesystem;

    public function __construct()
    {
        parent::__construct();
        $this->filesystem = new Filesystem();
    }

    protected function configure()
    {
        $this
            ->setDescription('Update code in this folder based on instruct.json')
            ->setHelp('This command allows you to update the file system according to instruct.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentPath = getcwd();
        $instructFilePath = $currentPath . '/instruct.json';

        $output->writeln([
            '<fg=green>Update File System based on instruct.json</>',
            '----------------'
        ]);

        if (!$this->filesystem->exists($instructFilePath)) {
            $output->writeln('<fg=red>File instruct.json not found in the current directory.</>');
            return Command::FAILURE;
        }

        try {
            $instructContent = file_get_contents($instructFilePath);
            $validator = Validation::createValidator();
            $jsonConstraint = new JsonConstraint();

            $violations = $validator->validate($instructContent, $jsonConstraint);
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $output->writeln('<fg=red>Invalid JSON: ' . $violation->getMessage() . '</>');
                }
                return Command::FAILURE;
            }

            $instructData = json_decode($instructContent, true);

            if (!$this->validateFileSystemOps($instructData, $output)) {
                $output->writeln('<fg=red>Invalid instruct.json structure according to FileSystemOps standard.</>');
                return Command::FAILURE;
            }

            $this->performFileSystemOperations($instructData, $output);

            $output->writeln('<fg=green>File system updated successfully.</>');

            return Command::SUCCESS;

        } catch (IOExceptionInterface $exception) {
            $output->writeln('<fg=red>An error occurred while reading instruct.json: ' . $exception->getMessage() . '</>');
        } catch (\Exception $e) {
            $output->writeln('<fg=red>An error occurred: ' . $e->getMessage() . '</>');
        }

        return Command::FAILURE;
    }

    private function validateFileSystemOps(array $data, OutputInterface $output): bool
    {
        foreach ($data as $index => $operation) {
            if (!isset($operation['path']) || !is_string($operation['path'])) {
                $output->writeln('<fg=red>Validation Error: Missing or invalid "path" at index ' . $index . '.</>');
                return false;
            }

            if (!isset($operation['action']) || !in_array($operation['action'], ['create', 'delete'], true)) {
                $output->writeln('<fg=red>Validation Error: Missing or invalid "action" at index ' . $index . '. Allowed actions are "create" and "delete".</>');
                return false;
            }

            if (!isset($operation['type']) || !in_array($operation['type'], ['file', 'directory'], true)) {
                $output->writeln('<fg=red>Validation Error: Missing or invalid "type" at index ' . $index . '. Allowed types are "file" and "directory".</>');
                return false;
            }

            if ($operation['action'] === 'create' && $operation['type'] === 'file' && (!isset($operation['content']) || !is_string($operation['content']))) {
                $output->writeln('<fg=red>Validation Error: Missing or invalid "content" for "create" action of type "file" at index ' . $index . '.</>');
                return false;
            }

            if (isset($operation['permissions']) && !preg_match('/^[0-7]{3,4}$/', $operation['permissions'])) {
                $output->writeln('<fg=red>Validation Error: Invalid "permissions" format at index ' . $index . '. Expected format is a three or four digit octal number.</>');
                return false;
            }
        }

        return true;
    }

    private function performFileSystemOperations(array $data, OutputInterface $output)
    {
        foreach ($data as $index => $operation) {
            $path = $operation['path'];
            $permissions = isset($operation['permissions']) ? octdec($operation['permissions']) : null;

            try {
                // Remove existing file or directory before creating
                if ($this->filesystem->exists($path)) {
                    $output->writeln('<fg=yellow>Removing existing ' . $operation['type'] . ' at path ' . $path . '.</>');
                    $this->filesystem->remove($path);
                }

                switch ($operation['action']) {
                    case 'create':
                        if ($operation['type'] === 'file') {
                            $this->filesystem->dumpFile($path, $operation['content'] ?? '');
                            chmod($path, $permissions ?? 0644); // Ensure default file permissions
                            $output->writeln('<fg=green>Created file: ' . $path . ' with permissions ' . decoct($permissions ?? 0644) . '</>');
                        } elseif ($operation['type'] === 'directory') {
                            $this->filesystem->mkdir($path, $permissions ?? 0755); // Create directory with specified or default permissions
                            $output->writeln('<fg=green>Created directory: ' . $path . ' with permissions ' . decoct($permissions ?? 0755) . '</>');
                        }
                        break;

                    case 'delete':
                        // Already handled by the existence check and removal at the start of the loop
                        $output->writeln('<fg=red>Deleted ' . $operation['type'] . ': ' . $path . '</>');
                        break;

                    default:
                        $output->writeln('<fg=red>Unknown action "' . $operation['action'] . '" at index ' . $index . '.</>');
                        break;
                }
            } catch (IOExceptionInterface $exception) {
                $output->writeln('<fg=red>An error occurred during ' . $operation['action'] . ' operation at index ' . $index . ': ' . $exception->getMessage() . '</>');
            }
        }
    }
}
