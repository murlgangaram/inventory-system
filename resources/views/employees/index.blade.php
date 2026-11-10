
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Record | Inventory System</title>

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

        .sidebar-label {
            color: #94a3b8;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 15px;
            margin-bottom: 10px;
        }

        .sidebar a {
            display: block;
            color: #cbd5e1;
            text-decoration: none;
            padding: 13px 15px;
            border-radius: 8px;
            margin-bottom: 7px;
            font-size: 14px;
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

        .page-description {
            color: var(--muted);
            font-size: 14px;
        }

        .content-card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 3px 12px rgba(0,0,0,.03);
        }

        .search-input { max-width: 380px; }

        .table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            padding: 15px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            font-size: 13px;
        }

        .employee-name {
            color: var(--navy);
            font-weight: 600;
            text-decoration: none;
        }

        .employee-name:hover { color: var(--blue); }

        .btn-primary {
            background: var(--blue);
            border-color: var(--blue);
        }

        .btn-primary:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }

        .modal-content {
            border: 0;
            border-radius: 13px;
        }

        .form-label {
            font-weight: 600;
            font-size: 13px;
        }

        .form-control {
            font-size: 13px;
            padding: 10px 12px;
        }

        .empty-state {
            text-align: center;
            padding: 45px 15px;
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
                padding: 20px;
            }

            .brand { padding-bottom: 20px; }
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="brand">Inventory System</div>

    <div class="sidebar-label">Main Menu</div>

    <a href="{{ url('/') }}">Dashboard</a>

    <a href="{{ route('computers.index') }}">
        Equipment Record
    </a>

    <a href="{{ route('employees.index') }}" class="active">
        Employee Record
    </a>
</aside>

<!-- MAIN CONTENT -->
<main class="main-content">

    <!-- PAGE HEADER -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title mb-1">Employee Record</h1>
            <p class="page-description mb-0">
                Create and manage the list of employees who can be assigned to computers and printers.
            </p>
        </div>

        <button class="btn btn-primary px-4"
                data-bs-toggle="modal"
                data-bs-target="#createEmployeeModal">
            + Create Employee
        </button>
    </div>

    <!-- SUCCESS MESSAGE -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- ERROR MESSAGE -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- VALIDATION ERRORS -->
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

    <!-- EMPLOYEE TABLE -->
    <div class="content-card">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

            <div>
                <h5 class="fw-bold mb-1">Employee List</h5>
                <small class="text-secondary">
                    Total employees: {{ $employees->total() }}
                </small>
            </div>

            <!-- SEARCH -->
            <form action="{{ route('employees.index') }}"
                  method="GET"
                  class="d-flex flex-wrap gap-2">

                <input
                    type="text"
                    name="search"
                    class="form-control search-input"
                    placeholder="Search employee, ID number, position, or office..."
                    value="{{ $search ?? '' }}"
                >

                <button class="btn btn-outline-primary" type="submit">
                    Search
                </button>

                @if(!empty($search))
                    <a href="{{ route('employees.index') }}"
                       class="btn btn-outline-secondary">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">

                <thead>
                    <tr>
                        <th>ID NUMBER</th>
                        <th>EMPLOYEE NAME</th>
                        <th>POSITION</th>
                        <th>OFFICE</th>
                        <th class="text-center">ACTIONS</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td>{{ $employee->user_id_number }}</td>

                            <td>
                                <a href="{{ route('employees.show', $employee->id) }}"
                                   class="employee-name">
                                    {{ $employee->full_name }}
                                </a>
                            </td>

                            <td>{{ $employee->position ?: '—' }}</td>

                            <td>{{ $employee->office ?: '—' }}</td>

                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">

                                    <!-- EDIT BUTTON -->
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary edit-employee-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editEmployeeModal"
                                        data-id="{{ $employee->id }}"
                                        data-user-id-number="{{ $employee->user_id_number }}"
                                        data-full-name="{{ $employee->full_name }}"
                                        data-position="{{ $employee->position }}"
                                        data-job-description="{{ $employee->job_description }}"
                                        data-office="{{ $employee->office }}"
                                    >
                                        Edit
                                    </button>

                                    <!-- DELETE FORM -->
                                    <form
                                        action="{{ route('employees.destroy', $employee->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this employee?');"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger">
                                            Delete
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <h5>No Employee Records Found</h5>
                                    <p class="mb-0">
                                        There are no employee records available.
                                        Click Create Employee to add a new record.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        <!-- PAGINATION -->
        <div class="mt-3">
            {{ $employees->links('pagination::bootstrap-5') }}
        </div>

    </div>
</main>

<!-- CREATE EMPLOYEE MODAL -->
<div class="modal fade" id="createEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold">Create Employee</h5>
                    <small class="text-secondary">
                        Enter the employee information below.
                    </small>
                </div>

                <button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('employees.store') }}" method="POST">
                @csrf

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">ID Number *</label>
                            <input type="text"
                                   name="user_id_number"
                                   class="form-control"
                                   placeholder="Enter employee ID number"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text"
                                   name="full_name"
                                   class="form-control"
                                   placeholder="Enter employee full name"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Position</label>
                            <input type="text"
                                   name="position"
                                   class="form-control"
                                   placeholder="Enter position">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Office *</label>
                            <input type="text"
                                   name="office"
                                   class="form-control"
                                   placeholder="Enter office"
                                   required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Job Description</label>
                            <textarea name="job_description"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Enter job description"></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Save Employee
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- EDIT EMPLOYEE MODAL -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold">Edit Employee</h5>
                    <small class="text-secondary">
                        Update the selected employee information.
                    </small>
                </div>

                <button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
            </div>

            <form id="editEmployeeForm" method="POST">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">ID Number *</label>
                            <input type="text"
                                   name="user_id_number"
                                   id="edit_user_id_number"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text"
                                   name="full_name"
                                   id="edit_full_name"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Position</label>
                            <input type="text"
                                   name="position"
                                   id="edit_position"
                                   class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Office *</label>
                            <input type="text"
                                   name="office"
                                   id="edit_office"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Job Description</label>
                            <textarea name="job_description"
                                      id="edit_job_description"
                                      class="form-control"
                                      rows="3"></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Update Employee
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- BOOTSTRAP JAVASCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- EDIT EMPLOYEE JAVASCRIPT -->
<script>
    const employeeBaseUrl = @json(url('/employees'));

    document.querySelectorAll('.edit-employee-btn').forEach(button => {

        button.addEventListener('click', function () {

            const id = this.dataset.id;

            document.getElementById('editEmployeeForm').action =
                `${employeeBaseUrl}/${id}`;

            document.getElementById('edit_user_id_number').value =
                this.dataset.userIdNumber || '';

            document.getElementById('edit_full_name').value =
                this.dataset.fullName || '';

            document.getElementById('edit_position').value =
                this.dataset.position || '';

            document.getElementById('edit_office').value =
                this.dataset.office || '';

            document.getElementById('edit_job_description').value =
                this.dataset.jobDescription || '';
        });

    });
</script>

</body>
</html>
