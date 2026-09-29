<?php

use App\Http\Controllers\Api\V1\ServerCallActivityController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('server-call-activity', ServerCallActivityController::class)
        ->middleware(['throttle:call-activity-reports', 'call-activity.auth'])
        ->name('api.v1.server-call-activity.store');
});
