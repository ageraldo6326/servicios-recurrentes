<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\BusinessCoach\BusinessCoachService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class BusinessCoachServiceTest extends TestCase
{
    public function test_it_sends_only_structured_context_to_openai_and_reads_the_analysis(): void
    {
        config(['services.openai.key' => 'test-key']);
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => "## Resumen\nHay liquidez ajustada.",
            ]),
        ]);

        $analysis = app(BusinessCoachService::class)->analyze([
            'page' => 'dashboard',
            'cobros_pendientes_periodo' => 120,
        ]);

        $this->assertSame("## Resumen\nHay liquidez ajustada.", $analysis);
        Http::assertSent(function ($request): bool {
            return $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['store'] === false
                && $request['input'] !== ''
                && ! str_contains($request['input'], '<table');
        });
    }

    public function test_it_requires_an_api_key(): void
    {
        config(['services.openai.key' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_API_KEY');

        app(BusinessCoachService::class)->analyze(['page' => 'dashboard']);
    }
}
