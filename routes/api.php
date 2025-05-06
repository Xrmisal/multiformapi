<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FormController;

Route::post('/start', [FormController::class, 'start']);
Route::put('/update/{id}', [FormController::class, 'updateStep']);
Route::get('/status/{id}', [FormController::class, 'checkStatus']);