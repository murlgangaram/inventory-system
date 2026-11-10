<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ComputerController;

Route::get('/', function () {
    return redirect()->route('employees.index');
});

Route::resource('employees', EmployeeController::class);

Route::resource('computers', ComputerController::class)
    ->only(['index', 'store', 'update', 'destroy']);
