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
