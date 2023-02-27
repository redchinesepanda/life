<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogEntryController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/new', [LogEntryController::class, 'new']);

Route::post('/state', [LogEntryController::class, 'state']);

Route::post('/period', [LogEntryController::class, 'period']);

Route::post('/custom/{from}/{to}', [LogEntryController::class, 'custom'])->where('from', '\d{4}-\d{2}-\d{2}')->where('to', '\d{4}-\d{2}-\d{2}');
