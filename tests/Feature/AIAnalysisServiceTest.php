<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AiAnalysisType;
use App\Models\AiUsageLog;
use App\Models\User;
use App\Services\AIAnalysis\AIAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AIAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_internal_advisor_is_available_in_the_authenticated_application_layout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Asesor IA');
    }

    public function test_it_redacts_sensitive_patterns_before_sending_the_analysis(): void
    {
        config(['services.openai.key' => 'test-key']);
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response(['output_text' => "## Resumen\nTodo en orden."]),
        ]);
        $user = User::factory()->create();

        $analysis = app(AIAnalysisService::class)->analyze(
            $user,
            'Cobro pendiente. api_key=super-secret-value-123456789',
            '¿Qué debo priorizar?',
            AiAnalysisType::NextActions,
        );

        $this->assertSame("## Resumen\nTodo en orden.", $analysis);
        Http::assertSent(function ($request): bool {
            return $request->hasHeader('Authorization', 'Bearer test-key')
                && $request['store'] === false
                && str_contains($request['input'], '[DATO_SENSIBLE_OCULTO]')
                && ! str_contains($request['input'], 'super-secret-value-123456789');
        });
        $this->assertDatabaseHas('ai_usage_logs', [
            'user_id' => $user->id,
            'analysis_type' => AiAnalysisType::NextActions->value,
            'status' => 'completed',
        ]);
        $this->assertSame(1, AiUsageLog::query()->count());
    }

    public function test_authenticated_users_can_submit_an_internal_analysis_without_storing_pasted_content(): void
    {
        config(['services.openai.key' => 'test-key']);
        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response(['output_text' => "## Próximas acciones\n- Contactar al cliente."]),
        ]);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('ai-analysis.panel')
            ->call('openChat')
            ->set('privacyAccepted', true)
            ->set('content', 'Dato interno que no debe persistir')
            ->set('question', '¿Cuál es el siguiente paso?')
            ->call('analyze')
            ->assertSet('content', '')
            ->assertSet('question', '')
            ->assertSet('error', '')
            ->assertSee('Contactar al cliente');

        $usageLog = AiUsageLog::query()->sole();
        $this->assertArrayNotHasKey('content', $usageLog->getAttributes());
        $this->assertArrayNotHasKey('analysis', $usageLog->getAttributes());
    }
}
