<?php

namespace Modules\Chatbot\Config;

use CodeIgniter\Config\BaseConfig;

class Ollama extends BaseConfig
{
    public string $host = 'http://localhost:11434';

    public string $model = 'gemma:2b';

    public array $options = [
        'temperature' => 0.7,
        'top_p'       => 0.9,
    ];

    public string $systemPrompt = 'You are a helpful and friendly AI assistant. Keep your responses concise, friendly, and informative.';
}
