<?php
namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');

        $employees = Employee::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('user_id_number', 'ILIKE', "%{$search}%")
                      ->orWhere('full_name', 'ILIKE', "%{$search}%")
                      ->orWhere('position', 'ILIKE', "%{$search}%")
                      ->orWhere('office', 'ILIKE', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->paginate(10)
            ->withQueryString();

        return view('employees.index', compact('employees', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id_number' => [
                'required',
                'string',
                'max:255',
                'unique:employees,user_id_number',
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'job_description' => ['nullable', 'string'],
            'office' => ['required', 'string', 'max:255'],
        ]);

        Employee::create($validated);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee added successfully.');
    }

    public function show(Employee $employee): View
    {
        $employee->load('assignments.equipment');

        return view('employees.show', compact('employee'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'user_id_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employees', 'user_id_number')
                    ->ignore($employee->id),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'job_description' => ['nullable', 'string'],
            'office' => ['required', 'string', 'max:255'],
        ]);

        $employee->update($validated);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        if ($employee->assignments()->exists()) {
            return redirect()
                ->route('employees.index')
                ->with('error', 'Cannot delete this employee because assignment history exists.');
        }

        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }
}
