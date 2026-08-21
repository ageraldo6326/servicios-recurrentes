<?php

declare(strict_types=1);

namespace App\Livewire\AIAnalysis;

use App\Enums\AiAnalysisType;
use App\Models\User;
use App\Services\AIAnalysis\AIAnalysisService;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class Panel extends Component
{
    public bool $open = false;

    public bool $loading = false;

    public bool $privacyAccepted = false;

    #[Validate]
    public string $content = '';

    #[Validate]
    public string $question = '';

    #[Validate]
    public string $analysisType = AiAnalysisType::Summary->value;

    /** @var list<array{id: string, type: string, question: string, analysis: string}> */
    public array $messages = [];

    public string $error = '';

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:'.config('services.ai_analysis.max_content_characters', 20000)],
            'question' => ['nullable', 'string', 'max:'.config('services.ai_analysis.max_question_characters', 2000)],
            'analysisType' => ['required', 'string', 'in:'.implode(',', array_map(
                static fn (AiAnalysisType $type): string => $type->value,
                AiAnalysisType::cases(),
            ))],
            'privacyAccepted' => ['accepted'],
        ];
    }

    public function openChat(): void
    {
        abort_unless(auth()->check(), 403);
        $this->open = true;
        $this->error = '';
    }

    public function close(): void
    {
        $this->open = false;
        $this->loading = false;
    }

    public function newConversation(): void
    {
        $this->content = '';
        $this->question = '';
        $this->analysisType = AiAnalysisType::Summary->value;
        $this->messages = [];
        $this->error = '';
        $this->resetValidation();
    }

    public function analyze(AIAnalysisService $service): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $this->validate();
        $this->loading = true;
        $this->error = '';

        try {
            $type = AiAnalysisType::from($this->analysisType);
            $question = trim($this->question);
            $analysis = $service->analyze($user, $this->content, $question, $type);

            $this->messages[] = [
                'id' => (string) Str::uuid(),
                'type' => $type->label(),
                'question' => $question,
                'analysis' => $analysis,
            ];

            // El contenido pegado no se conserva en el estado del chat ni en la base de datos.
            $this->content = '';
            $this->question = '';
        } catch (\RuntimeException $exception) {
            $this->error = $exception->getMessage();
        } catch (\Throwable) {
            $this->error = 'No fue posible procesar el análisis en este momento. Intenta nuevamente.';
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        $messages = array_map(function (array $message): array {
            $message['html'] = Str::markdown($message['analysis'], [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);

            return $message;
        }, $this->messages);

        return view('livewire.ai-analysis.panel', [
            'analysisTypes' => AiAnalysisType::cases(),
            'renderedMessages' => $messages,
        ]);
    }
}
