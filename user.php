
<?php
session_start();
require_once __DIR__ . '/db.php';

/* =========================================
   HELPER FUNCTIONS
========================================= */

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function getValue(array $row, string $key, string $default = '—'): string {
    $value = trim((string)($row[$key] ?? ''));
    return $value !== '' ? $value : $default;
}

/* =========================================
   CSRF TOKEN
========================================= */

if (empty($_SESSION['user_csrf_token'])) {
    $_SESSION['user_csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';
$showModal = false;
$fieldErrors = [];
$formAction = 'add_user';

$formData = [
    'user_id_number' => '',
    'full_name' => '',
    'position' => '',
    'job_description' => '',
    'office' => ''
];

$modalUserId = '';

/* =========================================
   HANDLE FORM SUBMISSIONS
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['user_csrf_token'], $token)
    ) {
        $error = 'Invalid session token. Please refresh the page.';

    } else {

        $action = $_POST['action'] ?? '';

        /* =================================
           ADD / EDIT USER
        ================================= */

        if ($action === 'add_user' || $action === 'edit_user') {

            $formAction = $action;

            $idNumber = trim($_POST['user_id_number'] ?? '');
            $name = trim($_POST['full_name'] ?? '');
            $position = trim($_POST['position'] ?? '');
            $jobDescription = trim($_POST['job_description'] ?? '');
            $office = trim($_POST['office'] ?? '');

            $userId = filter_var(
                $_POST['user_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $modalUserId = $action === 'edit_user'
                ? (string)($userId ?: '')
                : '';

            $formData = [
                'user_id_number' => $idNumber,
                'full_name' => $name,
                'position' => $position,
                'job_description' => $jobDescription,
                'office' => $office
            ];

            /* REQUIRED FIELD VALIDATION */

            if ($idNumber === '') {
                $fieldErrors['user_id_number'] =
                    'Please enter the ID number.';
            }

            if ($name === '') {
                $fieldErrors['full_name'] =
                    'Please enter the employee name.';
            }

            if (
                $action === 'edit_user' &&
                (!$userId || $userId <= 0)
            ) {
                $error = 'Invalid employee record. Please try again.';
            }

            /* CHECK DUPLICATE ID NUMBER */

            if (empty($fieldErrors) && $error === '') {

                try {

                    $excludeId = $action === 'edit_user'
                        ? (int)$userId
                        : 0;

                    $check = $pdo->prepare("
                        SELECT id
                        FROM users
                        WHERE user_id_number = ?
                        AND id != ?
                        LIMIT 1
                    ");

                    $check->execute([$idNumber, $excludeId]);

                    if ($check->fetch(PDO::FETCH_ASSOC)) {
                        $fieldErrors['user_id_number'] =
                            'This ID number is already registered.';
                    }

                } catch (PDOException $ex) {

                    error_log(
                        'Check employee ID error: ' . $ex->getMessage()
                    );

                    $error =
                        'Unable to validate employee information. Please try again.';
                }
            }

            /* INSERT OR UPDATE USER */

            if (empty($fieldErrors) && $error === '') {

                try {

                    if ($action === 'add_user') {

                        $stmt = $pdo->prepare("
                            INSERT INTO users (
                                user_id_number,
                                full_name,
                                position,
                                job_description,
                                office,
                                status
                            )
                            VALUES (?, ?, ?, ?, ?, 'Enabled')
                        ");

                        $stmt->execute([
                            $idNumber,
                            $name,
                            $position,
                            $jobDescription,
                            $office
                        ]);

                        header('Location: user.php?added=1');
                        exit;

                    } else {

                        $stmt = $pdo->prepare("
                            UPDATE users SET
                                user_id_number = ?,
                                full_name = ?,
                                position = ?,
                                job_description = ?,
                                office = ?
                            WHERE id = ?
                        ");

                        $stmt->execute([
                            $idNumber,
                            $name,
                            $position,
                            $jobDescription,
                            $office,
                            $userId
                        ]);

                        header('Location: user.php?updated=1');
                        exit;
                    }

                } catch (PDOException $ex) {

                    error_log(
                        'Save employee error: ' . $ex->getMessage()
                    );

                    $error =
                        'Unable to save employee information. Please try again.';
                }
            }

            if (!empty($fieldErrors) || $error !== '') {
                $showModal = true;
            }
        }

        /* =================================
           ENABLE / DISABLE EMPLOYEE
        ================================= */

        elseif ($action === 'toggle_user_status') {

            $userId = filter_var(
                $_POST['user_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $newStatus = $_POST['new_status'] ?? '';

            if (
                $userId &&
                $userId > 0 &&
                in_array($newStatus, ['Enabled', 'Disabled'], true)
            ) {

                try {

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET status = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $newStatus,
                        $userId
                    ]);

                    if ($stmt->rowCount() > 0) {

                        header('Location: user.php?status_updated=1');
                        exit;

                    }

                    /* Check whether the employee exists even if
                       the requested status is already saved. */

                    $check = $pdo->prepare("
                        SELECT id
                        FROM users
                        WHERE id = ?
                        LIMIT 1
                    ");

                    $check->execute([$userId]);

                    if ($check->fetch(PDO::FETCH_ASSOC)) {

                        header('Location: user.php?status_updated=1');
                        exit;

                    }

                    $error = 'Employee record could not be found.';

                } catch (PDOException $ex) {

                    error_log(
                        'Employee status error: ' . $ex->getMessage()
                    );

                    $error = 'Unable to update employee status.';
                }

            } else {

                $error = 'Invalid employee status request.';
            }
        }

        /* =================================
           DELETE USER HANDLER
           No delete button is displayed.
        ================================= */

        elseif ($action === 'delete_user') {

            $id = filter_var(
                $_POST['user_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if ($id && $id > 0) {

                try {

                    $stmt = $pdo->prepare("
                        DELETE FROM users
                        WHERE id = ?
                    ");

                    $stmt->execute([$id]);

                    if ($stmt->rowCount() > 0) {
                        header('Location: user.php?deleted=1');
                        exit;
                    }

                    $error = 'User could not be found.';

                } catch (PDOException $ex) {

                    error_log(
                        'Delete user error: ' . $ex->getMessage()
                    );

                    $error =
                        'Unable to remove user. Check whether equipment is assigned to this user.';
                }

            } else {

                $error = 'Invalid user selected.';
            }
        }
    }
}

/* =========================================
   FETCH USERS
========================================= */

try {

    $stmt = $pdo->query("
        SELECT
            id,
            user_id_number,
            full_name,
            position,
            job_description,
            office,
            status
        FROM users
        ORDER BY full_name ASC
    ");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {

    error_log('Fetch users error: ' . $ex->getMessage());

    $users = [];

    $error = 'Unable to load users. Check your users table.';
}

/* =========================================
   FETCH COMPUTERS AND PRINTERS
========================================= */

try {

    $allComputers = $pdo->query("
        SELECT *
        FROM computers
        ORDER BY id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $allPrinters = $pdo->query("
        SELECT *
        FROM printers
        ORDER BY id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {

    error_log('Fetch equipment error: ' . $ex->getMessage());

    $allComputers = [];
    $allPrinters = [];

    $error = $error ?: 'Unable to load equipment records.';
}

/* =========================================
   BUILD EMPLOYEE PROFILE DATA
========================================= */

$userProfileData = [];

foreach ($users as $user) {

    $fullName = trim($user['full_name'] ?? '');

    if ($fullName === '') {
        continue;
    }

    /* Use database ID as profile key. */

    $profileKey = (string)$user['id'];

    $userProfileData[$profileKey] = [

        'employee' => [
            'db_id' => (int)$user['id'],
            'id' => $user['user_id_number'] ?? '',
            'full_name' => $fullName,
            'position' => $user['position'] ?? '',
            'office' => $user['office'] ?? '',
            'job_description' => $user['job_description'] ?? '',
            'status' => $user['status'] ?? 'Enabled'
        ],

        'equipment' => []
    ];

    /* ASSIGNED COMPUTERS */

    foreach ($allComputers as $computer) {

        $assigned = trim(
            (string)($computer['assigned_to'] ?? '')
        );

        if (strcasecmp($assigned, $fullName) !== 0) {
            continue;
        }

        $brand = trim((string)($computer['brand'] ?? ''));
        $model = trim((string)($computer['model'] ?? ''));

        $userProfileData[$profileKey]['equipment'][] = [

            'type' => 'Computer',
            'name' => $computer['computer_name'] ?? '—',
            'office' => $computer['office'] ?? '',
            'ram' => $computer['ram'] ?? '',
            'cpu' => $computer['cpu'] ?? '',
            'ssd' => $computer['ssd'] ?? '',
            'hd' => $computer['hd'] ?? '',
            'brand_model' => trim($brand . ' ' . $model),
            'serial' => $computer['serial_number'] ?? '',
            'mac_address' => $computer['mac_address'] ?? '',
            'assigned_to' => $assigned,
            'remarks' => $computer['remarks'] ?? '',
            'status' => $computer['status'] ?? ''

        ];
    }

    /* ASSIGNED PRINTERS */

    foreach ($allPrinters as $printer) {

        $assigned = trim(
            (string)($printer['assigned_to'] ?? '')
        );

        if (strcasecmp($assigned, $fullName) !== 0) {
            continue;
        }

        $brand = trim((string)($printer['brand'] ?? ''));
        $model = trim((string)($printer['model'] ?? ''));

        $userProfileData[$profileKey]['equipment'][] = [

            'type' => 'Printer',
            'name' => $printer['printer_name'] ?? '—',
            'office' => $printer['office'] ?? '',
            'ram' => '—',
            'cpu' => '—',
            'ssd' => '—',
            'hd' => '—',
            'brand_model' => trim($brand . ' ' . $model),
            'serial' => $printer['serial_number'] ?? '',
            'mac_address' => '—',
            'assigned_to' => $assigned,
            'remarks' => $printer['remarks'] ?? '',
            'status' => $printer['status'] ?? ''

        ];
    }
}

/* =========================================
   SUCCESS MESSAGES
========================================= */

if (isset($_GET['added'])) {

    $message = 'Employee added successfully.';

} elseif (isset($_GET['updated'])) {

    $message = 'Employee updated successfully.';

} elseif (isset($_GET['deleted'])) {

    $message = 'Employee removed successfully.';

} elseif (isset($_GET['status_updated'])) {

    $message = 'Employee status updated successfully.';

}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Inventory System</title>

    <link rel="stylesheet" href="user.css">

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

        <a class="nav-link" href="index.php">
            Dashboard
        </a>

        <a class="nav-link" href="record.php">
            Equipment Record
        </a>

        <a class="nav-link active" href="user.php">
            Employee Record
        </a>

    </nav>

</aside>

<!-- =====================================
     MAIN CONTENT
===================================== -->

<main class="main">

    <header class="header">

        <h2>Assigned Employee Record</h2>

        <button
            type="button"
            class="button"
            onclick="openAddUserModal()"
        >
            Create Employee
        </button>

    </header>

    <p class="muted">
        Create and manage the list of employees who can be assigned
        to computers and printers.
    </p>

    <?php if ($message !== ''): ?>

        <div class="notice">
            <?= e($message) ?>
        </div>

    <?php endif; ?>

    <?php if ($error !== ''): ?>

        <div class="error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>

    <!-- =================================
         USER TABLE AND SEARCH
    ================================== -->

    <section class="panel">

        <div class="panel-head employee-toolbar">

            <!-- SEARCH BAR -->

            <div class="employee-search-wrapper">

                <span class="search-icon">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="20"
                        height="20"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>

                </span>

                <input
                    type="search"
                    id="employeeSearch"
                    class="employee-search-input"
                    autocomplete="off"
                    aria-label="Search employees"
                >

            </div>

            <!-- EMPLOYEE COUNT -->

            <span class="muted employee-count" id="employeeCount">

                <?= count($users) ?>

                <?= count($users) === 1 ? 'employee' : 'employees' ?>

            </span>

        </div>

        <!-- EMPLOYEE TABLE -->

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>ID NUMBER</th>
                        <th>EMPLOYEE NAME</th>
                        <th>POSITION</th>
                        <th>OFFICE</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>

                </thead>

                <tbody id="employeeTableBody">

                <?php if (empty($users)): ?>

                    <tr id="emptyEmployeeRow">

                        <td colspan="6" class="empty">
                            No employee added yet.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($users as $user): ?>

                    <?php
                        $employeeStatus = $user['status'] ?? 'Enabled';
                        $statusClass = $employeeStatus === 'Disabled'
                            ? 'disabled'
                            : 'enabled';
                    ?>

                    <tr class="employee-row">

                        <!-- ID NUMBER -->

                        <td>

                            <button
                                type="button"
                                class="user-record-link"
                                data-user-id="<?= (int)$user['id'] ?>"
                                aria-label="View employee <?= e($user['user_id_number']) ?>"
                            >

                                <?= e($user['user_id_number'] ?: '—') ?>

                            </button>

                        </td>

                        <!-- EMPLOYEE NAME -->

                        <td>

                            <button
                                type="button"
                                class="user-record-link"
                                data-user-id="<?= (int)$user['id'] ?>"
                                aria-label="View employee <?= e($user['full_name']) ?>"
                            >

                                <?= e($user['full_name']) ?>

                            </button>

                        </td>

                        <!-- POSITION -->

                        <td>
                            <?= e($user['position'] ?: '—') ?>
                        </td>

                        <!-- OFFICE -->

                        <td>
                            <?= e($user['office'] ?: '—') ?>
                        </td>

                        <!-- STATUS -->

                        <td>

                            <span class="employee-status-badge <?= e($statusClass) ?>">
                                <?= e($employeeStatus) ?>
                            </span>

                        </td>

                        <!-- ACTION -->

                        <td>

                            <button
                                type="button"
                                class="edit-button"
                                onclick='openEditUserModal(
                                    <?= json_encode(
                                        $user,
                                        JSON_HEX_APOS |
                                        JSON_HEX_QUOT |
                                        JSON_HEX_TAG |
                                        JSON_HEX_AMP
                                    ) ?>
                                )'
                            >

                                Edit

                            </button>

                        </td>

                    </tr>

                    <?php endforeach; ?>

                    <!-- NO SEARCH RESULTS -->

                    <tr id="emptyEmployeeRow" style="display: none;">

                        <td colspan="6" class="empty">
                            No matching employee found.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<!-- =====================================
     ADD / EDIT USER MODAL
===================================== -->

<div
    class="modal-overlay"
    id="addUserModal"
    aria-hidden="true"
>

    <div
        class="modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modalTitle"
    >

        <div class="modal-header">

            <h2 id="modalTitle">
                <?= $formAction === 'edit_user'
                    ? 'Edit Employee'
                    : 'Add New Employee' ?>
            </h2>

            <button
                type="button"
                class="close-modal"
                onclick="closeAddUserModal()"
                aria-label="Close modal"
            >
                ×
            </button>

        </div>

        <form method="POST" action="user.php" novalidate>

            <div class="modal-body">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['user_csrf_token']) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    id="formAction"
                    value="<?= e($formAction) ?>"
                >

                <input
                    type="hidden"
                    name="user_id"
                    id="editUserId"
                    value="<?= e($modalUserId) ?>"
                >

                <div class="field">

                    <label for="user_id_number">
                        ID Number <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="user_id_number"
                        name="user_id_number"
                        maxlength="50"
                        value="<?= e($formData['user_id_number']) ?>"
                        class="<?= isset($fieldErrors['user_id_number']) ? 'input-error' : '' ?>"
                        aria-invalid="<?= isset($fieldErrors['user_id_number']) ? 'true' : 'false' ?>"
                    >

                    <?php if (isset($fieldErrors['user_id_number'])): ?>

                        <span class="field-error">
                            <?= e($fieldErrors['user_id_number']) ?>
                        </span>

                    <?php endif; ?>

                </div>

                <div class="field">

                    <label for="full_name">
                        Employee Name <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="150"
                        value="<?= e($formData['full_name']) ?>"
                        class="<?= isset($fieldErrors['full_name']) ? 'input-error' : '' ?>"
                        aria-invalid="<?= isset($fieldErrors['full_name']) ? 'true' : 'false' ?>"
                    >

                    <?php if (isset($fieldErrors['full_name'])): ?>

                        <span class="field-error">
                            <?= e($fieldErrors['full_name']) ?>
                        </span>

                    <?php endif; ?>

                </div>

                <div class="field">

                    <label for="position">
                        Position
                    </label>

                    <input
                        type="text"
                        id="position"
                        name="position"
                        maxlength="150"
                        value="<?= e($formData['position']) ?>"
                    >

                </div>

                <div class="field">

                    <label for="job_description">
                        Job Description
                    </label>

                    <input
                        type="text"
                        id="job_description"
                        name="job_description"
                        maxlength="150"
                        value="<?= e($formData['job_description']) ?>"
                    >

                </div>

                <div class="field">

                    <label for="office">
                        Office
                    </label>

                    <input
                        type="text"
                        id="office"
                        name="office"
                        maxlength="150"
                        value="<?= e($formData['office']) ?>"
                    >

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="secondary-button"
                    onclick="closeAddUserModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="primary-button"
                    id="submitUserButton"
                >

                    <?= $formAction === 'edit_user'
                        ? 'Save Changes'
                        : 'Add Employee' ?>

                </button>

            </div>

        </form>

    </div>

</div>

<!-- =====================================
     EMPLOYEE PROFILE MODAL
===================================== -->

<div
    class="user-profile-overlay"
    id="userProfileModal"
    aria-hidden="true"
>

    <section
        class="user-profile-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="profileModalTitle"
    >

        <header class="user-profile-header">

            <div>

                <p id="profileModalTitle">
                    Employee information and assigned equipment
                </p>

            </div>

            <button
                type="button"
                class="user-profile-close"
                id="closeUserProfile"
                aria-label="Close employee profile"
            >
                ×
            </button>

        </header>

        <div class="user-profile-body">

            <!-- PROFILE TABS -->

            <div class="user-profile-tabs" role="tablist">

                <button
                    type="button"
                    class="user-profile-tab active"
                    id="overviewTab"
                    role="tab"
                    aria-selected="true"
                    aria-controls="overviewPanel"
                    onclick="switchProfileTab('overview')"
                >
                    Overview
                </button>

                <button
                    type="button"
                    class="user-profile-tab"
                    id="equipmentTab"
                    role="tab"
                    aria-selected="false"
                    aria-controls="equipmentPanel"
                    onclick="switchProfileTab('equipment')"
                >
                    Assigned Equipment
                </button>

            </div>

            <!-- =================================
                 OVERVIEW TAB
            ================================== -->

            <section
                class="user-profile-tab-panel active"
                id="overviewPanel"
                role="tabpanel"
                aria-labelledby="overviewTab"
            >

                <section class="employee-overview">

                    <div class="employee-overview-header">

                        <div
                            class="employee-avatar"
                            id="profileAvatar"
                        >
                            --
                        </div>

                        <div class="employee-heading">

                            <h3 id="profileName">
                                Employee Name
                            </h3>

                            <p id="profilePosition">
                                Position
                            </p>

                        </div>

                        <!-- EMPLOYEE STATUS AND BUTTON -->

                        <div class="employee-status-controls">

                            <span
                                id="profileStatus"
                                class="employee-status-badge enabled"
                            >
                                Enabled
                            </span>

                            <form
                                method="POST"
                                action="user.php"
                                id="employeeStatusForm"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e($_SESSION['user_csrf_token']) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="toggle_user_status"
                                >

                                <input
                                    type="hidden"
                                    name="user_id"
                                    id="profileDatabaseId"
                                    value=""
                                >

                                <input
                                    type="hidden"
                                    name="new_status"
                                    id="profileNewStatus"
                                    value=""
                                >

                                <button
                                    type="submit"
                                    class="employee-status-button disable"
                                    id="toggleEmployeeButton"
                                >
                                    Disable Employee
                                </button>

                            </form>

                        </div>

                    </div>

                    <!-- EMPLOYEE INFORMATION -->

                    <div class="employee-info-grid">

                        <div class="employee-info-item">

                            <label>User ID Number</label>

                            <span id="profileId">—</span>

                        </div>

                        <div class="employee-info-item">

                            <label>Full Name</label>

                            <span id="profileFullName">—</span>

                        </div>

                        <div class="employee-info-item">

                            <label>Position</label>

                            <span id="profileInfoPosition">—</span>

                        </div>

                        <div class="employee-info-item">

                            <label>Office</label>

                            <span id="profileOffice">—</span>

                        </div>

                        <div class="employee-info-item">

                            <label>Job Description</label>

                            <span id="profileJobDescription">—</span>

                        </div>

                    </div>

                </section>

                <!-- EQUIPMENT SUMMARY -->

                <div class="employee-equipment-summary">

                    <div class="employee-summary-item">

                        <span>Total Equipment</span>

                        <strong id="totalEquipment">0</strong>

                    </div>

                    <div class="employee-summary-item">

                        <span>Computers</span>

                        <strong id="totalComputers">0</strong>

                    </div>

                    <div class="employee-summary-item">

                        <span>Printers</span>

                        <strong id="totalPrinters">0</strong>

                    </div>

                </div>

            </section>

            <!-- =================================
                 ASSIGNED EQUIPMENT TAB
            ================================== -->

            <section
                class="user-profile-tab-panel"
                id="equipmentPanel"
                role="tabpanel"
                aria-labelledby="equipmentTab"
                hidden
            >

                <section class="employee-equipment-panel">

                    <div class="employee-equipment-panel-header">

                        <h3>Assigned Equipment</h3>

                        <input
                            type="search"
                            id="equipmentSearch"
                            class="employee-equipment-search"
                            placeholder="Search equipment..."
                        >

                    </div>

                    <div class="employee-equipment-table-wrap">

                        <table class="employee-equipment-table">

                            <thead>

                                <tr>
                                    <th>TYPE</th>
                                    <th>EQUIPMENT NAME</th>
                                    <th>OFFICE</th>
                                    <th>RAM</th>
                                    <th>CPU</th>
                                    <th>SSD</th>
                                    <th>HD</th>
                                    <th>BRAND / MODEL</th>
                                    <th>SERIAL NO.</th>
                                    <th>MAC ADDRESS</th>
                                    <th>REMARKS</th>
                                    <th>STATUS</th>
                                </tr>

                            </thead>

                            <tbody id="profileEquipmentBody">

                                <tr>

                                    <td
                                        colspan="12"
                                        class="employee-equipment-empty"
                                    >
                                        No assigned equipment.
                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </section>

            </section>

        </div>

    </section>

</div>

<!-- =====================================
     PROFILE DATA
===================================== -->

<script type="application/json" id="userProfileData"><?=
    json_encode(
        $userProfileData,
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_TAG |
        JSON_HEX_AMP
    )
?></script>

<!-- =====================================
     JAVASCRIPT
===================================== -->

<script>

/* =====================================
   ADD USER MODAL
===================================== */

const addUserModal = document.getElementById('addUserModal');

function openAddUserModal() {

    document.getElementById('modalTitle').textContent =
        'Add New Employee';

    document.getElementById('formAction').value = 'add_user';

    document.getElementById('submitUserButton').textContent =
        'Add Employee';

    document.getElementById('editUserId').value = '';

    document.getElementById('user_id_number').value = '';
    document.getElementById('full_name').value = '';
    document.getElementById('position').value = '';
    document.getElementById('job_description').value = '';
    document.getElementById('office').value = '';

    addUserModal.classList.add('show');
    addUserModal.setAttribute('aria-hidden', 'false');

    document.getElementById('user_id_number').focus();
}

/* =====================================
   EDIT USER MODAL
===================================== */

function openEditUserModal(user) {

    document.getElementById('modalTitle').textContent =
        'Edit Employee';

    document.getElementById('formAction').value = 'edit_user';

    document.getElementById('submitUserButton').textContent =
        'Save Changes';

    document.getElementById('editUserId').value = user.id;

    document.getElementById('user_id_number').value =
        user.user_id_number || '';

    document.getElementById('full_name').value =
        user.full_name || '';

    document.getElementById('position').value =
        user.position || '';

    document.getElementById('job_description').value =
        user.job_description || '';

    document.getElementById('office').value =
        user.office || '';

    addUserModal.classList.add('show');
    addUserModal.setAttribute('aria-hidden', 'false');

    document.getElementById('user_id_number').focus();
}

/* =====================================
   CLOSE ADD / EDIT MODAL
===================================== */

function closeAddUserModal() {

    addUserModal.classList.remove('show');

    addUserModal.setAttribute('aria-hidden', 'true');
}

addUserModal.addEventListener('click', function(event) {

    if (event.target === addUserModal) {
        closeAddUserModal();
    }

});

/* =====================================
   EMPLOYEE PROFILE DATA
===================================== */

const userProfileData = JSON.parse(
    document.getElementById('userProfileData').textContent
);

const userProfileModal =
    document.getElementById('userProfileModal');

const profileEquipmentBody =
    document.getElementById('profileEquipmentBody');

const equipmentSearch =
    document.getElementById('equipmentSearch');

let currentEmployeeEquipment = [];

/* =====================================
   SWITCH PROFILE TABS
===================================== */

function switchProfileTab(tabName) {

    const overviewTab = document.getElementById('overviewTab');
    const equipmentTab = document.getElementById('equipmentTab');

    const overviewPanel = document.getElementById('overviewPanel');
    const equipmentPanel = document.getElementById('equipmentPanel');

    if (tabName === 'overview') {

        overviewTab.classList.add('active');
        equipmentTab.classList.remove('active');

        overviewTab.setAttribute('aria-selected', 'true');
        equipmentTab.setAttribute('aria-selected', 'false');

        overviewPanel.classList.add('active');
        equipmentPanel.classList.remove('active');

        overviewPanel.hidden = false;
        equipmentPanel.hidden = true;

    } else {

        equipmentTab.classList.add('active');
        overviewTab.classList.remove('active');

        equipmentTab.setAttribute('aria-selected', 'true');
        overviewTab.setAttribute('aria-selected', 'false');

        equipmentPanel.classList.add('active');
        overviewPanel.classList.remove('active');

        equipmentPanel.hidden = false;
        overviewPanel.hidden = true;

    }

}

/* =====================================
   SAFE TEXT DISPLAY
===================================== */

function setProfileText(id, value) {

    document.getElementById(id).textContent =
        value && String(value).trim() !== ''
            ? value
            : '—';

}

/* =====================================
   EMPLOYEE INITIALS
===================================== */

function getInitials(name) {

    const parts = String(name || '')
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    if (parts.length === 0) {
        return '--';
    }

    return parts
        .slice(0, 2)
        .map(part => part.charAt(0).toUpperCase())
        .join('');

}

/* =====================================
   UPDATE PROFILE STATUS DISPLAY
===================================== */

function updateEmployeeStatusDisplay(status) {

    const statusBadge = document.getElementById('profileStatus');

    const statusButton =
        document.getElementById('toggleEmployeeButton');

    const newStatus =
        document.getElementById('profileNewStatus');

    const isEnabled = status === 'Enabled';

    statusBadge.textContent = status;

    statusBadge.className =
        'employee-status-badge ' +
        (isEnabled ? 'enabled' : 'disabled');

    statusButton.textContent =
        isEnabled ? 'Disable Employee' : 'Enable Employee';

    statusButton.className =
        'employee-status-button ' +
        (isEnabled ? 'disable' : 'enable');

    newStatus.value =
        isEnabled ? 'Disabled' : 'Enabled';
}

/* =====================================
   OPEN EMPLOYEE PROFILE
===================================== */

function openUserProfile(userId) {

    const profileKey = String(userId);

    const profile = userProfileData[profileKey];

    if (!profile) {

        alert('Employee profile could not be found.');
        return;
    }

    const employee = profile.employee || {};
    const equipment = profile.equipment || [];

    /* EMPLOYEE DETAILS */

    setProfileText('profileName', employee.full_name);
    setProfileText('profileFullName', employee.full_name);
    setProfileText('profileId', employee.id);
    setProfileText('profilePosition', employee.position);
    setProfileText('profileInfoPosition', employee.position);
    setProfileText('profileOffice', employee.office);
    setProfileText(
        'profileJobDescription',
        employee.job_description
    );

    /* EMPLOYEE INITIALS */

    document.getElementById('profileAvatar').textContent =
        getInitials(employee.full_name);

    /* EMPLOYEE DATABASE ID */

    document.getElementById('profileDatabaseId').value =
        employee.db_id || '';

    /* EMPLOYEE STATUS */

    updateEmployeeStatusDisplay(
        employee.status || 'Enabled'
    );

    /* EQUIPMENT COUNTS */

    const computerCount = equipment.filter(item =>
        item.type === 'Computer'
    ).length;

    const printerCount = equipment.filter(item =>
        item.type === 'Printer'
    ).length;

    document.getElementById('totalEquipment').textContent =
        equipment.length;

    document.getElementById('totalComputers').textContent =
        computerCount;

    document.getElementById('totalPrinters').textContent =
        printerCount;

    /* SAVE EQUIPMENT FOR SEARCH */

    currentEmployeeEquipment = equipment;

    equipmentSearch.value = '';

    renderEmployeeEquipment(equipment);

    /* OPEN OVERVIEW TAB BY DEFAULT */

    switchProfileTab('overview');

    /* SHOW PROFILE MODAL */

    userProfileModal.classList.add('show');

    userProfileModal.setAttribute('aria-hidden', 'false');

    document.body.style.overflow = 'hidden';

    document.getElementById('closeUserProfile').focus();

}

/* =====================================
   CONFIRM ENABLE / DISABLE
===================================== */

document.getElementById('employeeStatusForm')
    .addEventListener('submit', function(event) {

        const newStatus =
            document.getElementById('profileNewStatus').value;

        const employeeName =
            document.getElementById('profileName').textContent;

        const actionText = newStatus === 'Disabled'
            ? 'disable'
            : 'enable';

        const confirmed = confirm(
            'Are you sure you want to ' +
            actionText +
            ' ' +
            employeeName +
            '?'
        );

        if (!confirmed) {
            event.preventDefault();
        }

    });

/* =====================================
   RENDER ASSIGNED EQUIPMENT
===================================== */

function renderEmployeeEquipment(equipment) {

    profileEquipmentBody.replaceChildren();

    if (!equipment.length) {

        const row = document.createElement('tr');
        const cell = document.createElement('td');

        cell.colSpan = 12;
        cell.className = 'employee-equipment-empty';

        cell.textContent =
            'No equipment assigned to this employee.';

        row.appendChild(cell);

        profileEquipmentBody.appendChild(row);

        return;
    }

    equipment.forEach(item => {

        const row = document.createElement('tr');

        const columns = [
            item.type,
            item.name,
            item.office,
            item.ram,
            item.cpu,
            item.ssd,
            item.hd,
            item.brand_model,
            item.serial,
            item.mac_address,
            item.remarks,
            item.status
        ];

        columns.forEach((value, index) => {

            const cell = document.createElement('td');

            cell.textContent =
                value && String(value).trim() !== ''
                    ? value
                    : '—';

            if (index === 0) {

                const badge = document.createElement('span');

                badge.className = 'equipment-type-badge';
                badge.textContent = value || '—';

                cell.replaceChildren(badge);

            }

            if (index === 11) {

                const badge = document.createElement('span');

                badge.className = 'equipment-status';
                badge.textContent = value || '—';

                cell.replaceChildren(badge);

            }

            row.appendChild(cell);

        });

        profileEquipmentBody.appendChild(row);

    });

}

/* =====================================
   SEARCH ASSIGNED EQUIPMENT
===================================== */

equipmentSearch.addEventListener('input', function() {

    const searchValue = this.value.trim().toLowerCase();

    const filteredEquipment = currentEmployeeEquipment.filter(item => {

        return Object.values(item).some(value =>

            String(value ?? '')
                .toLowerCase()
                .includes(searchValue)

        );

    });

    renderEmployeeEquipment(filteredEquipment);

});

/* =====================================
   CLICKABLE ID NUMBER AND FULL NAME
===================================== */

document.querySelectorAll('.user-record-link').forEach(button => {

    button.addEventListener('click', function() {

        const userId = this.dataset.userId || '';

        openUserProfile(userId);

    });

});

/* =====================================
   EMPLOYEE SEARCH
===================================== */

const employeeSearch =
    document.getElementById('employeeSearch');

const employeeCount =
    document.getElementById('employeeCount');

const emptyEmployeeRow =
    document.getElementById('emptyEmployeeRow');

function searchEmployees() {

    const searchValue = employeeSearch.value
        .trim()
        .toLowerCase();

    const employeeRows =
        document.querySelectorAll('.employee-row');

    let visibleCount = 0;

    employeeRows.forEach(row => {

        const rowText = row.textContent.toLowerCase();

        const matches = rowText.includes(searchValue);

        row.style.display = matches ? '' : 'none';

        if (matches) {
            visibleCount++;
        }

    });

    /* UPDATE EMPLOYEE COUNT */

    employeeCount.textContent =
        visibleCount +
        (visibleCount === 1 ? ' employee' : ' employees');

    /* SHOW NO RESULTS MESSAGE */

    if (emptyEmployeeRow) {

        emptyEmployeeRow.style.display =
            visibleCount === 0 ? '' : 'none';

        const emptyMessage =
            emptyEmployeeRow.querySelector('td');

        if (emptyMessage) {

            emptyMessage.textContent = searchValue
                ? 'No matching employee found.'
                : 'No employee added yet.';

        }

    }

}

/* SEARCH WHILE TYPING */

employeeSearch.addEventListener('input', searchEmployees);

/* =====================================
   CLOSE EMPLOYEE PROFILE
===================================== */

function closeUserProfile() {

    userProfileModal.classList.remove('show');

    userProfileModal.setAttribute('aria-hidden', 'true');

    document.body.style.overflow = '';

}

document.getElementById('closeUserProfile')
    .addEventListener('click', closeUserProfile);

userProfileModal.addEventListener('click', function(event) {

    if (event.target === userProfileModal) {
        closeUserProfile();
    }

});

/* =====================================
   ESCAPE KEY
===================================== */

document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {

        closeAddUserModal();

        if (userProfileModal.classList.contains('show')) {
            closeUserProfile();
        }

    }

});

/* =====================================
   REOPEN MODAL AFTER VALIDATION ERROR
===================================== */

<?php if ($showModal): ?>

addUserModal.classList.add('show');
addUserModal.setAttribute('aria-hidden', 'false');

document.body.style.overflow = 'hidden';

<?php endif; ?>

</script>

</body>
</html>
