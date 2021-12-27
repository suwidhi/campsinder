<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AuthenticationController;

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

Route::get('/Csrftokens', function (Request $request) {
    return response(csrf_token());
});

Route::post('/tokens/create', function (Request $request) {
    $token = $request->user()->createToken($request->token_name);
    // $token = $user->createToken('token_name')->plainTextToken;
    return ['token' => $token->plainTextToken];
});

Route::post('/createUser', [AuthenticationController::class, 'createUsers']);
Route::post('/generateEmailOtp', [AuthenticationController::class, 'generateEmailOtp']);
Route::post('/verifyOtpCode', [AuthenticationController::class, 'verifyOtpCode']);