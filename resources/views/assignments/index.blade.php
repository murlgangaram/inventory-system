
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Assignment | Inventory System</title>

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

        .content-card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            font-size: 14px;
        }

        .form-control,
        .form-select {
            border-color: var(--line);
            padding: 10px 12px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
        }

        .btn-primary {
            background: var(--blue);
            border-color: var(--blue);
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

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-returned {
            background: #e2e8f0;
            color: #475569;
        }

        .status-badge {
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .table-responsive {
            overflow-x: auto;
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

<aside class="sidebar">
    <h5>Inventory System</h5>

    <a href="{{ route('employees.index') }}">Employee Record</a>
    <a href="{{ route('computers.index') }}">Computer Record</a>
    <a href="{{ route('printers.index') }}">Printer Record</a>
    <a href="{{ route('assignments.index') }}" class="active">Equipment Assignment</a>
</aside>

<main class="main-content">

    <div class="mb-4">
        <h2 class="page-title">Equipment Assignment</h2>
        <p class="page-description">
            Assign computers and printers to employees and manage returned equipment.
        </p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please check the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Add Assignment Form -->
    <section class="content-card">
        <h5 class="section-title">Assign Equipment to Employee</h5>

        <form action="{{ route('assignments.store') }}" method="POST">
            @csrf

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Select Employee</label>
                    <select name="employee_id" class="form-select" required>
                        <option value="">Choose employee</option>

                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}"
                                {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->full_name }}
                                ({{ $employee->user_id_number }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Equipment Type</label>
                    <select name="equipment_type" id="equipmentType"
                            class="form-select" required>
                        <option value="computer"
                            {{ old('equipment_type', 'computer') == 'computer' ? 'selected' : '' }}>
                            Computer
                        </option>
                        <option value="printer"
                            {{ old('equipment_type') == 'printer' ? 'selected' : '' }}>
                            Printer
                        </option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Select Equipment</label>
                    <select name="equipment_id" id="equipmentId"
                            class="form-select" required>
                        <option value="">Choose equipment</option>

                        @foreach($computers as $computer)
                            <option value="{{ $computer->id }}"
                                data-type="computer"
                                {{ old('equipment_id') == $computer->id && old('equipment_type', 'computer') == 'computer' ? 'selected' : '' }}>
                                {{ $computer->computer_name }}
                                — {{ $computer->office }}
                            </option>
                        @endforeach

                        @foreach($printers as $printer)
                            <option value="{{ $printer->id }}"
                                data-type="printer"
                                {{ old('equipment_id') == $printer->id && old('equipment_type') == 'printer' ? 'selected' : '' }}>
                                {{ $printer->printer_name }}
                                — {{ $printer->office }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Assignment Date</label>
                    <input type="date" name="assigned_at"
                           class="form-control"
                           value="{{ old('assigned_at', date('Y-m-d')) }}"
                           required>
                </div>

                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control"
                              rows="2"
                              placeholder="Optional assignment remarks">{{ old('remarks') }}</textarea>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary px-4">
                        Save Assignment
                    </button>
                </div>

            </div>
        </form>
    </section>

    <!-- Assignment History -->
    <section class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="section-title mb-0">Assignment History</h5>
            <span class="text-muted small">
                {{ $assignments->total() }} records
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>EMPLOYEE</th>
                        <th>EQUIPMENT TYPE</th>
                        <th>EQUIPMENT</th>
                        <th>ASSIGNED DATE</th>
                        <th>RETURNED DATE</th>
                        <th>STATUS</th>
                        <th>REMARKS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($assignments as $assignment)
                        <tr>
                            <td>
                                @if($assignment->employee)
                                    <strong>{{ $assignment->employee->full_name }}</strong>
                                    <div class="text-muted small">
                                        {{ $assignment->employee->user_id_number }}
                                    </div>
                                @else
                                    <span class="text-muted">Employee unavailable</span>
                                @endif
                            </td>

                            <td>
                                {{ class_basename($assignment->equipment_type) }}
                            </td>

                            <td>
                                @if($assignment->equipment)
                                    @if($assignment->equipment instanceof \App\Models\Computer)
                                        {{ $assignment->equipment->computer_name }}
                                    @elseif($assignment->equipment instanceof \App\Models\Printer)
                                        {{ $assignment->equipment->printer_name }}
                                    @else
                                        Equipment #{{ $assignment->equipment_id }}
                                    @endif
                                @else
                                    <span class="text-muted">Equipment unavailable</span>
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
                                    <span class="status-badge status-active">Assigned</span>
                                @else
                                    <span class="status-badge status-returned">Returned</span>
                                @endif
                            </td>

                            <td>{{ $assignment->remarks ?: '—' }}</td>

                            <td>
                                @if(is_null($assignment->returned_at))
                                    <form action="{{ route('assignments.return', $assignment) }}"
                                          method="POST"
                                          onsubmit="return confirm('Are you sure you want to mark this equipment as returned?');">
                                        @csrf

                                        <input type="hidden" name="assigned_at"
                                               value="{{ $assignment->assigned_at->format('Y-m-d') }}">

                                        <input type="hidden" name="returned_at"
                                               value="{{ date('Y-m-d') }}">

                                        <button type="submit"
                                                class="btn btn-sm btn-outline-primary">
                                            Return
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">Completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                No equipment assignments found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $assignments->links() }}
        </div>
    </section>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const equipmentType = document.getElementById('equipmentType');
    const equipmentId = document.getElementById('equipmentId');

    function filterEquipment() {
        const selectedType = equipmentType.value;
        const options = equipmentId.querySelectorAll('option');

        let selectedOptionStillValid = false;

        options.forEach(option => {
            if (!option.dataset.type) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const matches = option.dataset.type === selectedType;

            option.hidden = !matches;
            option.disabled = !matches;

            if (matches && option.selected) {
                selectedOptionStillValid = true;
            }
        });

        if (!selectedOptionStillValid) {
            equipmentId.value = '';
        }
    }

    equipmentType.addEventListener('change', filterEquipment);

    filterEquipment();
</script>

</body>
</html>
