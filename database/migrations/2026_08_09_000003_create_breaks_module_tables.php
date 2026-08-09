<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('break_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedSmallInteger('work_minutes')->default(30);
            $table->unsignedSmallInteger('break_minutes')->default(5);
            $table->boolean('sound_on_break')->default(true);
            $table->boolean('sound_on_return')->default(true);
            $table->boolean('visual_alert')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('break_exercises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->string('category');
            $table->unsignedSmallInteger('recommended_duration_minutes')->default(5);
            $table->string('difficulty')->default('basic');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['user_id', 'is_active']);
        });

        Schema::create('breaks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained('break_exercises')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('notified_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->dateTime('returned_to_work_at')->nullable();
            $table->unsignedSmallInteger('configured_work_minutes')->default(30);
            $table->unsignedSmallInteger('configured_break_minutes')->default(5);
            $table->unsignedSmallInteger('actual_duration_seconds')->nullable();
            $table->string('status');
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'scheduled_at']);
        });

        Schema::create('break_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('break_id')->constrained('breaks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('break_setting_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('break_setting_id')->constrained('break_settings')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('data')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('break_exercises')->insert([
            ['name' => 'Movilidad de cuello', 'description' => 'Movimiento suave para liberar tensión cervical.', 'instructions' => 'Inclina lentamente la cabeza hacia cada lado sin forzar.', 'category' => 'Cuello', 'recommended_duration_minutes' => 2, 'difficulty' => 'basic', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rotación de hombros', 'description' => 'Rotaciones controladas para relajar los hombros.', 'instructions' => 'Haz círculos lentos hacia atrás y luego hacia adelante.', 'category' => 'Hombros', 'recommended_duration_minutes' => 2, 'difficulty' => 'basic', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Estiramiento de brazos', 'description' => 'Estiramiento sencillo para brazos y espalda alta.', 'instructions' => 'Extiende los brazos al frente y luego por encima de la cabeza.', 'category' => 'Brazos', 'recommended_duration_minutes' => 3, 'difficulty' => 'basic', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Estiramiento de muñecas', 'description' => 'Movilidad para manos y muñecas.', 'instructions' => 'Extiende cada brazo y lleva suavemente los dedos hacia atrás.', 'category' => 'Muñecas', 'recommended_duration_minutes' => 2, 'difficulty' => 'basic', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Movilidad de espalda', 'description' => 'Movimiento suave para cambiar la postura.', 'instructions' => 'Gira el torso lentamente a ambos lados manteniendo la espalda cómoda.', 'category' => 'Espalda', 'recommended_duration_minutes' => 3, 'difficulty' => 'basic', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Elevación de talones', 'description' => 'Activación ligera de piernas y pantorrillas.', 'instructions' => 'Eleva los talones y vuelve a bajar lentamente, sujetándote si lo necesitas.', 'category' => 'Piernas', 'recommended_duration_minutes' => 3, 'difficulty' => 'basic', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Marcha en el lugar', 'description' => 'Actividad aeróbica ligera sin desplazamiento.', 'instructions' => 'Marcha suavemente en el lugar a un ritmo cómodo.', 'category' => 'Aeróbico ligero', 'recommended_duration_minutes' => 5, 'difficulty' => 'basic', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Caminata corta', 'description' => 'Caminata breve para cambiar de postura.', 'instructions' => 'Camina unos minutos dentro de un espacio seguro y cómodo.', 'category' => 'Caminata', 'recommended_duration_minutes' => 5, 'difficulty' => 'basic', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sentarse y levantarse', 'description' => 'Movimiento controlado para activar las piernas.', 'instructions' => 'Levántate y vuelve a sentarte lentamente usando una silla estable.', 'category' => 'Piernas', 'recommended_duration_minutes' => 3, 'difficulty' => 'basic', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Movilidad general', 'description' => 'Secuencia breve de movimientos cómodos.', 'instructions' => 'Mueve hombros, brazos, espalda y piernas sin forzar.', 'category' => 'Movilidad general', 'recommended_duration_minutes' => 5, 'difficulty' => 'basic', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Respiración y relajación', 'description' => 'Pausa de respiración consciente.', 'instructions' => 'Respira lentamente, relaja los hombros y mantén un ritmo cómodo.', 'category' => 'Respiración', 'recommended_duration_minutes' => 3, 'difficulty' => 'basic', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('break_setting_histories');
        Schema::dropIfExists('break_histories');
        Schema::dropIfExists('breaks');
        Schema::dropIfExists('break_exercises');
        Schema::dropIfExists('break_settings');
    }
};
