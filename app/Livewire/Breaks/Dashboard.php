<?php

namespace App\Livewire\Breaks;

use App\Actions\ConfigureBreakSettings;
use App\Actions\CreateBreakExercise;
use App\Actions\DeleteBreakExercise;
use App\Actions\UpdateBreakExercise;
use App\Models\BreakExercise;
use App\Models\BreakSetting;
use App\Services\BreakCycleService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    use WithFileUploads;

    public bool $isEnabled = true;

    public int $workMinutes = 30;

    public int $breakMinutes = 5;

    public bool $soundOnBreak = true;

    public bool $soundOnReturn = true;

    public bool $visualAlert = true;

    public $customSound;

    public ?string $customSoundUrl = null;

    public ?int $editingExerciseId = null;

    public string $exerciseName = '';

    public string $exerciseDescription = '';

    public string $exerciseInstructions = '';

    public string $exerciseCategory = 'Movilidad general';

    public int $exerciseDuration = 5;

    public string $exerciseDifficulty = 'basic';

    public bool $exerciseActive = true;

    public function mount(BreakCycleService $cycle): void
    {
        $this->loadSettings($cycle->settings(auth()->user()));
    }

    public function saveSettings(ConfigureBreakSettings $configure, BreakCycleService $cycle): void
    {
        $validated = $this->validate([
            'isEnabled' => ['boolean'],
            'workMinutes' => ['required', 'integer', 'in:20,30,45,60,90'],
            'breakMinutes' => ['required', 'integer', 'in:2,5,10,15'],
            'soundOnBreak' => ['boolean'],
            'soundOnReturn' => ['boolean'],
            'visualAlert' => ['boolean'],
            'customSound' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:10240'],
        ]);

        $customSoundPath = $this->customSound?->store('break-sounds/'.$this->userId(), 'public')
            ?? $this->currentSoundPath($cycle);

        $configure->execute(auth()->user(), [
            'is_enabled' => $validated['isEnabled'],
            'work_minutes' => $validated['workMinutes'],
            'break_minutes' => $validated['breakMinutes'],
            'sound_on_break' => $validated['soundOnBreak'],
            'sound_on_return' => $validated['soundOnReturn'],
            'visual_alert' => $validated['visualAlert'],
            'custom_sound_path' => $customSoundPath,
        ]);

        $this->customSound = null;
        $this->customSoundUrl = $customSoundPath ? url('storage/'.ltrim($customSoundPath, '/')) : null;
        session()->flash('success', 'Configuración de pausas actualizada.');
    }

    public function clearCustomSound(ConfigureBreakSettings $configure): void
    {
        $configure->execute(auth()->user(), ['custom_sound_path' => null]);
        $this->customSound = null;
        $this->customSoundUrl = null;
        session()->flash('success', 'Sonido personalizado eliminado.');
    }

    public function saveExercise(CreateBreakExercise $create, UpdateBreakExercise $update): void
    {
        $validated = $this->validate([
            'exerciseName' => ['required', 'string', 'max:255'],
            'exerciseDescription' => ['nullable', 'string', 'max:2000'],
            'exerciseInstructions' => ['nullable', 'string', 'max:5000'],
            'exerciseCategory' => ['required', 'string', 'max:100'],
            'exerciseDuration' => ['required', 'integer', 'between:1,60'],
            'exerciseDifficulty' => ['required', 'in:basic,gentle,moderate'],
            'exerciseActive' => ['boolean'],
        ]);

        $attributes = [
            'name' => $validated['exerciseName'],
            'description' => $validated['exerciseDescription'],
            'instructions' => $validated['exerciseInstructions'],
            'category' => $validated['exerciseCategory'],
            'recommended_duration_minutes' => $validated['exerciseDuration'],
            'difficulty' => $validated['exerciseDifficulty'],
            'is_active' => $validated['exerciseActive'],
        ];

        if ($this->editingExerciseId !== null) {
            $exercise = BreakExercise::query()->where('user_id', auth()->id())->findOrFail($this->editingExerciseId);
            $update->execute(auth()->user(), $exercise, $attributes);
            session()->flash('success', 'Ejercicio actualizado.');
        } else {
            $create->execute(auth()->user(), $attributes);
            session()->flash('success', 'Ejercicio creado.');
        }

        $this->resetExerciseForm();
    }

    public function editExercise(int $exerciseId): void
    {
        $exercise = BreakExercise::query()->where('user_id', auth()->id())->findOrFail($exerciseId);

        $this->editingExerciseId = $exercise->id;
        $this->exerciseName = $exercise->name;
        $this->exerciseDescription = $exercise->description ?? '';
        $this->exerciseInstructions = $exercise->instructions ?? '';
        $this->exerciseCategory = $exercise->category;
        $this->exerciseDuration = $exercise->recommended_duration_minutes;
        $this->exerciseDifficulty = $exercise->difficulty;
        $this->exerciseActive = $exercise->is_active;
    }

    public function deleteExercise(int $exerciseId, DeleteBreakExercise $delete): void
    {
        $exercise = BreakExercise::query()->where('user_id', auth()->id())->findOrFail($exerciseId);
        $delete->execute(auth()->user(), $exercise);

        if ($this->editingExerciseId === $exerciseId) {
            $this->resetExerciseForm();
        }

        session()->flash('success', 'Ejercicio eliminado.');
    }

    public function resetExerciseForm(): void
    {
        $this->reset(['editingExerciseId', 'exerciseName', 'exerciseDescription', 'exerciseInstructions']);
        $this->exerciseCategory = 'Movilidad general';
        $this->exerciseDuration = 5;
        $this->exerciseDifficulty = 'basic';
        $this->exerciseActive = true;
        $this->resetValidation();
    }

    public function render(BreakCycleService $cycle): View
    {
        $userId = (int) auth()->id();
        $snapshot = $cycle->synchronize(auth()->user());
        $exercises = BreakExercise::query()
            ->where(function ($query) use ($userId): void {
                $query->whereNull('user_id')->orWhere('user_id', $userId);
            })
            ->orderBy('user_id')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('livewire.breaks.dashboard', [
            'snapshot' => $snapshot,
            'exercises' => $exercises,
            'workOptions' => [20, 30, 45, 60, 90],
            'breakOptions' => [2, 5, 10, 15],
            'customSoundUrl' => $this->customSoundUrl,
        ]);
    }

    private function loadSettings(BreakSetting $settings): void
    {
        $this->isEnabled = $settings->is_enabled;
        $this->workMinutes = $settings->work_minutes;
        $this->breakMinutes = $settings->break_minutes;
        $this->soundOnBreak = $settings->sound_on_break;
        $this->soundOnReturn = $settings->sound_on_return;
        $this->visualAlert = $settings->visual_alert;
        $this->customSoundUrl = $settings->custom_sound_path === null
            ? null
            : url('storage/'.ltrim($settings->custom_sound_path, '/'));
    }

    private function currentSoundPath(BreakCycleService $cycle): ?string
    {
        return $cycle->settings(auth()->user())->custom_sound_path;
    }

    private function userId(): int
    {
        return (int) auth()->id();
    }
}
