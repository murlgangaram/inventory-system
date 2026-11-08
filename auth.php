
<?php

/*
|--------------------------------------------------------------------------
| SECURE SESSION CONFIGURATION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);

    session_start();
}

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/db.php';

// Support both $pdo and $conn database variables.
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

/*
|--------------------------------------------------------------------------
| RESTRICTED PERMISSIONS
|--------------------------------------------------------------------------
| These permissions are exclusive to Super Admin.
*/

function restricted_permissions(): array
{
    return [
        'manage_accounts',
        'manage_roles',
        'reset_admin_password',
        'manage_system_settings'
    ];
}

/*
|--------------------------------------------------------------------------
| CHECK IF ACCOUNT IS LOGGED IN
|--------------------------------------------------------------------------
*/

function require_login(): void
{
    global $pdo;

    if (empty($_SESSION['account_id'])) {
        header('Location: login.php');
        exit;
    }

    try {

        if (!isset($pdo) || !($pdo instanceof PDO)) {
            throw new RuntimeException(
                'Database connection is unavailable.'
            );
        }

        // Always verify the account against MySQL.
        $stmt = $pdo->prepare("
            SELECT
                id,
                username,
                role,
                employee_id,
                status
            FROM accounts
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            current_account_id()
        ]);

        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        // Account does not exist or is disabled.
        if (
            !$account ||
            $account['status'] !== 'Active'
        ) {

            $_SESSION = [];

            if (ini_get('session.use_cookies')) {

                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    '',
                    [
                        'expires' => time() - 42000,
                        'path' => $params['path'],
                        'domain' => $params['domain'],
                        'secure' => $params['secure'],
                        'httponly' => $params['httponly'],
                        'samesite' => $params['samesite'] ?? 'Lax'
                    ]
                );
            }

            session_destroy();

            header('Location: login.php?account=disabled');
            exit;
        }

        // Refresh account information from the database.
        $_SESSION['account_id'] = (int) $account['id'];

        $_SESSION['username'] = $account['username'];

        $_SESSION['role'] = $account['role'];

        $_SESSION['employee_id'] = $account['employee_id'];

    } catch (Throwable $ex) {

        error_log(
            'Authentication verification error: ' .
            $ex->getMessage()
        );

        http_response_code(503);

        exit(
            'Unable to verify your account. Please try again later.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| CHECK ALLOWED ACCOUNT ROLES
|--------------------------------------------------------------------------
*/

function require_role(array $allowedRoles): void
{
    require_login();

    if (!in_array(current_role(), $allowedRoles, true)) {

        http_response_code(403);

        exit(
            'Access denied. You do not have permission to access this page.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| GET CURRENT ACCOUNT INFORMATION
|--------------------------------------------------------------------------
*/

function current_role(): string
{
    return (string) ($_SESSION['role'] ?? '');
}

function current_username(): string
{
    return (string) ($_SESSION['username'] ?? '');
}

function current_account_id(): int
{
    return (int) ($_SESSION['account_id'] ?? 0);
}

function current_employee_id(): ?int
{
    $employeeId = $_SESSION['employee_id'] ?? null;

    return $employeeId !== null
        ? (int) $employeeId
        : null;
}

/*
|--------------------------------------------------------------------------
| ACCOUNT ROLE CHECKS
|--------------------------------------------------------------------------
*/

function is_super_admin(): bool
{
    return current_role() === 'super_admin';
}

function is_admin(): bool
{
    return current_role() === 'admin';
}

function is_user(): bool
{
    return current_role() === 'user';
}

/*
|--------------------------------------------------------------------------
| ROLE-BASED ACCESS CONTROL
|--------------------------------------------------------------------------
*/

function require_super_admin(): void
{
    require_role(['super_admin']);
}

function require_admin_or_super_admin(): void
{
    require_role([
        'super_admin',
        'admin'
    ]);
}

/*
|--------------------------------------------------------------------------
| DATABASE PERMISSION CHECK
|--------------------------------------------------------------------------
*/

function has_permission(string $permission): bool
{
    global $pdo;

    // An unauthenticated account receives no permissions.
    if (empty($_SESSION['account_id'])) {
        return false;
    }

    // Super Admin always has full access.
    if (is_super_admin()) {
        return true;
    }

    $role = current_role();

    // Only Admin and User have configurable permissions.
    if (!in_array($role, ['admin', 'user'], true)) {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | PROTECT SENSITIVE PERMISSIONS
    |--------------------------------------------------------------------------
    | Even if a restricted permission is accidentally inserted
    | into role_permissions, Admin and User cannot receive it.
    */

    if (
        in_array(
            $permission,
            restricted_permissions(),
            true
        )
    ) {
        return false;
    }

    try {

        if (!isset($pdo) || !($pdo instanceof PDO)) {
            throw new RuntimeException(
                'Database connection is unavailable.'
            );
        }

        $stmt = $pdo->prepare("
            SELECT 1
            FROM role_permissions
            WHERE role = ?
            AND permission = ?
            LIMIT 1
        ");

        $stmt->execute([
            $role,
            $permission
        ]);

        return (bool) $stmt->fetchColumn();

    } catch (Throwable $e) {

        error_log(
            'Permission check error: ' .
            $e->getMessage()
        );

        // Fail securely if permissions cannot be verified.
        return false;
    }
}

/*
|--------------------------------------------------------------------------
| REQUIRE A SPECIFIC PERMISSION
|--------------------------------------------------------------------------
*/

function require_permission(string $permission): void
{
    require_login();

    if (!has_permission($permission)) {

        http_response_code(403);

        exit(
            'Access denied. You do not have permission to perform this action.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| PERMISSION SHORTCUT FUNCTIONS
|--------------------------------------------------------------------------
*/

// Dashboard
function can_view_dashboard(): bool
{
    return has_permission('view_dashboard');
}

// Inventory
function can_manage_inventory(): bool
{
    return has_permission('manage_inventory');
}

function can_view_inventory(): bool
{
    return has_permission('view_inventory');
}

// Employees
function can_manage_employees(): bool
{
    return has_permission('manage_employees');
}

function can_view_employees(): bool
{
    return has_permission('view_employees');
}

// Accounts
function can_manage_accounts(): bool
{
    return has_permission('manage_accounts');
}

// Roles
function can_manage_roles(): bool
{
    return has_permission('manage_roles');
}

// Password management
function can_reset_admin_password(): bool
{
    return has_permission('reset_admin_password');
}

// System settings
function can_manage_system_settings(): bool
{
    return has_permission('manage_system_settings');
}
