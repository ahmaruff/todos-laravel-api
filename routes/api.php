<?php

use App\Commands\ResponseJsonCommand;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ResponseJsonCommand::responseSuccess("success get index", [
        "service" => config('app.service'),
        'version' => config('app.version')
    ]);
});

Route::get('logs', [\App\Http\Controllers\LogController::class, 'index'])->name('logs');

Route::group([
    'prefix' => 'todos',
    'name' => 'todos.',
]. function() {
    Route::get('/', [\App\Http\Controllers\Api\TodoController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Api\TodoController::class, 'store'])->name('store');
    Route::get('/{id}', [\App\Http\Controllers\Api\TodoController::class, 'show'])->name('show');
    Route::put('/{id}', [\App\Http\Controllers\Api\TodoController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\Api\TodoController::class, 'destroy'])->name('destroy');
});
