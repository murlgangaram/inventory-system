
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Inventory System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --navy: #172033;
            --blue: #2563eb;
            --bg: #f3f6fb;
            --muted: #718096;
            --line: #e6ebf2;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--navy);
            font-family: Arial, sans-serif;
        }

        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: var(--navy);
            position: fixed;
            top: 0;
            left: 0;
            padding: 25px 16px;
        }

        .sidebar h5 {
            color: white;
            font-weight: 700;
            margin: 0 12px 30px;
        }

        .sidebar a {
            display: block;
            color: #cbd5e1;
            text-decoration: none;
            padding: 12px 14px;
            margin-bottom: 6px;
            border-radius: 8px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: var(--blue);
            color: white;
        }

        .main-content {
            margin-left: 250px;
            padding: 32px;
        }

        .page-title {
            font-weight: 700;
            margin-bottom: 5px;
        }

        .page-description {
            color: var(--muted);
        }

        .stat-card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 22px;
            height: 100%;
        }

        .stat-label {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 12px;
        }

        .stat-number {
            font-size: 30px;
            font-weight: 700;
            margin: 0;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #eff6ff;
            color: var(--blue);
            font-size: 21px;
        }

        .content-card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 24px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
        }

        .table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            white-space: nowrap;
            padding: 14px;
        }

        .table tbody td {
            padding: 14px;
            vertical-align: middle;
        }

        .status-badge {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-assigned {
            background: #dcfce7;
            color: #166534;
        }

        .status-returned {
            background: #e2e8f0;
            color: #475569;
        }

        .quick-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border: 1px solid var(--line);
            border-radius: 9px;
            color: var(--navy);
            text-decoration: none;
            margin-bottom: 10px;
        }

        .quick-link:hover {
            border-color: var(--blue);
            color: var(--blue);
            background: #f8fbff;
        }

        .quick-link small {
            color: var(--muted);
        }

        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .main-content {
                margin-left: 0;
                padding: 18px;
            }
        }
    </style>
</head>

<body>

@php
    // Dashboard statistics
    $totalEmployees = \App\Models\Employee::count();

    $totalComputers = \App\Models\Computer::count();

    $totalPrinters = \App\Models\Printer::count();

    $activeAssignments = \App\Models\Assignment::whereNull('returned_at')
        ->count();

    $totalAssignments = \App\Models\Assignment::count();

    // Available equipment
    $availableComputers = \App\Models\Computer::whereDoesntHave(
        'activeAssignments'
    )->count();

    $availablePrinters = \App\Models\Printer::whereDoesntHave(
        'activeAssignments'
    )->count();

    $totalAvailable = $availableComputers + $availablePrinters;

    // Equipment under maintenance
    $computersMaintenance = \App\Models\Computer::where(
        'status',
        'For Maintenance'
    )->count();

    $printersMaintenance = \App\Models\Printer::where(
        'status',
        'For Maintenance'
    )->count();

    $totalMaintenance = $computersMaintenance + $printersMaintenance;

    // Recent assignments
    $recentAssignments = \App\Models\Assignment::with([
        'employee',
        'equipment'
    ])
        ->latest()
        ->take(8)
        ->get();
@endphp

<aside class="sidebar">
    <h5>Inventory System</h5>

    <a href="{{ url('/') }}" class="active">Dashboard</a>
    <a href="{{ route('employees.index') }}">Employee Record</a>
    <a href="{{ route('computers.index') }}">Computer Record</a>
    <a href="{{ route('printers.index') }}">Printer Record</a>
    <a href="{{ route('assignments.index') }}">Equipment Assignment</a>
</aside>

<main class="main-content">

    <!-- Dashboard Header -->
    <div class="mb-4">
        <h2 class="page-title">Dashboard</h2>
        <p class="page-description">
            Overview of your office equipment inventory and assignments.
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">

        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label">Total Employees</p>
                        <h3 class="stat-number">{{ $totalEmployees }}</h3>
                    </div>
                    <div class="stat-icon">👥</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label">Total Computers</p>
                        <h3 class="stat-number">{{ $totalComputers }}</h3>
                    </div>
                    <div class="stat-icon">💻</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label">Total Printers</p>
                        <h3 class="stat-number">{{ $totalPrinters }}</h3>
                    </div>
                    <div class="stat-icon">🖨️</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label">Currently Assigned</p>
                        <h3 class="stat-number">{{ $activeAssignments }}</h3>
                    </div>
                    <div class="stat-icon">📋</div>
                </div>
            </div>
        </div>

    </div>

    <!-- Equipment Overview -->
    <div class="row g-3 mb-4">

        <div class="col-md-6">
            <div class="stat-card">
                <p class="stat-label">Available Equipment</p>
                <h3 class="stat-number">{{ $totalAvailable }}</h3>
                <small class="text-muted">
                    Computers: {{ $availableComputers }}
                    | Printers: {{ $availablePrinters }}
                </small>
            </div>
        </div>

        <div class="col-md-6">
            <div class="stat-card">
                <p class="stat-label">Equipment for Maintenance</p>
                <h3 class="stat-number">{{ $totalMaintenance }}</h3>
                <small class="text-muted">
                    Computers: {{ $computersMaintenance }}
                    | Printers: {{ $printersMaintenance }}
                </small>
            </div>
        </div>

    </div>

    <!-- Recent Assignments -->
    <section class="content-card mb-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="section-title mb-0">Recent Equipment Assignments</h5>

            <a href="{{ route('assignments.index') }}"
               class="btn btn-sm btn-outline-primary">
                View All
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>EMPLOYEE</th>
                        <th>EQUIPMENT TYPE</th>
                        <th>EQUIPMENT</th>
                        <th>ASSIGNED DATE</th>
                        <th>RETURNED DATE</th>
                        <th>STATUS</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($recentAssignments as $assignment)
                        <tr>
                            <td>
                                @if($assignment->employee)
                                    <strong>
                                        {{ $assignment->employee->full_name }}
                                    </strong>
                                    <div class="text-muted small">
                                        {{ $assignment->employee->user_id_number }}
                                    </div>
                                @else
                                    Employee unavailable
                                @endif
                            </td>

                            <td>
                                {{ class_basename($assignment->equipment_type) }}
                            </td>

                            <td>
                                @if($assignment->equipment instanceof \App\Models\Computer)
                                    {{ $assignment->equipment->computer_name }}
                                @elseif($assignment->equipment instanceof \App\Models\Printer)
                                    {{ $assignment->equipment->printer_name }}
                                @else
                                    Equipment #{{ $assignment->equipment_id }}
                                @endif
                            </td>

                            <td>
                                {{ $assignment->assigned_at?->format('M d, Y') }}
                            </td>

                            <td>
                                {{ $assignment->returned_at?->format('M d, Y') ?? '—' }}
                            </td>

                            <td>
                                @if(is_null($assignment->returned_at))
                                    <span class="status-badge status-assigned">
                                        Assigned
                                    </span>
                                @else
                                    <span class="status-badge status-returned">
                                        Returned
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No equipment assignments recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section>

    <!-- Quick Access -->
    <section class="content-card">
        <h5 class="section-title mb-3">Quick Access</h5>

        <div class="row">
            <div class="col-md-6">
                <a href="{{ route('employees.index') }}" class="quick-link">
                    <div>
                        <strong>Employee Management</strong>
                        <div><small>Manage employee records</small></div>
                    </div>
                    <span>→</span>
                </a>

                <a href="{{ route('computers.index') }}" class="quick-link">
                    <div>
                        <strong>Computer Management</strong>
                        <div><small>Manage computer inventory</small></div>
                    </div>
                    <span>→</span>
                </a>
            </div>

            <div class="col-md-6">
                <a href="{{ route('printers.index') }}" class="quick-link">
                    <div>
                        <strong>Printer Management</strong>
                        <div><small>Manage printer inventory</small></div>
                    </div>
                    <span>→</span>
                </a>

                <a href="{{ route('assignments.index') }}" class="quick-link">
                    <div>
                        <strong>Equipment Assignment</strong>
                        <div><small>Assign and return equipment</small></div>
                    </div>
                    <span>→</span>
                </a>
            </div>
        </div>
    </section>

    <p class="text-center text-muted small mt-4">
        Inventory System Dashboard
    </p>

</main>

</body>
</html>
