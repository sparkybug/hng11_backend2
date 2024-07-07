<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrganisationController;
use App\Http\Middleware\JWTAuthenticate;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware([JWTAuthenticate::class])->group(function () {
    Route::get('/users/{id}', [AuthController::class, 'show']);

    // Organisations Routes
    Route::get('/organisations', [OrganisationController::class, 'index']);

    Route::get('/organisations/{orgId}', [OrganisationController::class, 'show']);


    Route::post('/organisations/', [OrganisationController::class, 'store']);

    Route::post('/organisations/{orgId}/users', [OrganisationController::class, 'addUser']);

});
