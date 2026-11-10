<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class ComputerController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');

        $computers = Computer::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('computer_name', 'ILIKE', "%{$search}%")
                      ->orWhere('office', 'ILIKE', "%{$search}%")
                      ->orWhere('brand', 'ILIKE', "%{$search}%")
                      ->orWhere('model', 'ILIKE', "%{$search}%")
                      ->orWhere('serial_number', 'ILIKE', "%{$search}%");
                });
            })
            ->orderBy('computer_name')
            ->paginate(10)
            ->withQueryString();

        return view('computers.index', compact('computers', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'computer_name' => ['required', 'string', 'max:255'],
            'office' => ['required', 'string', 'max:255'],
            'ram' => ['nullable', 'string', 'max:100'],
            'cpu' => ['nullable', 'string', 'max:255'],
            'ssd' => ['nullable', 'string', 'max:100'],
            'hd' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => [
                'nullable',
                'string',
                'max:255',
                'unique:computers,serial_number',
            ],
            'mac_address' => [
                'nullable',
                'string',
                'max:255',
                'unique:computers,mac_address',
            ],
            'status' => [
                'required',
                Rule::in(['Working', 'For Maintenance', 'For Checking', 'Retired']),
            ],
            'remarks' => ['nullable', 'string'],
        ]);

        Computer::create($validated);

        return redirect()
            ->route('computers.index')
            ->with('success', 'Computer added successfully.');
    }

    public function update(Request $request, Computer $computer): RedirectResponse
    {
        $validated = $request->validate([
            'computer_name' => ['required', 'string', 'max:255'],
            'office' => ['required', 'string', 'max:255'],
            'ram' => ['nullable', 'string', 'max:100'],
            'cpu' => ['nullable', 'string', 'max:255'],
            'ssd' => ['nullable', 'string', 'max:100'],
            'hd' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('computers', 'serial_number')->ignore($computer->id),
            ],
            'mac_address' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('computers', 'mac_address')->ignore($computer->id),
            ],
            'status' => [
                'required',
                Rule::in(['Working', 'For Maintenance', 'For Checking', 'Retired']),
            ],
            'remarks' => ['nullable', 'string'],
        ]);

        $computer->update($validated);

        return redirect()
            ->route('computers.index')
            ->with('success', 'Computer updated successfully.');
    }

    public function destroy(Computer $computer): RedirectResponse
    {
        if ($computer->assignments()->exists()) {
            return redirect()
                ->route('computers.index')
                ->with('error', 'Cannot delete this computer because assignment history exists.');
        }

        $computer->delete();

        return redirect()
            ->route('computers.index')
            ->with('success', 'Computer deleted successfully.');
    }
}
