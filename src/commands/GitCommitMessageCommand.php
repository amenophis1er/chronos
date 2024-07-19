            ->setDescription('Generate a commit message for uncommitted changes using an LLM.')
            ->setHelp('This command analyzes the current Git repository and generates a commit message for uncommitted changes using a configured LLM provider.')
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'LLM provider to use (default: openai)');
        foreach ($changes as $type => $files) {
        $provider = $input->getOption('provider') ?? $config['default_provider'];
            $commitMessage = $llm->generateCommitMessage($diff, $changes);
        exec('git add -N .', $output, $returnCode); // Stage new files without adding them
        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to stage new files');
        }

        $diff = implode("\n", $output);

        exec('git reset', $output, $returnCode); // Unstage the new files
        if ($returnCode !== 0) {
            throw new \RuntimeException('Failed to unstage new files');
        }

        return $diff;
        $changes = [
            'Added' => [],
            'Modified' => [],
            'Deleted' => []
        ];
        $currentFile = null;
                $currentFile = substr($parts[3], 2); // Remove 'b/' prefix
                // Reset the file status for each new file encountered
                $fileStatus = 'Modified';
                $fileStatus = 'Added';
                $fileStatus = 'Deleted';
            }

            if ($currentFile && !in_array($currentFile, $changes[$fileStatus])) {
                $changes[$fileStatus][] = $currentFile;
        // Remove duplicates and empty categories
        foreach ($changes as $status => $files) {
            $changes[$status] = array_unique($files);
            if (empty($changes[$status])) {
                unset($changes[$status]);
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