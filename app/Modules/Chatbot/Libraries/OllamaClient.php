<?php

namespace Modules\Chatbot\Libraries;

use Modules\Chatbot\Config\Ollama;

class OllamaClient
{
    protected Ollama $config;

    public function __construct()
    {
        $this->config = config(Ollama::class);
    }

    public function chat(array $messages): string
    {
        $systemMessage = array_shift($messages);

        $payload = [
            'model'   => $this->config->model,
            'stream'  => false,
            'options' => $this->config->options,
            'messages' => array_merge(
                [$systemMessage],
                $messages
            ),
        ];

        $response = $this->post('/api/chat', $payload);

        if (isset($response['message']['content'])) {
            return $response['message']['content'];
        }

        if (isset($response['error'])) {
            log_message('error', 'Ollama API error: ' . $response['error']);
            return 'Sorry, I encountered an error. Please try again.';
        }

        return 'I\'m not sure how to respond to that.';
    }

    public function isAvailable(): bool
    {
        try {
            $response = $this->get('/api/tags');
            return $response !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function get(string $endpoint): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->config->host . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            return null;
        }

        return json_decode($response, true) ?? null;
    }

    protected function post(string $endpoint, array $data): ?array
    {
        $json = json_encode($data);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->config->host . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json),
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return null;
        }

        return json_decode($response, true) ?? null;
    }
}
