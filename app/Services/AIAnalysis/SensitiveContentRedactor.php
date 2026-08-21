<?php

declare(strict_types=1);

namespace App\Services\AIAnalysis;

final class SensitiveContentRedactor
{
    public function redact(string $content): string
    {
        $patterns = [
            '/-----BEGIN [A-Z ]*PRIVATE KEY-----.*?-----END [A-Z ]*PRIVATE KEY-----/si',
            '/\b(?:sk|rk|pk)_[A-Za-z0-9_-]{16,}\b/',
            '/\b(?:ghp|github_pat)_[A-Za-z0-9_]{16,}\b/i',
            '/\bBearer\s+[A-Za-z0-9._~+\/-]{16,}\b/i',
            '/\b(password|contrase(?:ñ|n)a|api[_ -]?key|token|secret)\s*[:=]\s*[^\s,;]+/i',
        ];

        return (string) preg_replace($patterns, '[DATO_SENSIBLE_OCULTO]', $content);
    }
}
