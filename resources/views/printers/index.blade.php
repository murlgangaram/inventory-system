
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Printer Record | Inventory System</title>

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
            font-family: Arial, sans-serif;
            color: var(--navy);
        }

        .sidebar {
            position: fixed;
            width: 250px;
            height: 100vh;
            background: var(--navy);
            padding: 25px 15px;
            color: white;
        }

        .sidebar h4 {
            font-size: 20px;
            margin: 0 10px 35px;
        }

        .sidebar a {
            display: block;
            color: #cbd5e1;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 7px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: var(--blue);
            color: white;
        }

        .main {
            margin-left: 250px;
            padding: 35px;
        }

        .content-card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 25px;
        }

        .search-box {
            max-width: 380px;
        }

        .table th {
            color: var(--muted);
            font-size: 12px;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
            font-size: 14px;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            background: #eaf2ff;
            color: #2563eb;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<div class="sidebar">
    <h4>Inventory System</h4>

    <a href="{{ route('employees.index') }}">Employee Record</a>
    <a href="{{ route('computers.index') }}">Computer Record</a>
    <a href="{{ route('printers.index') }}" class="active">Printer Record</a>
</div>

<div class="main">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Printer Record</h2>
            <p class="text-secondary mb-0">Manage and monitor registered printers.</p>
        </div>

        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPrinterModal">
            + Add Printer
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="content-card">

        <form method="GET" action="{{ route('printers.index') }}" class="mb-4">
            <div class="input-group search-box">
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Search printer, office, brand..."
                    value="{{ $search }}"
                >
                <button class="btn btn-outline-primary">Search</button>

                @if($search)
                    <a href="{{ route('printers.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>PRINTER NAME</th>
                        <th>OFFICE</th>
                        <th>BRAND / MODEL</th>
                        <th>SERIAL NUMBER</th>
                        <th>STATUS</th>
                        <th>REMARKS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($printers as $printer)
                        <tr>
                            <td class="fw-semibold">{{ $printer->printer_name }}</td>
                            <td>{{ $printer->office }}</td>
                            <td>{{ $printer->brand }} {{ $printer->model }}</td>
                            <td>{{ $printer->serial_number ?: '—' }}</td>
                            <td>
                                <span class="status">{{ $printer->status }}</span>
                            </td>
                            <td>{{ $printer->remarks ?: '—' }}</td>

                            <td class="text-nowrap">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary edit-printer"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editPrinterModal"
                                    data-id="{{ $printer->id }}"
                                    data-name="{{ $printer->printer_name }}"
                                    data-office="{{ $printer->office }}"
                                    data-brand="{{ $printer->brand }}"
                                    data-model="{{ $printer->model }}"
                                    data-serial="{{ $printer->serial_number }}"
                                    data-status="{{ $printer->status }}"
                                    data-remarks="{{ $printer->remarks }}"
                                >
                                    Edit
                                </button>

                                <form
                                    method="POST"
                                    action="{{ route('printers.destroy', $printer) }}"
                                    class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to delete this printer?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                No printer records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $printers->links() }}
        </div>

    </div>
</div>

{{-- ADD PRINTER MODAL --}}
<div class="modal fade" id="addPrinterModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <form method="POST" action="{{ route('printers.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Printer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Printer Name *</label>
                            <input type="text" name="printer_name" class="form-control" required
                                   placeholder="e.g. ADMIN-PRINTER-001">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Office *</label>
                            <input type="text" name="office" class="form-control" required
                                   placeholder="Enter office">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" class="form-control"
                                   placeholder="e.g. HP, Epson, Canon">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" class="form-control"
                                   placeholder="Enter printer model">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Serial Number</label>
                            <input type="text" name="serial_number" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="Working">Working</option>
                                <option value="For Maintenance">For Maintenance</option>
                                <option value="For Checking">For Checking</option>
                                <option value="Retired">Retired</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3"
                                      placeholder="Additional information..."></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">Save Printer</button>
                </div>

            </form>
        </div>
    </div>
</div>

{{-- EDIT PRINTER MODAL --}}
<div class="modal fade" id="editPrinterModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <form method="POST" id="editPrinterForm">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <h5 class="modal-title">Edit Printer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Printer Name *</label>
                            <input type="text" name="printer_name" id="edit_printer_name"
                                   class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Office *</label>
                            <input type="text" name="office" id="edit_printer_office"
                                   class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" id="edit_printer_brand"
                                   class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" id="edit_printer_model"
                                   class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Serial Number</label>
                            <input type="text" name="serial_number" id="edit_printer_serial"
                                   class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status *</label>
                            <select name="status" id="edit_printer_status"
                                    class="form-select" required>
                                <option value="Working">Working</option>
                                <option value="For Maintenance">For Maintenance</option>
                                <option value="For Checking">For Checking</option>
                                <option value="Retired">Retired</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" id="edit_printer_remarks"
                                      class="form-control" rows="3"></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">Update Printer</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.querySelectorAll('.edit-printer').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;

            document.getElementById('editPrinterForm').action =
                "{{ url('/printers') }}/" + id;

            document.getElementById('edit_printer_name').value = this.dataset.name || '';
            document.getElementById('edit_printer_office').value = this.dataset.office || '';
            document.getElementById('edit_printer_brand').value = this.dataset.brand || '';
            document.getElementById('edit_printer_model').value = this.dataset.model || '';
            document.getElementById('edit_printer_serial').value = this.dataset.serial || '';
            document.getElementById('edit_printer_status').value = this.dataset.status || 'Working';
            document.getElementById('edit_printer_remarks').value = this.dataset.remarks || '';
        });
    });
</script>

</body>
</html>
