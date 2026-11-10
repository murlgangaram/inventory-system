<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Printer;
use App\Models\Assignment;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type', 'computers');

        $computers = Computer::orderBy('office')
            ->get();

        $printers = Printer::orderBy('office')
            ->get();

        $assignments = Assignment::with([
            'employee',
            'equipment'
        ])
            ->latest()
            ->get();

        return view('reports.index', compact(
            'type',
            'computers',
            'printers',
            'assignments'
        ));
    }
}
