<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController; 
use App\Http\Controllers\API\EmployeeController; 


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::post('register', [AuthController::class,"register"]);
Route::post('login', [AuthController::class,"login"]);
Route::middleware("auth:api")->group(function(){
    Route::post("employees",[EmployeeController::class,"index"]);
    Route::post("timerecords",[EmployeeController::class,"getTimeRecord"]);
    Route::post("updateOrCreateTimesheet",[EmployeeController::class,"updateOrCreateTimesheet"]);


});