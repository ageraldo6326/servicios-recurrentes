<?php

declare(strict_types=1);

namespace App\Services\BusinessCoach;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class BusinessCoachService
{
    /** @param array<string, mixed> $context */
    public function analyze(array $context): string
    {
        $apiKey = (string) config('services.openai.key');
        if ($apiKey === '') {
            throw new RuntimeException('Configura OPENAI_API_KEY para activar Business Coach.');
        }

        $page = (string) ($context['page'] ?? 'dashboard');
        $promptPath = base_path('prompts/'.$this->promptName($page).'.md');
        $instructions = is_file($promptPath) ? file_get_contents($promptPath) : $this->defaultPrompt();
        unset($context['_source_version']);
        $payload = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $proxy = trim((string) config('services.openai.proxy', ''));
        $httpOptions = ['proxy' => $proxy];

        if ($proxy === '') {
            $httpOptions['curl'] = [CURLOPT_NOPROXY => '*'];
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->connectTimeout((int) config('services.openai.connect_timeout', 10))
            ->timeout((int) config('services.openai.timeout', 60))
            ->retry(2, 1000)
            ->withOptions($httpOptions)
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.model', 'gpt-5-mini'),
                'instructions' => $instructions,
                'input' => "Analiza este contexto estructurado y responde en español:\n{$payload}",
                'store' => false,
                'text' => ['verbosity' => 'low'],
            ]);

        if ($response->failed()) {
            Log::warning('Business Coach API error', ['status' => $response->status()]);
            throw new RuntimeException('No fue posible generar el análisis en este momento.');
        }

        $text = $response->json('output_text');
        if (is_string($text) && trim($text) !== '') {
            return trim($text);
        }

        foreach ((array) $response->json('output', []) as $item) {
            foreach ((array) ($item['content'] ?? []) as $content) {
                if (is_string($content['text'] ?? null) && trim($content['text']) !== '') {
                    return trim($content['text']);
                }
            }
        }

        throw new RuntimeException('OpenAI no devolvió un análisis legible.');
    }

    private function promptName(string $page): string
    {
        return match ($page) {
            'clientes' => 'clientes',
            'servicios' => 'servicios',
            'servicios_contratados' => 'servicios-contratados',
            'cobros_y_pagos' => 'cobros',
            'proveedores' => 'proveedores',
            'gestiones_y_seguimiento' => 'gestiones',
            'compromisos' => 'compromisos',
            default => 'dashboard',
        };
    }

    private function defaultPrompt(): string
    {
        return 'Actúa como director financiero y de operaciones de una empresa de servicios recurrentes. Sé breve, concreto y práctico. Usa títulos: Resumen, Riesgos, Oportunidades, Recomendaciones y Acciones sugeridas. Máximo 600 palabras. No inventes datos.';
    }
}
