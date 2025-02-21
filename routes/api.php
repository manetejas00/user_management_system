<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PersonController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/persons', [PersonController::class, 'index']);
    Route::put('/persons/{id}', [PersonController::class, 'update']);
    Route::delete('/persons/{id}', [PersonController::class, 'destroy']);
    Route::post('/persons/bulk-delete', [PersonController::class, 'bulkDelete']);
    Route::post('/persons/bulk-create', [PersonController::class, 'bulkCreate']);
});
