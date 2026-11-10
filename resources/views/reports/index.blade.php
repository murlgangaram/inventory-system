
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f3f6fb;
            color: #172033;
            font-family: Arial, sans-serif;
        }
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: #172033;
            position: fixed;
            padding: 25px 15px;
        }
        .sidebar h4 {
            color: white;
            margin-bottom: 30px;
        }
        .sidebar a {
            display: block;
            color: #cbd5e1;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 6px;
        }
        .sidebar a:hover, .sidebar a.active {
            background: #2563eb;
            color: white;
        }
        .main {
            margin-left: 250px;
            padding: 30px;
        }
        .card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,.04);
        }
        @media print {
            .sidebar, .no-print, .btn { display: none !important; }
            .main { margin: 0; padding: 0; }
            body { background: white; }
        }
    </style>
</head>
<body>

<div class="sidebar no-print">
    <h4>Inventory System</h4>
    <a href="{{ url('/') }}">Dashboard</a>
    <a href="{{ route('employees.index') }}">Employee Record</a>
    <a href="{{ route('computers.index') }}">Computer Record</a>
    <a href="{{ route('printers.index') }}">Printer Record</a>
    <a href="{{ route('assignments.index') }}">Equipment Assignment</a>
    <a href="{{ route('reports.index') }}" class="active">Reports</a>
</div>

<div class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Inventory Reports</h2>
            <p class="text-muted">View and print your inventory records.</p>
        </div>
        <button onclick="window.print()" class="btn btn-primary no-print">
            Print Report
        </button>
    </div>

    <div class="card p-3 mb-4 no-print">
        <form method="GET" action="{{ route('reports.index') }}"
              class="d-flex gap-2 flex-wrap">
            <select name="type" class="form-select" style="max-width:280px">
                <option value="computers" {{ $type == 'computers' ? 'selected' : '' }}>Computer Inventory</option>
                <option value="printers" {{ $type == 'printers' ? 'selected' : '' }}>Printer Inventory</option>
                <option value="assignments" {{ $type == 'assignments' ? 'selected' : '' }}>Employee Assignment</option>
            </select>
            <button class="btn btn-primary">View Report</button>
        </form>
    </div>

    <div class="card p-4">
        @if($type == 'computers')
            <h5 class="mb-3">Computer Inventory Report</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Computer Name</th>
                            <th>Office</th>
                            <th>Brand / Model</th>
                            <th>CPU</th>
                            <th>RAM</th>
                            <th>SSD</th>
                            <th>Serial Number</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($computers as $computer)
                        <tr>
                            <td>{{ $computer->computer_name }}</td>
                            <td>{{ $computer->office }}</td>
                            <td>{{ $computer->brand }} {{ $computer->model }}</td>
                            <td>{{ $computer->cpu }}</td>
                            <td>{{ $computer->ram }}</td>
                            <td>{{ $computer->ssd }}</td>
                            <td>{{ $computer->serial_number }}</td>
                            <td>{{ $computer->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No computer records found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        @elseif($type == 'printers')
            <h5 class="mb-3">Printer Inventory Report</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Printer Name</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Serial Number</th>
                            <th>Office</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($printers as $printer)
                        <tr>
                            <td>{{ $printer->printer_name }}</td>
                            <td>{{ $printer->brand }}</td>
                            <td>{{ $printer->model }}</td>
                            <td>{{ $printer->serial_number }}</td>
                            <td>{{ $printer->office }}</td>
                            <td>{{ $printer->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No printer records found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        @elseif($type == 'assignments')
            <h5 class="mb-3">Employee Assignment Report</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Employee</th>
                            <th>Equipment Type</th>
                            <th>Equipment</th>
                            <th>Assigned Date</th>
                            <th>Returned Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($assignments as $assignment)
                        <tr>
                            <td>{{ $assignment->employee?->full_name ?? 'Employee unavailable' }}</td>
                            <td>{{ class_basename($assignment->equipment_type) }}</td>
                            <td>
                                {{ $assignment->equipment?->computer_name
                                    ?? $assignment->equipment?->printer_name
                                    ?? 'Equipment unavailable' }}
                            </td>
                            <td>{{ $assignment->assigned_at?->format('M d, Y') }}</td>
                            <td>{{ $assignment->returned_at?->format('M d, Y') ?? '-' }}</td>
                            <td>{{ $assignment->returned_at ? 'Returned' : 'Assigned' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No assignment records found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <div class="text-muted mt-3">
            Generated on: {{ now()->format('F d, Y h:i A') }}
        </div>
    </div>
</div>

</body>
</html>
