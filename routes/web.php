
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;

// Redirect homepage to Employee Record
Route::get('/', function () {
    return redirect()->route('employees.index');
});

// Employee CRUD routes
Route::resource('employees', EmployeeController::class);
