
<?php

require_once __DIR__ . '/auth.php';

require_permission('manage_accounts');

/* =========================================
   SESSION AND SECURITY
========================================= */

if (empty($_SESSION['account_csrf'])) {
    $_SESSION['account_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['account_csrf'];

$message = $_SESSION['account_message'] ?? '';
$error = $_SESSION['account_error'] ?? '';

unset(
    $_SESSION['account_message'],
    $_SESSION['account_error']
);

/* =========================================
   HELPER FUNCTIONS
========================================= */

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

function redirectAccountPage(): void
{
    header('Location: account_management.php');
    exit;
}

function setAccountMessage(string $message): void
{
    $_SESSION['account_message'] = $message;
    redirectAccountPage();
}

function setAccountError(string $error): void
{
    $_SESSION['account_error'] = $error;
    redirectAccountPage();
}

/*
|--------------------------------------------------------------------------
| Validate employee assignment
|--------------------------------------------------------------------------
*/

function validateEmployee(
    PDO $pdo,
    ?int $employeeId,
    string $role,
    int $accountId = 0
): void {

    // User accounts must be linked to an employee.
    if ($role === 'user' && empty($employeeId)) {
        throw new Exception(
            'Please select an employee for the User account.'
        );
    }

    // Admin accounts may optionally be linked.
    if (empty($employeeId)) {
        return;
    }

    // Check if employee exists and is enabled.
    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE id = ?
        AND status = 'Enabled'
        LIMIT 1
    ");

    $stmt->execute([$employeeId]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception(
            'The selected employee does not exist or is disabled.'
        );
    }

    // Prevent one employee from being linked to multiple accounts.
    $stmt = $pdo->prepare("
        SELECT id
        FROM accounts
        WHERE employee_id = ?
        AND id != ?
        LIMIT 1
    ");

    $stmt->execute([
        $employeeId,
        $accountId
    ]);

    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception(
            'This employee is already linked to another account.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Validate username
|--------------------------------------------------------------------------
*/

function validateUsername(
    PDO $pdo,
    string $username,
    int $accountId = 0
): void {

    if (!preg_match('/^[a-zA-Z0-9_.-]{3,100}$/', $username)) {
        throw new Exception(
            'Username must be 3–100 characters and may contain letters, numbers, dots, underscores, and hyphens.'
        );
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM accounts
        WHERE username = ?
        AND id != ?
        LIMIT 1
    ");

    $stmt->execute([
        $username,
        $accountId
    ]);

    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception(
            'That username is already taken.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Validate optional employee ID
|--------------------------------------------------------------------------
*/

function getOptionalEmployeeId(): ?int
{
    $value = $_POST['employee_id'] ?? '';

    if ($value === '') {
        return null;
    }

    $employeeId = filter_var(
        $value,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($employeeId === false) {
        throw new Exception(
            'Please select a valid employee.'
        );
    }

    return (int) $employeeId;
}

/* =========================================
   HANDLE POST REQUESTS
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        /* =========================================
           CSRF VALIDATION
        ========================================= */

        $submittedToken = $_POST['csrf_token'] ?? '';

        if (
            empty($submittedToken) ||
            !hash_equals($csrfToken, $submittedToken)
        ) {
            throw new Exception(
                'Invalid security token. Please refresh the page and try again.'
            );
        }

        $action = $_POST['action'] ?? '';

        /* =========================================
           CREATE ACCOUNT
        ========================================= */

        if ($action === 'create') {

            $username = trim($_POST['username'] ?? '');

            $password = $_POST['password'] ?? '';

            $confirmPassword = $_POST['confirm_password'] ?? '';

            $role = $_POST['role'] ?? '';

            $employeeId = getOptionalEmployeeId();

            if ($username === '' || $password === '') {
                throw new Exception(
                    'Username and password are required.'
                );
            }

            if (!in_array($role, ['admin', 'user'], true)) {
                throw new Exception(
                    'Invalid account role.'
                );
            }

            validateUsername($pdo, $username);

            if (strlen($password) < 12) {
                throw new Exception(
                    'Password must contain at least 12 characters.'
                );
            }

            if ($password !== $confirmPassword) {
                throw new Exception(
                    'Password confirmation does not match.'
                );
            }

            validateEmployee(
                $pdo,
                $employeeId,
                $role
            );

            // Always hash passwords before saving.
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                INSERT INTO accounts
                    (
                        employee_id,
                        username,
                        password,
                        role,
                        status,
                        created_at
                    )
                VALUES
                    (?, ?, ?, ?, 'Active', NOW())
            ");

            $stmt->execute([
                $employeeId,
                $username,
                $hashedPassword,
                $role
            ]);

            setAccountMessage(
                'Account successfully created.'
            );
        }

        /* =========================================
           EDIT ACCOUNT
        ========================================= */

        elseif ($action === 'edit') {

            $accountId = (int) ($_POST['account_id'] ?? 0);

            $username = trim($_POST['username'] ?? '');

            $role = $_POST['role'] ?? '';

            $employeeId = getOptionalEmployeeId();

            if ($accountId <= 0) {
                throw new Exception(
                    'Invalid account selected.'
                );
            }

            if ($username === '') {
                throw new Exception(
                    'Username is required.'
                );
            }

            if (!in_array($role, ['admin', 'user'], true)) {
                throw new Exception(
                    'Invalid account role.'
                );
            }

            // Retrieve the account being edited.
            $stmt = $pdo->prepare("
                SELECT id, role
                FROM accounts
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$accountId]);

            $targetAccount = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$targetAccount) {
                throw new Exception(
                    'Account not found.'
                );
            }

            // Protect all Super Admin accounts.
            if ($targetAccount['role'] === 'super_admin') {
                throw new Exception(
                    'Super Admin accounts cannot be edited.'
                );
            }

            validateUsername(
                $pdo,
                $username,
                $accountId
            );

            validateEmployee(
                $pdo,
                $employeeId,
                $role,
                $accountId
            );

            $stmt = $pdo->prepare("
                UPDATE accounts
                SET
                    username = ?,
                    role = ?,
                    employee_id = ?
                WHERE id = ?
                AND role != 'super_admin'
            ");

            $stmt->execute([
                $username,
                $role,
                $employeeId,
                $accountId
            ]);

            setAccountMessage(
                'Account information successfully updated.'
            );
        }

        /* =========================================
           RESET PASSWORD
        ========================================= */

        elseif ($action === 'reset_password') {

            $accountId = (int) ($_POST['account_id'] ?? 0);

            $newPassword = $_POST['new_password'] ?? '';

            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($accountId <= 0) {
                throw new Exception(
                    'Invalid account selected.'
                );
            }

            if ($newPassword === '') {
                throw new Exception(
                    'Please enter a new password.'
                );
            }

            if (strlen($newPassword) < 12) {
                throw new Exception(
                    'New password must contain at least 12 characters.'
                );
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception(
                    'Password confirmation does not match.'
                );
            }

            // Check the target account.
            $stmt = $pdo->prepare("
                SELECT id, role
                FROM accounts
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$accountId]);

            $targetAccount = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$targetAccount) {
                throw new Exception(
                    'Account not found.'
                );
            }

            // Never reset a Super Admin password here.
            if ($targetAccount['role'] === 'super_admin') {
                throw new Exception(
                    'Super Admin passwords cannot be reset from this page.'
                );
            }

            // Hash the new password.
            $hashedPassword = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                UPDATE accounts
                SET password = ?
                WHERE id = ?
                AND role != 'super_admin'
            ");

            $stmt->execute([
                $hashedPassword,
                $accountId
            ]);

            setAccountMessage(
                'Password successfully reset.'
            );
        }

        /* =========================================
           ENABLE / DISABLE ACCOUNT
        ========================================= */

        elseif ($action === 'toggle_status') {

            $accountId = (int) ($_POST['account_id'] ?? 0);

            if ($accountId <= 0) {
                throw new Exception(
                    'Invalid account selected.'
                );
            }

            $stmt = $pdo->prepare("
                SELECT id, role, status
                FROM accounts
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$accountId]);

            $targetAccount = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$targetAccount) {
                throw new Exception(
                    'Account not found.'
                );
            }

            // Protect all Super Admin accounts.
            if ($targetAccount['role'] === 'super_admin') {
                throw new Exception(
                    'Super Admin accounts cannot be disabled.'
                );
            }

            $newStatus = $targetAccount['status'] === 'Active'
                ? 'Disabled'
                : 'Active';

            $stmt = $pdo->prepare("
                UPDATE accounts
                SET status = ?
                WHERE id = ?
                AND role != 'super_admin'
            ");

            $stmt->execute([
                $newStatus,
                $accountId
            ]);

            setAccountMessage(
                'Account status updated successfully.'
            );
        }

        else {
            throw new Exception(
                'Invalid account action.'
            );
        }

    } catch (PDOException $e) {

        // Log database errors without exposing technical details.
        error_log(
            'Account Management Database Error: ' . $e->getMessage()
        );

        if ($e->getCode() === '23000') {

            setAccountError(
                'The operation could not be completed because the username or employee assignment already exists.'
            );

        } else {

            setAccountError(
                'A database error occurred. Please check your account information and try again.'
            );

        }

    } catch (Exception $e) {

        setAccountError(
            $e->getMessage()
        );
    }
}

/* =========================================
   GET EMPLOYEES
========================================= */

$employeeStmt = $pdo->query("
    SELECT
        id,
        user_id_number,
        full_name
    FROM users
    WHERE status = 'Enabled'
    ORDER BY full_name ASC
");

$employees = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================
   GET ACCOUNT STATISTICS
========================================= */

$statsStmt = $pdo->query("
    SELECT
        COUNT(*) AS total_accounts,

        SUM(
            CASE
                WHEN status = 'Active'
                THEN 1
                ELSE 0
            END
        ) AS active_accounts,

        SUM(
            CASE
                WHEN status = 'Disabled'
                THEN 1
                ELSE 0
            END
        ) AS disabled_accounts,

        SUM(
            CASE
                WHEN role = 'admin'
                THEN 1
                ELSE 0
            END
        ) AS admin_accounts,

        SUM(
            CASE
                WHEN role = 'user'
                THEN 1
                ELSE 0
            END
        ) AS user_accounts

    FROM accounts
");

$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

/* =========================================
   GET REGISTERED ACCOUNTS
========================================= */

$accountStmt = $pdo->query("
    SELECT
        a.id,
        a.employee_id,
        a.username,
        a.role,
        a.status,
        a.created_at,
        u.full_name,
        u.user_id_number

    FROM accounts a

    LEFT JOIN users u
        ON a.employee_id = u.id

    ORDER BY a.id DESC
");

$accounts = $accountStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Account Management</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #263238;
        }

        .page-container {
            max-width: 1400px;
            margin: auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0 0 7px;
            font-size: 26px;
        }

        .page-header p {
            margin: 0;
            color: #777;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e7eaf0;
        }

        .stat-card p {
            margin: 0 0 10px;
            color: #777;
            font-size: 13px;
        }

        .stat-card h2 {
            margin: 0;
            font-size: 27px;
        }

        .content-card {
            background: #fff;
            padding: 24px;
            border-radius: 10px;
            border: 1px solid #e7eaf0;
            margin-bottom: 25px;
        }

        .content-card h2 {
            margin: 0 0 20px;
            font-size: 19px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
        }

        input,
        select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d8dee8;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #2563eb;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        button {
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-edit {
            background: #e0edff;
            color: #1d4ed8;
        }

        .btn-reset {
            background: #fff0d9;
            color: #9a5b00;
        }

        .btn-disable {
            background: #fee2e2;
            color: #b91c1c;
        }

        .btn-enable {
            background: #dcfce7;
            color: #166534;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .table-toolbar input {
            max-width: 300px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th,
        td {
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid #edf0f4;
            font-size: 13px;
        }

        th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-active {
            background: #dcfce7;
            color: #166534;
        }

        .badge-disabled {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-admin {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-user {
            background: #f1f5f9;
            color: #475569;
        }

        .super-admin-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .action-group {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        details {
            margin-top: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 7px;
            padding: 10px;
            background: #fafafa;
        }

        summary {
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
        }

        .inline-form {
            margin-top: 12px;
        }

        .inline-form .form-group {
            margin-bottom: 12px;
        }

        .password-note {
            font-size: 11px;
            color: #64748b;
            margin-top: 5px;
        }

        .empty-state {
            text-align: center;
            color: #777;
            padding: 30px;
        }

        @media (max-width: 650px) {

            body {
                padding: 12px;
            }

            .content-card {
                padding: 16px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: auto;
            }

        }

    </style>

</head>

<body>

<div class="page-container">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <h1>Account Management</h1>

            <p>
                Manage system accounts, permissions, and passwords.
            </p>

        </div>

    </div>

    <!-- ALERT MESSAGES -->

    <?php if ($message): ?>

        <div class="alert alert-success">
            <?= e($message) ?>
        </div>

    <?php endif; ?>

    <?php if ($error): ?>

        <div class="alert alert-error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>

    <!-- ACCOUNT STATISTICS -->

    <div class="stats-grid">

        <div class="stat-card">

            <p>Total Accounts</p>

            <h2>
                <?= (int) ($stats['total_accounts'] ?? 0) ?>
            </h2>

        </div>

        <div class="stat-card">

            <p>Active Accounts</p>

            <h2>
                <?= (int) ($stats['active_accounts'] ?? 0) ?>
            </h2>

        </div>

        <div class="stat-card">

            <p>Disabled Accounts</p>

            <h2>
                <?= (int) ($stats['disabled_accounts'] ?? 0) ?>
            </h2>

        </div>

        <div class="stat-card">

            <p>Admin Accounts</p>

            <h2>
                <?= (int) ($stats['admin_accounts'] ?? 0) ?>
            </h2>

        </div>

        <div class="stat-card">

            <p>User Accounts</p>

            <h2>
                <?= (int) ($stats['user_accounts'] ?? 0) ?>
            </h2>

        </div>

    </div>

    <!-- CREATE ACCOUNT -->

    <div class="content-card">

        <h2>Create New Account</h2>

        <form method="POST" action="account_management.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($csrfToken) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="create"
            >

            <div class="form-grid">

                <div class="form-group">

                    <label>Username</label>

                    <input
                        type="text"
                        name="username"
                        placeholder="Enter username"
                        minlength="3"
                        maxlength="100"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>Account Role</label>

                    <select name="role" required>

                        <option value="">Select Role</option>

                        <option value="admin">Admin</option>

                        <option value="user">User</option>

                    </select>

                </div>

                <div class="form-group">

                    <label>Employee</label>

                    <select name="employee_id">

                        <option value="">
                            Select Employee (Optional for Admin)
                        </option>

                        <?php foreach ($employees as $employee): ?>

                            <option value="<?= (int) $employee['id'] ?>">

                                <?= e($employee['full_name']) ?>

                                -

                                <?= e($employee['user_id_number']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label>Password</label>

                    <input
                        type="password"
                        name="password"
                        minlength="12"
                        autocomplete="new-password"
                        placeholder="Minimum 12 characters"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>Confirm Password</label>

                    <input
                        type="password"
                        name="confirm_password"
                        minlength="12"
                        autocomplete="new-password"
                        placeholder="Confirm password"
                        required
                    >

                </div>

                <div class="form-group full-width">

                    <button type="submit" class="btn-primary">
                        Create Account
                    </button>

                </div>

            </div>

        </form>

    </div>

    <!-- REGISTERED ACCOUNTS -->

    <div class="content-card">

        <h2>Registered Accounts</h2>

        <div class="table-toolbar">

            <p>
                Manage registered system accounts.
            </p>

            <input
                type="text"
                id="accountSearch"
                placeholder="Search username, employee, role..."
            >

        </div>

        <div class="table-responsive">

            <table id="accountTable">

                <thead>

                    <tr>

                        <th>Username</th>

                        <th>Employee</th>

                        <th>Role</th>

                        <th>Status</th>

                        <th>Date Created</th>

                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody>

                <?php if (count($accounts) > 0): ?>

                    <?php foreach ($accounts as $account): ?>

                        <?php

                        $isSuperAdmin =
                            $account['role'] === 'super_admin';

                        $isActive =
                            $account['status'] === 'Active';

                        ?>

                        <tr class="account-row">

                            <td>

                                <strong>
                                    <?= e($account['username']) ?>
                                </strong>

                            </td>

                            <td>

                                <?php if (!empty($account['full_name'])): ?>

                                    <?= e($account['full_name']) ?>

                                    <br>

                                    <small>
                                        <?= e($account['user_id_number']) ?>
                                    </small>

                                <?php else: ?>

                                    <span style="color:#94a3b8;">
                                        Not linked
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($isSuperAdmin): ?>

                                    <span class="badge badge-admin">
                                        Super Admin
                                    </span>

                                <?php elseif ($account['role'] === 'admin'): ?>

                                    <span class="badge badge-admin">
                                        Admin
                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-user">
                                        User
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($isActive): ?>

                                    <span class="badge badge-active">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-disabled">
                                        Disabled
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= e($account['created_at'] ?? '') ?>
                            </td>

                            <td>

                                <?php if ($isSuperAdmin): ?>

                                    <span class="super-admin-label">
                                        Protected Account
                                    </span>

                                <?php else: ?>

                                    <div class="action-group">

                                        <!-- EDIT ACCOUNT BUTTON -->

                                        <button
                                            type="button"
                                            class="btn-edit"
                                            onclick="toggleDetails('edit-<?= (int) $account['id'] ?>')"
                                        >
                                            Edit
                                        </button>

                                        <!-- RESET PASSWORD BUTTON -->

                                        <button
                                            type="button"
                                            class="btn-reset"
                                            onclick="toggleDetails('reset-<?= (int) $account['id'] ?>')"
                                        >
                                            Reset Password
                                        </button>

                                        <!-- ENABLE / DISABLE BUTTON -->

                                        <form
                                            method="POST"
                                            action="account_management.php"
                                            onsubmit="return confirm('Are you sure you want to <?= $isActive ? 'disable' : 'enable' ?> this account?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($csrfToken) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="toggle_status"
                                            >

                                            <input
                                                type="hidden"
                                                name="account_id"
                                                value="<?= (int) $account['id'] ?>"
                                            >

                                            <?php if ($isActive): ?>

                                                <button
                                                    type="submit"
                                                    class="btn-disable"
                                                >
                                                    Disable
                                                </button>

                                            <?php else: ?>

                                                <button
                                                    type="submit"
                                                    class="btn-enable"
                                                >
                                                    Enable
                                                </button>

                                            <?php endif; ?>

                                        </form>

                                    </div>

                                    <!-- EDIT ACCOUNT FORM -->

                                    <details id="edit-<?= (int) $account['id'] ?>">

                                        <summary>
                                            Edit Account Information
                                        </summary>

                                        <form
                                            method="POST"
                                            action="account_management.php"
                                            class="inline-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($csrfToken) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="edit"
                                            >

                                            <input
                                                type="hidden"
                                                name="account_id"
                                                value="<?= (int) $account['id'] ?>"
                                            >

                                            <div class="form-group">

                                                <label>Username</label>

                                                <input
                                                    type="text"
                                                    name="username"
                                                    value="<?= e($account['username']) ?>"
                                                    minlength="3"
                                                    maxlength="100"
                                                    required
                                                >

                                            </div>

                                            <div class="form-group">

                                                <label>Account Role</label>

                                                <select name="role" required>

                                                    <option
                                                        value="admin"
                                                        <?= $account['role'] === 'admin' ? 'selected' : '' ?>
                                                    >
                                                        Admin
                                                    </option>

                                                    <option
                                                        value="user"
                                                        <?= $account['role'] === 'user' ? 'selected' : '' ?>
                                                    >
                                                        User
                                                    </option>

                                                </select>

                                            </div>

                                            <div class="form-group">

                                                <label>Linked Employee</label>

                                                <select name="employee_id">

                                                    <option value="">
                                                        No Employee Linked
                                                    </option>

                                                    <?php foreach ($employees as $employee): ?>

                                                        <option
                                                            value="<?= (int) $employee['id'] ?>"
                                                            <?= (int) $account['employee_id'] === (int) $employee['id'] ? 'selected' : '' ?>
                                                        >

                                                            <?= e($employee['full_name']) ?>

                                                            -

                                                            <?= e($employee['user_id_number']) ?>

                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

                                            </div>

                                            <button
                                                type="submit"
                                                class="btn-primary"
                                            >
                                                Save Changes
                                            </button>

                                        </form>

                                    </details>

                                    <!-- RESET PASSWORD FORM -->

                                    <details id="reset-<?= (int) $account['id'] ?>">

                                        <summary>
                                            Reset Account Password
                                        </summary>

                                        <form
                                            method="POST"
                                            action="account_management.php"
                                            class="inline-form"
                                            onsubmit="return confirm('Reset the password for this account?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($csrfToken) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="reset_password"
                                            >

                                            <input
                                                type="hidden"
                                                name="account_id"
                                                value="<?= (int) $account['id'] ?>"
                                            >

                                            <div class="form-group">

                                                <label>New Password</label>

                                                <input
                                                    type="password"
                                                    name="new_password"
                                                    minlength="12"
                                                    autocomplete="new-password"
                                                    placeholder="Enter new password"
                                                    required
                                                >

                                            </div>

                                            <div class="form-group">

                                                <label>Confirm New Password</label>

                                                <input
                                                    type="password"
                                                    name="confirm_password"
                                                    minlength="12"
                                                    autocomplete="new-password"
                                                    placeholder="Confirm new password"
                                                    required
                                                >

                                            </div>

                                            <p class="password-note">

                                                Password must contain at least
                                                12 characters. It will be securely
                                                hashed before being saved.

                                            </p>

                                            <button
                                                type="submit"
                                                class="btn-primary"
                                            >
                                                Confirm Password Reset
                                            </button>

                                        </form>

                                    </details>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6" class="empty-state">
                            No accounts found.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script>

/* =========================================
   SEARCH ACCOUNTS
========================================= */

const accountSearch = document.getElementById('accountSearch');

if (accountSearch) {

    accountSearch.addEventListener('input', function () {

        const searchValue = this.value.toLowerCase();

        const rows = document.querySelectorAll('.account-row');

        rows.forEach(function (row) {

            const rowText = row.innerText.toLowerCase();

            row.style.display = rowText.includes(searchValue)
                ? ''
                : 'none';

        });

    });

}

/* =========================================
   OPEN / CLOSE ACCOUNT DETAILS
========================================= */

function toggleDetails(id) {

    const selected = document.getElementById(id);

    if (!selected) {
        return;
    }

    const allDetails = document.querySelectorAll('td details');

    allDetails.forEach(function (item) {

        if (item !== selected) {
            item.open = false;
        }

    });

    selected.open = !selected.open;

}

</script>

</body>
</html>
