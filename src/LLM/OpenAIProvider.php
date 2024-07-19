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

    public function generateCommitMessage(string $diff, array $categorizedChanges): string
    {
        $url = $this->baseUrl . 'chat/completions';

        $changesSummary = $this->formatChangesSummary($categorizedChanges);

        $data = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant that generates detailed and descriptive Git commit messages based on the provided diff and summary of changes.'],
                ['role' => 'user', 'content' => "Generate a detailed and descriptive commit message for the following changes:\n\nSummary of changes:\n$changesSummary\nFull diff:\n$diff"],
            ],
            'temperature' => 0.7,
            'max_tokens' => 500,
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

    private function formatChangesSummary(array $changes): string
    {
        $summary = '';
        foreach ($changes as $change) {
            $summary .= "- $change\n";
        }
        return $summary;
    }
}