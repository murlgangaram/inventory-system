<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Computer;
use App\Models\Employee;
use App\Models\Printer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    // Display assignment records
    public function index()
    {
        $assignments = Assignment::with(['employee', 'equipment'])
            ->latest()
            ->paginate(10);

        $employees = Employee::orderBy('full_name')->get();

        $computers = Computer::orderBy('computer_name')->get();

        $printers = Printer::orderBy('printer_name')->get();

        return view('assignments.index', compact(
            'assignments',
            'employees',
            'computers',
            'printers'
        ));
    }

    // Assign equipment to an employee
    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipment_type' => [
                'required',
                Rule::in(['computer', 'printer']),
            ],
            'equipment_id' => ['required', 'integer'],
            'employee_id' => [
                'required',
                'exists:employees,id',
            ],
            'assigned_at' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        // Determine equipment model
        if ($validated['equipment_type'] === 'computer') {
            $equipment = Computer::findOrFail(
                $validated['equipment_id']
            );
        } else {
            $equipment = Printer::findOrFail(
                $validated['equipment_id']
            );
        }

        // Check if equipment is already assigned
        if ($equipment->activeAssignments()->exists()) {
            return back()
                ->withInput()
                ->withErrors([
                    'equipment_id' =>
                        'This equipment is already assigned to an employee.'
                ]);
        }

        // Save assignment
        Assignment::create([
            'equipment_type' => get_class($equipment),
            'equipment_id' => $equipment->id,
            'employee_id' => $validated['employee_id'],
            'assigned_at' => $validated['assigned_at'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->route('assignments.index')
            ->with('success', 'Equipment assigned successfully.');
    }

    // Return equipment
    public function returnEquipment(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'returned_at' => [
                'required',
                'date',
                'after_or_equal:assigned_at',
            ],
        ]);

        if ($assignment->returned_at !== null) {
            return back()->withErrors([
                'returned_at' => 'This equipment has already been returned.'
            ]);
        }

        $assignment->update([
            'returned_at' => $validated['returned_at'],
        ]);

        return redirect()
            ->route('assignments.index')
            ->with('success', 'Equipment returned successfully.');
    }
}
