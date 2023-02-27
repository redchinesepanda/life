<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogEntryController;
use App\Http\Controllers\AuthController;

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

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/new', [LogEntryController::class, 'new']);

Route::post('/state', [LogEntryController::class, 'state']);

Route::post('/period', [LogEntryController::class, 'period'])->middleware('auth:sanctum');

Route::post('/custom/{from}/{to}', [LogEntryController::class, 'custom'])
    ->middleware('auth:sanctum')
    ->where('from', '[0-9-]+')
    ->where('to', '[0-9-]+');
