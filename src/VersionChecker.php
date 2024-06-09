<?php

namespace Amenophis\Chronos;

use Symfony\Component\Console\Output\OutputInterface;

class VersionChecker
{
    const VERSION_CACHE_FILE = '/.version_cache.json';

    private $currentVersion;
    private $repoUrl;
    private $cacheFilePath;

    public function __construct($currentVersion, $repoUrl)
    {
        $this->currentVersion = $currentVersion;
        $this->repoUrl = $repoUrl;
        $this->cacheFilePath = getenv('HOME') . self::VERSION_CACHE_FILE;
    }

    public function checkForUpdates(OutputInterface $output)
    {
        $cache = $this->getCache();
        $now = new \DateTime();
        $lastChecked = isset($cache['last_checked']) ? new \DateTime($cache['last_checked']) : null;

        if ($lastChecked && $now->diff($lastChecked)->days < 1) {
            $output->writeln("<info>Using cached version information.</info>");
            $latestVersion = $cache['latest_version'];
        } else {
            $output->write("<info>Fetching latest version...</info>");
            $latestVersion = $this->fetchLatestVersion($output);
            $this->updateCache($latestVersion);
            $output->writeln("<info>Done.</info>");
        }

        $normalizedLatestVersion = ltrim($latestVersion, 'v');
        $normalizedCurrentVersion = ltrim($this->currentVersion, 'v');

        if (version_compare($normalizedLatestVersion, $normalizedCurrentVersion, '>')) {
            $output->writeln("<fg=yellow>[UPDATE AVAILABLE]</> New version $latestVersion is available. You are running $this->currentVersion.");
            $output->writeln("<fg=yellow>Check out:</> <href={$this->repoUrl}/releases/latest>{$this->repoUrl}/releases/latest</>");
        } else {
            $output->writeln("<info>You are running the latest version ($this->currentVersion).</info>");
        }
    }

    private function getCache()
    {
        if (file_exists($this->cacheFilePath)) {
            return json_decode(file_get_contents($this->cacheFilePath), true);
        }

        return [];
    }

    private function updateCache($latestVersion)
    {
        $cache = [
            'latest_version' => $latestVersion,
            'last_checked' => (new \DateTime())->format('c')
        ];

        file_put_contents($this->cacheFilePath, json_encode($cache));
    }

    private function fetchLatestVersion(OutputInterface $output)
    {
        $apiUrl = $this->inferApiUrl($this->repoUrl);

        $context = stream_context_create([
            "http" => [
                "header" => "User-Agent: PHP\r\n"
            ]
        ]);

        $response = @file_get_contents($apiUrl, false, $context);

        if ($response === false) {
            $output->writeln("<error>Failed to fetch the latest version. Error: " . error_get_last()['message'] . "</error>");
            return $this->currentVersion;
        }

        $release = json_decode($response, true);

        if (!isset($release['tag_name'])) {
            $output->writeln("<error>Invalid response from GitHub API. Response: " . $response . "</error>");
            return $this->currentVersion;
        }

        return $release['tag_name'];
    }

    private function inferApiUrl($repoUrl)
    {
        // Parse the repo URL to get the parts needed
        $parsedUrl = parse_url($repoUrl);
        if (!isset($parsedUrl['host']) || !isset($parsedUrl['path'])) {
            throw new \InvalidArgumentException("Invalid repository URL: $repoUrl");
        }

        $pathParts = explode('/', trim($parsedUrl['path'], '/'));
        if (count($pathParts) < 2) {
            throw new \InvalidArgumentException("Invalid repository URL: $repoUrl");
        }

        $owner = $pathParts[0];
        $repo = $pathParts[1];

        return "https://api.github.com/repos/$owner/$repo/releases/latest";
    }
}
