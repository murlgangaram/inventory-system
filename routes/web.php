
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ComputerController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\ReportController;

// Redirect homepage to Employee Record
Route::get('/', function () {
    return redirect()->route('employees.index');
});

// Employee Management
Route::resource('employees', EmployeeController::class);

// Computer Management
Route::resource('computers', ComputerController::class);

Route::resource('printers', PrinterController::class)
    ->only(['index', 'store', 'update', 'destroy']);

    // Equipment Assignment Routes
Route::resource('assignments', AssignmentController::class)
    ->only(['index', 'store']);

Route::post(
    '/assignments/{assignment}/return',
    [AssignmentController::class, 'returnEquipment']
)->name('assignments.return');

Route::get('/reports', [ReportController::class, 'index'])
    ->name('reports.index');
