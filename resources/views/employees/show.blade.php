
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile | Inventory System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --navy: #172033;
            --blue: #2563eb;
            --bg: #f3f6fb;
            --muted: #718096;
            --line: #e6ebf2;
        }

        * { box-sizing: border-box; }

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
            color: white;
        }

        .brand {
            font-size: 21px;
            font-weight: 700;
            padding: 5px 12px 30px;
        }

        .sidebar a {
            display: block;
            color: #cbd5e1;
            text-decoration: none;
            padding: 13px 15px;
            border-radius: 8px;
            margin-bottom: 7px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: var(--blue);
            color: white;
        }

        .main-content {
            margin-left: 250px;
            padding: 35px;
        }

        .page-title {
            font-size: 27px;
            font-weight: 700;
        }

        .page-description { color: var(--muted); }

        .content-card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 22px;
            box-shadow: 0 3px 12px rgba(0,0,0,.03);
        }

        .detail-label {
            font-size: 12px;
            color: var(--muted);
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .detail-value {
            font-weight: 600;
            margin-bottom: 20px;
            overflow-wrap: anywhere;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
        }

        .table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            padding: 14px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 14px;
            vertical-align: middle;
        }

        @media(max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <div class="brand">Inventory System</div>

    <a href="{{ url('/') }}">Dashboard</a>
    <a href="#">Equipment Record</a>
    <a href="{{ route('employees.index') }}" class="active">
        Employee Record
    </a>
</aside>

<main class="main-content">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h1 class="page-title mb-1">Employee Profile</h1>
            <p class="page-description mb-0">
                View employee information and assigned equipment.
            </p>
        </div>

        <a href="{{ route('employees.index') }}"
           class="btn btn-outline-secondary">
            Back to Employee List
        </a>
    </div>

    <!-- EMPLOYEE INFORMATION -->
    <div class="content-card">

        <h5 class="fw-bold mb-4">Employee Information</h5>

        <div class="row">

            <div class="col-md-6">
                <div class="detail-label">ID Number</div>
                <div class="detail-value">
                    {{ $employee->user_id_number }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="detail-label">Employee Name</div>
                <div class="detail-value">
                    {{ $employee->full_name }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="detail-label">Position</div>
                <div class="detail-value">
                    {{ $employee->position ?: '—' }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="detail-label">Office</div>
                <div class="detail-value">
                    {{ $employee->office }}
                </div>
            </div>

            <div class="col-12">
                <div class="detail-label">Job Description</div>
                <div class="detail-value">
                    {{ $employee->job_description ?: '—' }}
                </div>
            </div>

        </div>
    </div>

    <!-- CURRENTLY ASSIGNED EQUIPMENT -->
    <div class="content-card">

        <h5 class="section-title mb-3">Currently Assigned Equipment</h5>

        <div class="table-responsive">
            <table class="table table-hover">

                <thead>
                    <tr>
                        <th>EQUIPMENT TYPE</th>
                        <th>EQUIPMENT NAME</th>
                        <th>MODEL</th>
                        <th>SERIAL NUMBER</th>
                        <th>ASSIGNED DATE</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($employee->activeAssignments as $assignment)
                        @php
                            $equipment = $assignment->equipment;
                            $type = class_basename($assignment->equipment_type);
                        @endphp

                        @if($equipment)
                            <tr>
                                <td>
                                    {{ $type === 'Computer' ? 'Computer' : ($type === 'Printer' ? 'Printer' : $type) }}
                                </td>

                                <td>
                                    @if($type === 'Computer')
                                        {{ $equipment->computer_name }}
                                    @elseif($type === 'Printer')
                                        {{ $equipment->printer_name }}
                                    @else
                                        {{ $equipment->name ?? '—' }}
                                    @endif
                                </td>

                                <td>
                                    {{ $equipment->model ?? '—' }}
                                </td>

                                <td>
                                    {{ $equipment->serial_number ?? '—' }}
                                </td>

                                <td>
                                    {{ $assignment->assigned_at?->format('M d, Y') ?? '—' }}
                                </td>
                            </tr>
                        @endif

                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                No equipment currently assigned to this employee.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    <!-- ASSIGNMENT HISTORY -->
    <div class="content-card">

        <h5 class="section-title mb-3">Equipment Assignment History</h5>

        <div class="table-responsive">
            <table class="table table-hover">

                <thead>
                    <tr>
                        <th>EQUIPMENT TYPE</th>
                        <th>ASSIGNED DATE</th>
                        <th>RETURNED DATE</th>
                        <th>REMARKS</th>
                        <th>STATUS</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($employee->assignments as $assignment)
                        @php
                            $type = class_basename($assignment->equipment_type);
                        @endphp

                        <tr>
                            <td>{{ $type }}</td>

                            <td>
                                {{ $assignment->assigned_at?->format('M d, Y') ?? '—' }}
                            </td>

                            <td>
                                {{ $assignment->returned_at?->format('M d, Y') ?? '—' }}
                            </td>

                            <td>
                                {{ $assignment->remarks ?: '—' }}
                            </td>

                            <td>
                                @if($assignment->returned_at)
                                    <span class="badge bg-secondary">Returned</span>
                                @else
                                    <span class="badge bg-success">Assigned</span>
                                @endif
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                No assignment history found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

</main>

</body>
</html>
