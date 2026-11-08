
<?php
require_once __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| PAGE ACCESS
|--------------------------------------------------------------------------
| Only Super Admin can access Role Management.
*/

require_role(['super_admin']);

/*
|--------------------------------------------------------------------------
| SECURITY
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['role_csrf'])) {
    $_SESSION['role_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['role_csrf'];

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| AVAILABLE ROLES
|--------------------------------------------------------------------------
| Super Admin is intentionally not editable.
*/

$editableRoles = [
    'admin' => 'Admin',
    'user'  => 'User'
];

/*
|--------------------------------------------------------------------------
| PERMISSION DEFINITIONS
|--------------------------------------------------------------------------
*/

$permissionGroups = [

    'Dashboard' => [
        'view_dashboard' => [
            'label' => 'View Dashboard',
            'description' => 'Access the system dashboard.'
        ]
    ],

    'Equipment / Inventory' => [
        'view_inventory' => [
            'label' => 'View Equipment Records',
            'description' => 'View computer and printer records.'
        ],
        'manage_inventory' => [
            'label' => 'Manage Equipment',
            'description' => 'Add and edit computer and printer records.'
        ]
    ],

    'Employee Management' => [
        'view_employees' => [
            'label' => 'View Employee Records',
            'description' => 'View employee records and profiles.'
        ],
        'manage_employees' => [
            'label' => 'Manage Employees',
            'description' => 'Create, edit, and manage employee records.'
        ]
    ],

    'Account Management' => [
        'manage_accounts' => [
            'label' => 'Manage Accounts',
            'description' => 'Create, edit, and enable or disable accounts.'
        ],
        'reset_admin_password' => [
            'label' => 'Reset Account Passwords',
            'description' => 'Reset passwords for other system accounts.'
        ]
    ],

    'System Administration' => [
        'manage_roles' => [
            'label' => 'Manage Roles and Permissions',
            'description' => 'Configure access permissions for other roles.'
        ],
        'manage_system_settings' => [
            'label' => 'Manage System Settings',
            'description' => 'Access and modify system-wide settings.'
        ]
    ]
];

/*
|--------------------------------------------------------------------------
| RESTRICTED PERMISSIONS
|--------------------------------------------------------------------------
| These permissions are exclusive to Super Admin.
*/

$restrictedPermissions = [
    'manage_accounts',
    'manage_roles',
    'reset_admin_password',
    'manage_system_settings'
];

/*
|--------------------------------------------------------------------------
| BUILD COMPLETE PERMISSION LIST
|--------------------------------------------------------------------------
*/

$allPermissions = [];

foreach ($permissionGroups as $groupName => $permissions) {
    foreach ($permissions as $permissionKey => $details) {
        $allPermissions[] = $permissionKey;
    }
}

/*
|--------------------------------------------------------------------------
| DEFAULT ROLE PERMISSIONS
|--------------------------------------------------------------------------
*/

$rolePermissions = [
    'admin' => [],
    'user'  => []
];

/*
|--------------------------------------------------------------------------
| HANDLE SAVE PERMISSIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        /*
        |--------------------------------------------------------------------------
        | CSRF VALIDATION
        |--------------------------------------------------------------------------
        */

        $submittedToken = $_POST['csrf_token'] ?? '';

        if (
            !is_string($submittedToken) ||
            $submittedToken === '' ||
            !hash_equals($csrfToken, $submittedToken)
        ) {
            throw new Exception(
                'Invalid security token. Please refresh the page and try again.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ROLE VALIDATION
        |--------------------------------------------------------------------------
        */

        $role = $_POST['role'] ?? '';

        if (
            !is_string($role) ||
            !array_key_exists($role, $editableRoles)
        ) {
            throw new Exception('Invalid role selected.');
        }

        /*
        |--------------------------------------------------------------------------
        | PERMISSION VALIDATION
        |--------------------------------------------------------------------------
        */

        $submittedPermissions = $_POST['permissions'] ?? [];

        if (!is_array($submittedPermissions)) {
            throw new Exception('Invalid permission data.');
        }

        $validatedPermissions = [];

        foreach ($submittedPermissions as $permission) {

            if (
                !is_string($permission) ||
                !in_array($permission, $allPermissions, true)
            ) {
                throw new Exception(
                    'An invalid permission was submitted.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | RESTRICTED PERMISSION CHECK
            |--------------------------------------------------------------------------
            */

            if (
                in_array(
                    $permission,
                    $restrictedPermissions,
                    true
                )
            ) {
                throw new Exception(
                    'Sensitive permissions are reserved for Super Admin.'
                );
            }

            $validatedPermissions[] = $permission;
        }

        /*
        |--------------------------------------------------------------------------
        | REMOVE DUPLICATE PERMISSIONS
        |--------------------------------------------------------------------------
        */

        $validatedPermissions = array_values(
            array_unique($validatedPermissions)
        );

        /*
        |--------------------------------------------------------------------------
        | SAVE PERMISSIONS USING TRANSACTION
        |--------------------------------------------------------------------------
        */

        $pdo->beginTransaction();

        /*
         * Remove the previous permissions for this role.
         */

        $deleteStmt = $pdo->prepare("
            DELETE FROM role_permissions
            WHERE role = ?
        ");

        $deleteStmt->execute([$role]);

        /*
         * Insert the newly selected permissions.
         */

        if (!empty($validatedPermissions)) {

            $insertStmt = $pdo->prepare("
                INSERT INTO role_permissions
                    (role, permission)
                VALUES
                    (?, ?)
            ");

            foreach ($validatedPermissions as $permission) {

                $insertStmt->execute([
                    $role,
                    $permission
                ]);
            }
        }

        /*
         * Commit transaction.
         */

        $pdo->commit();

        $message = $editableRoles[$role] .
            ' permissions successfully updated.';

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Role Management Database Error: ' .
            $e->getMessage()
        );

        $error = 'Unable to save permissions because a database error occurred.';

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Role Management Error: ' .
            $e->getMessage()
        );

        $error = $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| GET CURRENT ROLE PERMISSIONS
|--------------------------------------------------------------------------
*/

try {

    $permissionStmt = $pdo->query("
        SELECT role, permission
        FROM role_permissions
        WHERE role IN ('admin', 'user')
        ORDER BY role, permission
    ");

    while ($row = $permissionStmt->fetch(PDO::FETCH_ASSOC)) {

        $savedRole = $row['role'] ?? '';
        $savedPermission = $row['permission'] ?? '';

        /*
         * Only load recognized roles and permissions.
         */

        if (
            isset($rolePermissions[$savedRole]) &&
            in_array(
                $savedPermission,
                $allPermissions,
                true
            ) &&
            !in_array(
                $savedPermission,
                $restrictedPermissions,
                true
            )
        ) {

            $rolePermissions[$savedRole][] = $savedPermission;
        }
    }

} catch (PDOException $e) {

    error_log(
        'Role Permission Loading Error: ' .
        $e->getMessage()
    );

    $error = $error ?: 'Unable to load the saved role permissions. Please check the database.';
}

/*
|--------------------------------------------------------------------------
| SELECTED ROLE
|--------------------------------------------------------------------------
*/

$selectedRole = $_POST['role'] ?? $_GET['role'] ?? 'admin';

if (
    !is_string($selectedRole) ||
    !array_key_exists($selectedRole, $editableRoles)
) {
    $selectedRole = 'admin';
}

/*
|--------------------------------------------------------------------------
| SELECTED PERMISSIONS
|--------------------------------------------------------------------------
*/

$selectedPermissions = $rolePermissions[$selectedRole];

/*
 * If saving failed, preserve the submitted selections
 * so the user does not need to select them again.
 */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $error !== '' &&
    isset($_POST['permissions']) &&
    is_array($_POST['permissions'])
) {

    $selectedPermissions = array_values(
        array_filter(
            $_POST['permissions'],
            function ($permission) use (
                $allPermissions,
                $restrictedPermissions
            ) {
                return is_string($permission)
                    && in_array($permission, $allPermissions, true)
                    && !in_array($permission, $restrictedPermissions, true);
            }
        )
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Role and Permission Management</title>

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
            max-width: 1100px;
            margin: auto;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 26px;
        }

        .page-header p {
            margin: 0;
            color: #777;
            font-size: 14px;
        }

        .content-card {
            background: #fff;
            border: 1px solid #e7eaf0;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 22px;
        }

        .content-card h2 {
            margin: 0 0 18px;
            font-size: 19px;
        }

        .role-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
        }

        .role-card {
            padding: 18px;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            background: #fff;
        }

        .role-card h3 {
            margin: 0 0 9px;
            font-size: 16px;
        }

        .role-card p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }

        .role-badge {
            display: inline-block;
            margin-top: 12px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-super {
            color: #166534;
            background: #dcfce7;
        }

        .badge-admin {
            color: #1d4ed8;
            background: #dbeafe;
        }

        .badge-user {
            color: #475569;
            background: #f1f5f9;
        }

        .alert {
            padding: 13px 16px;
            border-radius: 7px;
            margin-bottom: 18px;
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        select {
            width: 100%;
            max-width: 400px;
            padding: 11px 12px;
            border: 1px solid #d8dee8;
            border-radius: 6px;
            background: #fff;
            font-size: 14px;
        }

        .permission-group {
            border: 1px solid #e7eaf0;
            border-radius: 8px;
            margin-bottom: 17px;
            overflow: hidden;
        }

        .permission-group h3 {
            margin: 0;
            padding: 14px 16px;
            background: #f8fafc;
            font-size: 15px;
            border-bottom: 1px solid #e7eaf0;
        }

        .permission-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 15px 16px;
            border-bottom: 1px solid #edf0f4;
        }

        .permission-item:last-child {
            border-bottom: none;
        }

        .permission-item input {
            width: 17px;
            height: 17px;
            margin: 2px 0 0;
            cursor: pointer;
            flex-shrink: 0;
        }

        .permission-info {
            flex: 1;
        }

        .permission-info strong {
            display: block;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .permission-info small {
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        .restricted-note {
            color: #991b1b;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            padding: 13px 15px;
            border-radius: 7px;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 18px;
        }

        .super-admin-box {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            border-radius: 8px;
            padding: 17px;
            line-height: 1.6;
            font-size: 13px;
            color: #166534;
        }

        .btn-primary {
            border: none;
            background: #2563eb;
            color: #fff;
            padding: 12px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        @media (max-width: 700px) {

            body {
                padding: 12px;
            }

            .content-card {
                padding: 16px;
            }

            .role-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="page-container">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <h1>Role and Permission Management</h1>

        <p>
            Configure which features Admin and User accounts can access.
        </p>

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

    <!-- AVAILABLE ROLES -->

    <div class="content-card">

        <h2>System Roles</h2>

        <div class="role-grid">

            <div class="role-card">

                <h3>Super Admin</h3>

                <p>
                    Full access to all system features, accounts,
                    roles, permissions, and system settings.
                </p>

                <span class="role-badge badge-super">
                    Full Access
                </span>

            </div>

            <div class="role-card">

                <h3>Admin</h3>

                <p>
                    Access to equipment and employee management,
                    depending on assigned permissions.
                </p>

                <span class="role-badge badge-admin">
                    Configurable Access
                </span>

            </div>

            <div class="role-card">

                <h3>User</h3>

                <p>
                    Limited system access based on assigned
                    permissions.
                </p>

                <span class="role-badge badge-user">
                    Configurable Access
                </span>

            </div>

        </div>

    </div>

    <!-- SUPER ADMIN PROTECTION -->

    <div class="content-card">

        <h2>Super Admin Permissions</h2>

        <div class="super-admin-box">

            <strong>Super Admin has permanent full access.</strong>

            <br>

            Super Admin permissions cannot be removed, changed,
            or restricted through this page.

        </div>

    </div>

    <!-- EDIT ROLE PERMISSIONS -->

    <div class="content-card">

        <h2>Configure Role Permissions</h2>

        <form method="POST" action="role_management.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($csrfToken) ?>"
            >

            <div class="form-group">

                <label for="role">Select Role</label>

                <select
                    name="role"
                    id="role"
                    onchange="changeRole(this.value)"
                    required
                >

                    <?php foreach ($editableRoles as $roleKey => $roleLabel): ?>

                        <option
                            value="<?= e($roleKey) ?>"
                            <?= $selectedRole === $roleKey ? 'selected' : '' ?>
                        >
                            <?= e($roleLabel) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="restricted-note">

                <strong>Restricted permissions:</strong>

                Account Management, Role Management, Password
                Management, and System Settings remain exclusive
                to Super Admin.

            </div>

            <?php foreach ($permissionGroups as $groupName => $permissions): ?>

                <div class="permission-group">

                    <h3><?= e($groupName) ?></h3>

                    <?php foreach ($permissions as $permissionKey => $details): ?>

                        <?php
                            $isRestricted = in_array(
                                $permissionKey,
                                $restrictedPermissions,
                                true
                            );
                        ?>

                        <?php if (!$isRestricted): ?>

                            <label class="permission-item">

                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="<?= e($permissionKey) ?>"
                                    <?= in_array(
                                        $permissionKey,
                                        $selectedPermissions,
                                        true
                                    ) ? 'checked' : '' ?>
                                >

                                <span class="permission-info">

                                    <strong>
                                        <?= e($details['label']) ?>
                                    </strong>

                                    <small>
                                        <?= e($details['description']) ?>
                                    </small>

                                </span>

                            </label>

                        <?php endif; ?>

                    <?php endforeach; ?>

                </div>

            <?php endforeach; ?>

            <div class="form-actions">

                <button type="submit" class="btn-primary">
                    Save Permissions
                </button>

            </div>

        </form>

    </div>

</div>

<script>

/*
|--------------------------------------------------------------------------
| CHANGE SELECTED ROLE
|--------------------------------------------------------------------------
*/

function changeRole(role) {

    const url = new URL(window.location.href);

    url.searchParams.set('role', role);

    window.location.href = url.toString();

}

</script>

</body>
</html>
