
<?php

require_once __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| DASHBOARD ACCESS
|--------------------------------------------------------------------------
*/

require_permission('view_dashboard');

$role = current_role();

$isSuperAdmin = is_super_admin();
$isAdmin = is_admin();
$isUser = is_user();

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
*/

$canViewDashboard = has_permission('view_dashboard');

$canViewInventory = has_permission('view_inventory');
$canManageInventory = has_permission('manage_inventory');

$canViewEmployees = has_permission('view_employees');
$canManageEmployees = has_permission('manage_employees');

$canManageAccounts = has_permission('manage_accounts');
$canManageRoles = has_permission('manage_roles');

$canManageSystemSettings = has_permission('manage_system_settings');

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
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

$computerCount = 0;
$printerCount = 0;
$employeeCount = 0;
$maintenanceCount = 0;
$retiredCount = 0;

$dashboardError = '';

if ($canViewInventory) {

    try {

        // Total computers
        $computerCount = (int) $pdo->query("
            SELECT COUNT(*)
            FROM computers
        ")->fetchColumn();

        // Total printers
        $printerCount = (int) $pdo->query("
            SELECT COUNT(*)
            FROM printers
        ")->fetchColumn();

        // Total employees
        if ($canViewEmployees) {

            $employeeCount = (int) $pdo->query("
                SELECT COUNT(*)
                FROM users
            ")->fetchColumn();

        }

        // Computers under maintenance
        $maintenanceComputers = (int) $pdo->query("
            SELECT COUNT(*)
            FROM computers
            WHERE LOWER(TRIM(status)) = 'for maintenance'
        ")->fetchColumn();

        // Printers under maintenance
        $maintenancePrinters = (int) $pdo->query("
            SELECT COUNT(*)
            FROM printers
            WHERE LOWER(TRIM(status)) = 'for maintenance'
        ")->fetchColumn();

        // Retired computers
        $retiredComputers = (int) $pdo->query("
            SELECT COUNT(*)
            FROM computers
            WHERE LOWER(TRIM(status)) = 'retired'
        ")->fetchColumn();

        // Retired printers
        $retiredPrinters = (int) $pdo->query("
            SELECT COUNT(*)
            FROM printers
            WHERE LOWER(TRIM(status)) = 'retired'
        ")->fetchColumn();

        // Combined statistics
        $maintenanceCount =
            $maintenanceComputers + $maintenancePrinters;

        $retiredCount =
            $retiredComputers + $retiredPrinters;

    } catch (PDOException $ex) {

        error_log(
            'Dashboard statistics error: ' . $ex->getMessage()
        );

        $dashboardError =
            'Unable to load dashboard statistics. Please check your database tables.';

    }
}

$totalInventory = $computerCount + $printerCount;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - Inventory System</title>

    <link rel="stylesheet" href="index.css">

</head>

<body>

<!-- =====================================
     SIDEBAR
===================================== -->

<aside class="sidebar">

    <div class="brand">
        Inventory System
    </div>

    <div class="section-label">
        Main Menu
    </div>

    <nav>

        <!-- DASHBOARD -->

        <?php if ($canViewDashboard): ?>

            <a class="nav-link active" href="index.php">
                <span>▦</span> Dashboard
            </a>

        <?php endif; ?>


        <!-- EQUIPMENT RECORD -->

        <?php if ($canViewInventory): ?>

            <a class="nav-link" href="record.php">
                <span>▤</span> Equipment Record
            </a>

        <?php endif; ?>


        <!-- EMPLOYEE RECORD -->

        <?php if ($canViewEmployees): ?>

            <a class="nav-link" href="user.php">
                <span>♙</span> Employee Record
            </a>

        <?php endif; ?>


        <!-- ADMINISTRATION -->

        <?php if ($canManageAccounts || $canManageRoles || $isSuperAdmin): ?>

            <div class="section-label">
                Administration
            </div>

            <!-- ACCOUNT MANAGEMENT -->

            <?php if ($canManageAccounts): ?>

                <a class="nav-link" href="account_management.php">
                    <span>⚙</span> Account Management
                </a>

            <?php endif; ?>


            <!-- ROLE MANAGEMENT -->

            <?php if ($canManageRoles): ?>

                <a class="nav-link" href="role_management.php">
                    <span>♙</span> Role Management
                </a>

            <?php endif; ?>


            <!-- ACTIVITY LOGS -->

            <?php if ($isSuperAdmin): ?>

                <a class="nav-link" href="activity_logs.php">
                    <span>◷</span> Activity Logs
                </a>

                <a class="nav-link" href="reports.php">
                    <span>▤</span> Reports
                </a>

            <?php endif; ?>


            <!-- SYSTEM SETTINGS -->

            <?php if ($canManageSystemSettings): ?>

                <a class="nav-link" href="system_settings.php">
                    <span>⚙</span> System Settings
                </a>

            <?php endif; ?>

        <?php endif; ?>


        <!-- EMPLOYEE USER MENU -->

        <?php if ($isUser): ?>

            <div class="section-label">
                My Account
            </div>

            <a class="nav-link" href="my_profile.php">
                <span>♙</span> My Profile
            </a>

        <?php endif; ?>


        <!-- LOGOUT -->

        <a class="nav-link" href="logout.php">
            <span>↪</span> Logout
        </a>

    </nav>

</aside>


<!-- =====================================
     MAIN CONTENT
===================================== -->

<main class="main">

    <!-- HEADER -->

    <header class="header">

        <div>

            <h1>Dashboard</h1>

            <p class="muted">

                Welcome, <?= e(current_username()) ?>!

                (<?= e(ucwords(str_replace('_', ' ', $role))) ?>)

            </p>

        </div>


        <?php if ($canManageInventory): ?>

            <a class="button" href="record.php">
                + Manage Inventory
            </a>

        <?php endif; ?>

    </header>


    <!-- DATABASE ERROR -->

    <?php if ($dashboardError !== ''): ?>

        <div class="error">
            <?= e($dashboardError) ?>
        </div>

    <?php endif; ?>


    <!-- =================================
         INVENTORY DASHBOARD
    ================================== -->

    <?php if ($canViewInventory): ?>

        <p class="muted">
            Overview of your inventory and employee records.
        </p>


        <!-- INVENTORY STATISTICS -->

        <div class="stats">


            <!-- COMPUTERS -->

            <section class="stat">

                <span class="muted">
                    Total Computers
                </span>

                <strong>
                    <?= $computerCount ?>
                </strong>

                <a href="record.php">
                    View computer records →
                </a>

            </section>


            <!-- PRINTERS -->

            <section class="stat">

                <span class="muted">
                    Total Printers
                </span>

                <strong>
                    <?= $printerCount ?>
                </strong>

                <a href="record.php#printer-list">
                    View printer records →
                </a>

            </section>


            <!-- TOTAL INVENTORY -->

            <section class="stat">

                <span class="muted">
                    Total Inventory
                </span>

                <strong>
                    <?= $totalInventory ?>
                </strong>

                <span class="muted">
                    Computers + printers
                </span>

            </section>


            <!-- EMPLOYEES -->

            <?php if ($canViewEmployees): ?>

                <section class="stat">

                    <span class="muted">
                        Total Employees
                    </span>

                    <strong>
                        <?= $employeeCount ?>
                    </strong>

                    <a href="user.php">
                        View employees →
                    </a>

                </section>

            <?php endif; ?>


            <!-- MAINTENANCE -->

            <section class="stat">

                <span class="muted">
                    For Maintenance
                </span>

                <strong>
                    <?= $maintenanceCount ?>
                </strong>

                <a href="record.php">
                    View inventory →
                </a>

            </section>


            <!-- RETIRED -->

            <section class="stat">

                <span class="muted">
                    Retired Equipment
                </span>

                <strong>
                    <?= $retiredCount ?>
                </strong>

                <a href="record.php">
                    View inventory →
                </a>

            </section>


        </div>


        <!-- =================================
             SUPER ADMIN QUICK ACTIONS
        ================================== -->

        <?php if ($isSuperAdmin): ?>

            <section class="panel">

                <h2>Super Admin Controls</h2>

                <p class="muted">
                    Manage system accounts and monitor your inventory system.
                </p>

                <div class="quick-actions">

                    <?php if ($canManageAccounts): ?>

                        <a href="account_management.php">
                            Manage Accounts →
                        </a>

                    <?php endif; ?>


                    <?php if ($canManageRoles): ?>

                        <a href="role_management.php">
                            Manage Roles and Permissions →
                        </a>

                    <?php endif; ?>


                    <a href="activity_logs.php">
                        View Activity Logs →
                    </a>


                    <a href="reports.php">
                        Generate Reports →
                    </a>


                    <?php if ($canManageSystemSettings): ?>

                        <a href="system_settings.php">
                            System Settings →
                        </a>

                    <?php endif; ?>

                </div>

            </section>

        <?php endif; ?>


        <!-- =================================
             ADMIN INFORMATION
        ================================== -->

        <?php if ($isAdmin): ?>

            <section class="panel">

                <h2>Administrator Access</h2>

                <p class="muted">
                    Your dashboard and available features are based
                    on the permissions assigned to your account.
                    Account management and system administration
                    are restricted according to your assigned permissions.
                </p>

            </section>

        <?php endif; ?>


    <!-- =================================
         EMPLOYEE USER DASHBOARD
    ================================== -->

    <?php elseif ($isUser): ?>

        <p class="muted">
            Welcome to your employee dashboard.
        </p>

        <section class="stat">

            <h2>My Employee Profile</h2>

            <p class="muted">
                View your personal information and assigned equipment.
            </p>

            <a href="my_profile.php">
                View My Profile →
            </a>

        </section>


    <?php else: ?>

        <section class="panel">

            <h2>Welcome to the Inventory System</h2>

            <p class="muted">
                Your account currently has access to the dashboard.
                Contact your Super Admin if you need access to additional modules.
            </p>

        </section>

    <?php endif; ?>


</main>

</body>
</html>
