<?php

namespace Amenophis\Chronos\LLM;

class LLMFactory
{
    public static function create(string $provider, array $config)
    {
        switch ($provider) {
            case 'openai':
                return new OpenAIProvider($config);
            // Add cases for other providers as needed
            default:
                throw new \InvalidArgumentException("Unsupported LLM provider: $provider");
        }
    }
}