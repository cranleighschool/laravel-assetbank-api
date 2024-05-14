<?php

use App\Http\Controllers\AssetBankController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('asset/{id}', [AssetBankController::class, 'getAssetByID']);
Route::put('asset/{id}', [AssetBankController::class, 'updateAssetByID']);
Route::put('migrate/{id}', [AssetBankController::class, 'tagAsMigrated']);
Route::get('/me', function (Request $request) {
    return $request->user();
});
