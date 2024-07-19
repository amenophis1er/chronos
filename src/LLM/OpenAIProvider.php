<?php

namespace Amenophis\Chronos\LLM;

class OpenAIProvider
{
    private $apiKey;
    private $model;
    private $baseUrl = 'https://api.openai.com/v1/';

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->model = $config['model'];
    }

    public function generateCommitMessage(string $diff, array $changes): string
    {
        $url = $this->baseUrl . 'chat/completions';

        $changesSummary = $this->formatChangesSummary($changes);

        $data = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant that generates Git commit messages. The message should have a concise first line (50-72 characters) summarizing the change, followed by a blank line and a more detailed explanation. Use plain text format without markdown.'],
                ['role' => 'user', 'content' => "Generate a commit message for the following changes:\n\nSummary of changes:\n$changesSummary\nFull diff:\n$diff"],
            ],
            'temperature' => 0.7,
            'max_tokens' => 350,
        ];

        $ch = \curl_init($url);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_POST, true);
        \curl_setopt($ch, CURLOPT_POSTFIELDS, \json_encode($data));
        \curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);

        $response = \curl_exec($ch);
        $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (\curl_errno($ch)) {
            throw new \RuntimeException('Curl error: ' . \curl_error($ch));
        }

        \curl_close($ch);

        if ($httpCode !== 200) {
            throw new \RuntimeException("HTTP Error: $httpCode, Response: $response");
        }

        $result = \json_decode($response, true);
        if (\json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to parse JSON response: ' . \json_last_error_msg());
        }

        if (!isset($result['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Unexpected response structure');
        }

        return $result['choices'][0]['message']['content'];
    }

    private function formatChangesSummary($changes): string
    {
        if (is_string($changes)) {
            return $changes; // Return as-is if it's already a string
        }

        $summary = '';
        foreach ($changes as $type => $files) {
            if (is_array($files) && !empty($files)) {
                $summary .= "$type:\n";
                foreach ($files as $file) {
                    $summary .= "  - $file\n";
                }
            } elseif (is_string($files)) {
                $summary .= "$type: $files\n";
            }
        }
        return $summary;
    }
}