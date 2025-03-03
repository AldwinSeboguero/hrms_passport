<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
Route::get('/', function () {
    return view('welcome');
});

Route::get('register', [AuthController::class,"register"]);
