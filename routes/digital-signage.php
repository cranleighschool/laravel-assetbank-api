<?php

use App\Http\Controllers\DigitalSignageController;
use Illuminate\Support\Facades\Route;

Route::get('testme', [DigitalSignageController::class, 'setupSenior']);
