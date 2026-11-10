<?php
namespace App\Http\Controllers;

use App\Models\Printer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrinterController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $printers = Printer::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('printer_name', 'ILIKE', "%{$search}%")
                        ->orWhere('brand', 'ILIKE', "%{$search}%")
                        ->orWhere('model', 'ILIKE', "%{$search}%")
                        ->orWhere('serial_number', 'ILIKE', "%{$search}%")
                        ->orWhere('office', 'ILIKE', "%{$search}%")
                        ->orWhere('status', 'ILIKE', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('printers.index', compact('printers', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'printer_name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255', 'unique:printers,serial_number'],
            'office' => ['required', 'string', 'max:255'],
            'status' => [
                'required',
                Rule::in(['Working', 'For Maintenance', 'For Checking', 'Retired']),
            ],
            'remarks' => ['nullable', 'string'],
        ]);

        Printer::create($validated);

        return redirect()->route('printers.index')
            ->with('success', 'Printer added successfully.');
    }

    public function update(Request $request, Printer $printer)
    {
        $validated = $request->validate([
            'printer_name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('printers', 'serial_number')->ignore($printer->id),
            ],
            'office' => ['required', 'string', 'max:255'],
            'status' => [
                'required',
                Rule::in(['Working', 'For Maintenance', 'For Checking', 'Retired']),
            ],
            'remarks' => ['nullable', 'string'],
        ]);

        $printer->update($validated);

        return redirect()->route('printers.index')
            ->with('success', 'Printer updated successfully.');
    }

    public function destroy(Printer $printer)
    {
        $printer->delete();

        return redirect()->route('printers.index')
            ->with('success', 'Printer deleted successfully.');
    }
}
