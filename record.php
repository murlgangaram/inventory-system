
<?php
session_start();
require_once __DIR__ . '/db.php';

/* =========================================
   PAGE SETTINGS
========================================= */

$isEmbedded = isset($_GET['embed']) && $_GET['embed'] === '1';
$selectedUser = trim($_GET['assigned_to'] ?? '');

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function statusClass($status): string {
    if ($status === 'Working') return 'working';
    if ($status === 'For Maintenance') return 'maintenance';
    if ($status === 'Retired') return 'retired';
    return 'checking';
}

function redirectRecord($isEmbedded, $selectedUser, $message) {
    $url = 'record.php?' . http_build_query(array_filter([
        $message,
        'embed' => $isEmbedded ? '1' : null,
        'assigned_to' => $selectedUser !== '' ? $selectedUser : null
    ], fn($value) => $value !== null && $value !== ''));

    header('Location: ' . $url . '#inventory-list');
    exit;
}

$error = '';
$computerError = '';

/* =========================================
   UPDATE COMPUTER
========================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['form_type'] ?? '') === 'update_computer'
) {
    $id = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);

    $computer_name = trim($_POST['computer_name'] ?? '');
    $office = trim($_POST['office'] ?? '');

    if ($id && $computer_name !== '' && $office !== '') {

        $stmt = $pdo->prepare("
            UPDATE computers SET
                computer_name = ?,
                office = ?,
                ram = ?,
                cpu = ?,
                ssd = ?,
                hd = ?,
                brand = ?,
                model = ?,
                serial_number = ?,
                mac_address = ?,
                assigned_to = ?,
                remarks = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $computer_name,
            $office,
            trim($_POST['ram'] ?? ''),
            trim($_POST['cpu'] ?? ''),
            trim($_POST['ssd'] ?? ''),
            trim($_POST['hd'] ?? ''),
            trim($_POST['brand'] ?? ''),
            trim($_POST['model'] ?? ''),
            trim($_POST['serial_number'] ?? ''),
            trim($_POST['mac_address'] ?? ''),
            trim($_POST['assigned_to'] ?? ''),
            trim($_POST['remarks'] ?? ''),
            trim($_POST['status'] ?? 'Working'),
            $id
        ]);

        redirectRecord($isEmbedded, $selectedUser, [
            'updated' => 'computer'
        ]);

    } else {
        $error = 'Unable to update computer. Please check the required fields.';
    }
}

/* =========================================
   UPDATE PRINTER
========================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['form_type'] ?? '') === 'update_printer'
) {
    $id = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);

    $printer_name = trim($_POST['printer_name'] ?? '');
    $office = trim($_POST['office'] ?? '');

    if ($id && $printer_name !== '' && $office !== '') {

        $stmt = $pdo->prepare("
            UPDATE printers SET
                printer_name = ?,
                brand_model = ?,
                serial_number = ?,
                assigned_to = ?,
                office = ?,
                remarks = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $printer_name,
            trim($_POST['brand_model'] ?? ''),
            trim($_POST['serial_number'] ?? ''),
            trim($_POST['assigned_to'] ?? ''),
            $office,
            trim($_POST['remarks'] ?? ''),
            trim($_POST['status'] ?? 'Working'),
            $id
        ]);

        redirectRecord($isEmbedded, $selectedUser, [
            'updated' => 'printer'
        ]);

    } else {
        $error = 'Unable to update printer. Please check the required fields.';
    }
}

/* =========================================
   ADD COMPUTER
========================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['form_type'] ?? '') === 'computer'
) {
    $computer_name = trim($_POST['computer_name'] ?? '');
    $office = trim($_POST['office'] ?? '');

    if ($computer_name !== '' && $office !== '') {

        $stmt = $pdo->prepare("
            INSERT INTO computers (
                computer_name,
                office,
                ram,
                cpu,
                ssd,
                hd,
                brand,
                model,
                serial_number,
                mac_address,
                assigned_to,
                remarks,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $computer_name,
            $office,
            trim($_POST['ram'] ?? ''),
            trim($_POST['cpu'] ?? ''),
            trim($_POST['ssd'] ?? ''),
            trim($_POST['hd'] ?? ''),
            trim($_POST['brand'] ?? ''),
            trim($_POST['model'] ?? ''),
            trim($_POST['serial_number'] ?? ''),
            trim($_POST['mac_address'] ?? ''),
            trim($_POST['assigned_to'] ?? ''),
            trim($_POST['remarks'] ?? ''),
            trim($_POST['status'] ?? 'Working')
        ]);

        redirectRecord($isEmbedded, $selectedUser, [
            'added' => 'computer'
        ]);

    } else {
        $computerError = 'Please enter the Computer Name and select an Office.';
    }
}

/* =========================================
   ADD PRINTER
========================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['form_type'] ?? '') === 'printer'
) {
    $printer_name = trim($_POST['printer_name'] ?? '');
    $office = trim($_POST['office'] ?? '');

    if ($printer_name !== '' && $office !== '') {

        $stmt = $pdo->prepare("
            INSERT INTO printers (
                printer_name,
                brand_model,
                serial_number,
                assigned_to,
                office,
                remarks,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $printer_name,
            trim($_POST['brand_model'] ?? ''),
            trim($_POST['serial_number'] ?? ''),
            trim($_POST['assigned_to'] ?? ''),
            $office,
            trim($_POST['remarks'] ?? ''),
            trim($_POST['status'] ?? 'Working')
        ]);

        redirectRecord($isEmbedded, $selectedUser, [
            'added' => 'printer'
        ]);

    } else {
        $error = 'Please enter the Printer Name and select an Office.';
    }
}

/* =========================================
   FETCH USERS
========================================= */

$usersStmt = $pdo->query("
    SELECT
        id,
        user_id_number,
        full_name,
        position,
        job_description,
        office
    FROM users
    ORDER BY full_name ASC
");

$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================
   FETCH ALL EQUIPMENT
========================================= */

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

/* =========================================
   PREPARE EMPLOYEE PROFILES
========================================= */

$userProfileData = [];

foreach ($users as $user) {

    $name = trim((string)($user['full_name'] ?? ''));

    if ($name === '') {
        continue;
    }

    $key = strtolower($name);

    $userProfileData[$key] = [
        'name' => $name,

        'employee' => [
            'id' => $user['user_id_number'] ?? '—',
            'full_name' => $user['full_name'] ?? '—',
            'position' => $user['position'] ?? '—',
            'office' => $user['office'] ?? '—',
            'job_description' => $user['job_description'] ?? '—'
        ],

        'equipment' => []
    ];
}

/* =========================================
   ADD COMPUTERS TO EMPLOYEE PROFILES
========================================= */

foreach ($allComputers as $pc) {

    $name = trim((string)($pc['assigned_to'] ?? ''));

    if ($name === '') {
        continue;
    }

    $key = strtolower($name);

    if (!isset($userProfileData[$key])) {

        $userProfileData[$key] = [
            'name' => $name,
            'employee' => [],
            'equipment' => []
        ];

    }

    $userProfileData[$key]['equipment'][] = [

        'type' => 'Computer',

        'name' => $pc['computer_name'] ?? '—',

        'office' => $pc['office'] ?? '—',

        'brand_model' => trim(
            ($pc['brand'] ?? '') . ' ' .
            ($pc['model'] ?? '')
        ) ?: '—',

        'ram' => $pc['ram'] ?? '—',

        'cpu' => $pc['cpu'] ?? '—',

        'ssd' => $pc['ssd'] ?? '—',

        'hd' => $pc['hd'] ?? '—',

        'serial' => $pc['serial_number'] ?? '—',

        'mac_address' => $pc['mac_address'] ?? '—',

        'status' => $pc['status'] ?? '—',

        'remarks' => $pc['remarks'] ?? '—'
    ];
}

/* =========================================
   ADD PRINTERS TO EMPLOYEE PROFILES
========================================= */

foreach ($allPrinters as $printer) {

    $name = trim((string)($printer['assigned_to'] ?? ''));

    if ($name === '') {
        continue;
    }

    $key = strtolower($name);

    if (!isset($userProfileData[$key])) {

        $userProfileData[$key] = [
            'name' => $name,
            'employee' => [],
            'equipment' => []
        ];

    }

    $userProfileData[$key]['equipment'][] = [

        'type' => 'Printer',

        'name' => $printer['printer_name'] ?? '—',

        'office' => $printer['office'] ?? '—',

        'brand_model' => $printer['brand_model'] ?? '—',

        'ram' => '—',

        'cpu' => '—',

        'ssd' => '—',

        'hd' => '—',

        'serial' => $printer['serial_number'] ?? '—',

        'mac_address' => '—',

        'status' => $printer['status'] ?? '—',

        'remarks' => $printer['remarks'] ?? '—'
    ];
}

/* =========================================
   FILTER EQUIPMENT BY ASSIGNED USER
========================================= */

$computers = $allComputers;
$printers = $allPrinters;

if ($selectedUser !== '') {

    $computers = array_values(array_filter(
        $computers,
        fn($pc) => strcasecmp(
            trim($pc['assigned_to'] ?? ''),
            $selectedUser
        ) === 0
    ));

    $printers = array_values(array_filter(
        $printers,
        fn($printer) => strcasecmp(
            trim($printer['assigned_to'] ?? ''),
            $selectedUser
        ) === 0
    ));
}

/* =========================================
   TOTAL RECORDS
========================================= */

$totalRecords = count($computers) + count($printers);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Inventory System</title>

<link rel="stylesheet" href="record.css">

<style>

/* =========================================
   GENERAL
========================================= */

html,
body {
    margin: 0;
    min-height: 100%;
}

body.embedded {
    background: #f3f6fc;
    overflow-x: hidden;
}

body.embedded .sidebar {
    display: none !important;
}

body.embedded .main {
    margin-left: 0 !important;
    width: 100% !important;
    max-width: none !important;
    padding: 14px !important;
    box-sizing: border-box;
}

body.embedded .muted {
    color: #64748b;
}

body.embedded .panel {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 13px;
    overflow: hidden;
    box-shadow: none;
}

body.embedded .panel-head {
    padding: 18px 22px;
    border-bottom: 1px solid #e2e8f0;
}

body.embedded .table-wrap {
    overflow-x: auto;
    width: 100%;
}

body.embedded table {
    min-width: 1150px;
    width: 100%;
}

/* =========================================
   HEADER
========================================= */

.page-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.page-heading h2 {
    margin: 0 0 5px;
    font-size: 22px;
}

.page-heading p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}

.selected-user-info {
    margin: 0 0 18px;
    color: #64748b;
    font-size: 14px;
}

.selected-user-info strong {
    color: #2563eb;
}

/* =========================================
   ASSIGNED USER LINK
========================================= */

.assigned-user-link {
    color: #2563eb;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
}

.assigned-user-link:hover {
    text-decoration: underline;
}

/* =========================================
   BUTTONS
========================================= */

.button {
    border: none;
    background: #2563eb;
    color: white;
    padding: 11px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.button:hover {
    background: #1d4ed8;
}

.button.gray {
    background: #e2e8f0;
    color: #334155;
}

.button.gray:hover {
    background: #cbd5e1;
}

/* =========================================
   ADD EQUIPMENT DROPDOWN
========================================= */

.equipment-dropdown {
    position: relative;
}

.equipment-menu {
    display: none;
    position: absolute;
    right: 0;
    top: calc(100% + 6px);
    min-width: 180px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    box-shadow: 0 10px 25px rgba(0,0,0,.12);
    z-index: 100;
    overflow: hidden;
}

.equipment-menu.show {
    display: block;
}

.equipment-option {
    display: block;
    width: 100%;
    padding: 12px 15px;
    border: none;
    background: white;
    text-align: left;
    cursor: pointer;
    font: inherit;
}

.equipment-option:hover {
    background: #f1f5f9;
}

/* =========================================
   PANEL
========================================= */

.panel {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.panel-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 22px;
    border-bottom: 1px solid #e2e8f0;
}

.panel-head h2 {
    margin: 0;
    font-size: 16px;
}

.panel-head .muted {
    color: #64748b;
    font-size: 13px;
}

/* =========================================
   SEARCH
========================================= */

.search {
    display: flex;
    align-items: center;
    gap: 9px;
    flex-wrap: wrap;
    padding: 16px 20px;
}

.search input {
    flex: 1;
    min-width: 180px;
    padding: 11px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font: inherit;
}

/* =========================================
   TABLE
========================================= */

.table-wrap {
    overflow-x: auto;
    width: 100%;
}

table {
    border-collapse: collapse;
}

#records th {
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    white-space: nowrap;
    padding: 14px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

#records td {
    padding: 14px;
    font-size: 12px;
    border-bottom: 1px solid #edf0f5;
    white-space: nowrap;
}

#records tbody tr:hover {
    background: #f8fafc;
}

.empty {
    text-align: center;
    padding: 30px;
    color: #64748b;
}

/* =========================================
   NOTICES
========================================= */

.notice {
    background: #dcfce7;
    color: #166534;
    padding: 12px 16px;
    border-radius: 7px;
    margin-bottom: 15px;
}

/* =========================================
   STATUS
========================================= */

.status {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 12px;
}

.status.working {
    background: #dcfce7;
    color: #166534;
}

.status.maintenance {
    background: #fef3c7;
    color: #92400e;
}

.status.retired {
    background: #fee2e2;
    color: #991b1b;
}

.status.checking {
    background: #e0e7ff;
    color: #3730a3;
}

/* =========================================
   MODALS
========================================= */

.modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(15, 23, 42, .60);
    align-items: center;
    justify-content: center;
    padding: 15px;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    width: 100%;
    max-width: 850px;
    max-height: 92vh;
    overflow-y: auto;
    border-radius: 13px;
    box-shadow: 0 20px 50px rgba(0,0,0,.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 22px;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
}

.modal-header p {
    margin: 6px 0 0;
    font-size: 13px;
}

.close-modal {
    border: none;
    background: #f1f5f9;
    width: 38px;
    height: 38px;
    font-size: 25px;
    border-radius: 7px;
    cursor: pointer;
}

.modal-body {
    padding: 22px;
}

/* =========================================
   FORM FIELDS
========================================= */

.field-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.field {
    min-width: 0;
}

.field label {
    display: block;
    margin-bottom: 7px;
    font-size: 13px;
    font-weight: 600;
}

.field input,
.field select,
.field textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 11px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font: inherit;
    font-size: 14px;
}

.field textarea {
    resize: vertical;
}

.required {
    color: red;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 22px;
}

.form-actions button {
    cursor: pointer;
}

/* =========================================
   EMPLOYEE PROFILE MODAL
========================================= */

.user-profile-modal-content {
    max-width: 1200px;
    width: 100%;
}

.user-profile-modal-content .modal-body {
    background: #f3f6fc;
}

/* =========================================
   EMPLOYEE OVERVIEW
========================================= */

.employee-overview {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 22px;
    margin-bottom: 20px;
}

.employee-overview-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}

.employee-avatar {
    width: 55px;
    height: 55px;
    min-width: 55px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #dbeafe;
    color: #1d4ed8;
    font-size: 19px;
    font-weight: 700;
}

.employee-heading h3 {
    margin: 0 0 5px;
    font-size: 19px;
    color: #0f172a;
}

.employee-heading p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
}

.employee-overview-title {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 16px;
}

.employee-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.employee-info-item {
    min-width: 0;
}

.employee-info-label {
    display: block;
    color: #64748b;
    font-size: 12px;
    margin-bottom: 5px;
}

.employee-info-value {
    display: block;
    color: #0f172a;
    font-size: 14px;
    font-weight: 600;
    overflow-wrap: anywhere;
}

/* =========================================
   EQUIPMENT SUMMARY
========================================= */

.employee-equipment-summary {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}

.employee-summary-item {
    background: #eff6ff;
    color: #1d4ed8;
    padding: 8px 13px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
}

/* =========================================
   EMPLOYEE EQUIPMENT PANEL
========================================= */

.employee-equipment-panel {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.employee-equipment-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 20px;
    border-bottom: 1px solid #e2e8f0;
}

.employee-equipment-head h3 {
    margin: 0;
    font-size: 16px;
}

.employee-equipment-count {
    color: #64748b;
    font-size: 13px;
}

.employee-equipment-search {
    display: flex;
    gap: 9px;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    flex-wrap: wrap;
}

.employee-equipment-search input {
    flex: 1;
    min-width: 150px;
    padding: 11px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font: inherit;
}

.employee-equipment-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.employee-equipment-table {
    width: 100%;
    min-width: 1450px;
    border-collapse: collapse;
}

.employee-equipment-table th {
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .3px;
    white-space: nowrap;
    padding: 14px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.employee-equipment-table td {
    padding: 14px;
    font-size: 12px;
    color: #0f172a;
    white-space: nowrap;
    border-bottom: 1px solid #edf0f5;
}

.employee-equipment-table tbody tr:hover {
    background: #f8fafc;
}

.employee-equipment-table tbody tr:last-child td {
    border-bottom: none;
}

.employee-equipment-table .equipment-name {
    font-weight: 600;
}

.employee-equipment-table .assigned-user {
    color: #2563eb;
    font-weight: 600;
}

.employee-equipment-empty {
    display: none;
    padding: 30px;
    text-align: center;
    color: #64748b;
}

/* =========================================
   RESPONSIVE
========================================= */

@media(max-width: 650px) {

    .field-grid,
    .employee-info-grid {
        grid-template-columns: 1fr;
    }

    body.embedded .main {
        padding: 10px !important;
    }

    .page-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .employee-overview {
        padding: 16px;
    }

    .employee-equipment-head {
        padding: 15px;
    }

    .employee-equipment-search {
        padding: 12px;
    }
}

</style>

</head>

<body class="<?= $isEmbedded ? 'embedded' : '' ?>">

<?php if (!$isEmbedded): ?>

<!-- =========================================
     SIDEBAR
========================================= -->

<aside class="sidebar">

    <div class="brand">Inventory System</div>

    <div class="section-label">Main Menu</div>

    <nav>

        <a class="nav-link" href="index.php">
            <span>▦</span> Dashboard
        </a>

        <a class="nav-link active" href="record.php">
            <span>▤</span> Equipment Record
        </a>

        <a class="nav-link" href="user.php">
            <span>♙</span> Employee Record
        </a>

    </nav>

</aside>

<?php endif; ?>

<!-- =========================================
     MAIN CONTENT
========================================= -->

<main class="main">

    <!-- PAGE HEADER -->

    <?php if (!$isEmbedded): ?>

    <div class="page-heading">

        <div>
          
            <h2>Equipment Record</h2>
             
            <p>
                View and manage computer and printer inventory records.
            </p>
        </div>

        <div class="equipment-dropdown">

            <button
                type="button"
                class="button"
                id="equipmentButton"
                aria-expanded="false"
            >
                Add Equipment ⌄
            </button>

            <div class="equipment-menu" id="equipmentMenu">

                <button
                    type="button"
                    class="equipment-option"
                    id="openComputerModal"
                >
                    Add Computer
                </button>

                <button
                    type="button"
                    class="equipment-option"
                    id="openPrinterModal"
                >
                    Add Printer
                </button>

            </div>

        </div>

    </div>
    


    <?php endif; ?>

    <?php if ($selectedUser !== ''): ?>

        <p class="selected-user-info">
            Showing equipment assigned to:
            <strong><?= e($selectedUser) ?></strong>
        </p>

    <?php else: ?>

        <?php if ($isEmbedded): ?>

            <p class="muted">
                View and search the computer and printer records in your inventory.
            </p>

        <?php endif; ?>

    <?php endif; ?>

    <!-- SUCCESS MESSAGES -->

    <?php if (isset($_GET['added'])): ?>

        <div class="notice">
            <?= $_GET['added'] === 'printer' ? 'Printer' : 'Computer' ?>
            record added successfully to the database.
        </div>

    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>

        <div class="notice">
            <?= $_GET['updated'] === 'printer' ? 'Printer' : 'Computer' ?>
            record updated successfully.
        </div>

    <?php endif; ?>

    <?php if ($error !== ''): ?>

        <div class="notice">
            <?= e($error) ?>
        </div>

    <?php endif; ?>

    <?php if ($computerError !== ''): ?>

        <div class="notice">
            <?= e($computerError) ?>
        </div>

    <?php endif; ?>

    <!-- =========================================
         EQUIPMENT RECORD TABLE
    ========================================= -->

    <section class="panel" id="inventory-list">

        <div class="panel-head">

            <h2>Equipment Record</h2>

            <span class="muted" id="recordCount">
                <?= $totalRecords ?>
                <?= $totalRecords === 1 ? 'record' : 'records' ?>
            </span>

        </div>

        <!-- SEARCH -->

        <div class="search">

            <input
                type="search"
                id="tableSearch"
                placeholder="Search equipment, type, user, or office..."
            >

            <button
                class="button"
                type="button"
                id="searchButton"
            >
                Search
            </button>

            <button
                class="button gray"
                type="button"
                id="clearSearch"
            >
                Clear
            </button>

        </div>

        <!-- TABLE -->

        <div class="table-wrap">

            <table id="records">

                <thead>

                    <tr>
                        <th>Equipment Type</th>
                        <th>Equipment Name</th>
                        <th>Office</th>
                        <th>RAM</th>
                        <th>CPU</th>
                        <th>SSD</th>
                        <th>HD</th>
                        <th>Brand / Model</th>
                        <th>Serial No.</th>
                        <th>MAC Address</th>
                        <th>Assigned User</th>
                        <th>Remarks</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>

                </thead>

                <tbody id="recordsBody">

                <?php foreach ($computers as $pc): ?>

                    <tr>

                        <td>Computer</td>

                        <td><?= e($pc['computer_name'] ?? '') ?></td>

                        <td><?= e($pc['office'] ?? '') ?></td>

                        <td><?= e($pc['ram'] ?? '—') ?></td>

                        <td><?= e($pc['cpu'] ?? '—') ?></td>

                        <td><?= e($pc['ssd'] ?? '—') ?></td>

                        <td><?= e($pc['hd'] ?? '—') ?></td>

                        <td>
                            <?= e(
                                trim(
                                    ($pc['brand'] ?? '') . ' ' .
                                    ($pc['model'] ?? '')
                                ) ?: '—'
                            ) ?>
                        </td>

                        <td><?= e($pc['serial_number'] ?? '—') ?></td>

                        <td><?= e($pc['mac_address'] ?? '—') ?></td>

                        <td>

                            <?php $assigned = trim((string)($pc['assigned_to'] ?? '')); ?>

                            <?php if ($assigned !== ''): ?>

                                <a
                                    href="#"
                                    class="assigned-user-link"
                                    data-user="<?= e($assigned) ?>"
                                >
                                    <?= e($assigned) ?>
                                </a>

                            <?php else: ?>

                                —

                            <?php endif; ?>

                        </td>

                        <td><?= e($pc['remarks'] ?? '—') ?></td>

                        <td>

                            <span class="status <?= e(statusClass($pc['status'] ?? '')) ?>">
                                <?= e($pc['status'] ?? '') ?>
                            </span>

                        </td>

                        <td>

                            <button
                                type="button"
                                class="button edit-button"
                                data-type="computer"
                                data-id="<?= (int)$pc['id'] ?>"
                                data-record="<?= e(json_encode(
                                    $pc,
                                    JSON_HEX_APOS |
                                    JSON_HEX_QUOT |
                                    JSON_HEX_TAG |
                                    JSON_HEX_AMP
                                )) ?>"
                            >
                                Edit
                            </button>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php foreach ($printers as $printer): ?>

                    <tr>

                        <td>Printer</td>

                        <td><?= e($printer['printer_name'] ?? '') ?></td>

                        <td><?= e($printer['office'] ?? '') ?></td>

                        <td>—</td>
                        <td>—</td>
                        <td>—</td>
                        <td>—</td>

                        <td><?= e($printer['brand_model'] ?? '—') ?></td>

                        <td><?= e($printer['serial_number'] ?? '—') ?></td>

                        <td>—</td>

                        <td>

                            <?php $assigned = trim((string)($printer['assigned_to'] ?? '')); ?>

                            <?php if ($assigned !== ''): ?>

                                <a
                                    href="#"
                                    class="assigned-user-link"
                                    data-user="<?= e($assigned) ?>"
                                >
                                    <?= e($assigned) ?>
                                </a>

                            <?php else: ?>

                                —

                            <?php endif; ?>

                        </td>

                        <td><?= e($printer['remarks'] ?? '—') ?></td>

                        <td>

                            <span class="status <?= e(statusClass($printer['status'] ?? '')) ?>">
                                <?= e($printer['status'] ?? '') ?>
                            </span>

                        </td>

                        <td>

                            <button
                                type="button"
                                class="button edit-button"
                                data-type="printer"
                                data-id="<?= (int)$printer['id'] ?>"
                                data-record="<?= e(json_encode(
                                    $printer,
                                    JSON_HEX_APOS |
                                    JSON_HEX_QUOT |
                                    JSON_HEX_TAG |
                                    JSON_HEX_AMP
                                )) ?>"
                            >
                                Edit
                            </button>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

            <div
                class="empty"
                id="emptyMessage"
                <?= $totalRecords > 0 ? 'style="display:none"' : '' ?>
            >

                <?php if ($selectedUser !== ''): ?>

                    No equipment records assigned to
                    <?= e($selectedUser) ?>.

                <?php else: ?>

                    No equipment records yet.

                <?php endif; ?>

            </div>

        </div>

    </section>

</main>

<!-- =========================================
     EMPLOYEE PROFILE DATA
========================================= -->

<script type="application/json" id="userProfileData"><?= json_encode(
    $userProfileData,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
) ?></script>

<!-- =========================================
     EMPLOYEE PROFILE MODAL
========================================= -->

<div class="modal" id="userProfileModal" aria-hidden="true">

    <div
        class="modal-content user-profile-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="userProfileTitle"
    >

        <div class="modal-header">

            <div>

                <h2 id="userProfileTitle">
                    Employee Profile
                </h2>

                <p class="muted">
                    Employee overview and assigned equipment records.
                </p>

            </div>

            <button
                type="button"
                class="close-modal"
                id="closeUserProfileModal"
                aria-label="Close"
            >
                &times;
            </button>

        </div>

        <div class="modal-body">

            <!-- EMPLOYEE OVERVIEW -->

            <section class="employee-overview">

                <div class="employee-overview-header">

                    <div
                        class="employee-avatar"
                        id="employeeAvatar"
                    >
                        --
                    </div>

                    <div class="employee-heading">

                        <h3 id="employeeName">
                            Employee Name
                        </h3>

                        <p id="employeePosition">
                            Employee Position
                        </p>

                    </div>

                </div>

                <div class="employee-overview-title">
                    Employee Overview
                </div>

                <div class="employee-info-grid">

                    <div class="employee-info-item">

                        <span class="employee-info-label">
                            User ID Number
                        </span>

                        <span
                            class="employee-info-value"
                            id="employeeId"
                        >
                            —
                        </span>

                    </div>

                    <div class="employee-info-item">

                        <span class="employee-info-label">
                            Full Name
                        </span>

                        <span
                            class="employee-info-value"
                            id="employeeFullName"
                        >
                            —
                        </span>

                    </div>

                    <div class="employee-info-item">

                        <span class="employee-info-label">
                            Position
                        </span>

                        <span
                            class="employee-info-value"
                            id="employeePositionValue"
                        >
                            —
                        </span>

                    </div>

                    <div class="employee-info-item">

                        <span class="employee-info-label">
                            Office / Division
                        </span>

                        <span
                            class="employee-info-value"
                            id="employeeOffice"
                        >
                            —
                        </span>

                    </div>

                    <div
                        class="employee-info-item"
                        style="grid-column:1/-1"
                    >

                        <span class="employee-info-label">
                            Job Description
                        </span>

                        <span
                            class="employee-info-value"
                            id="employeeJobDescription"
                        >
                            —
                        </span>

                    </div>

                </div>

            </section>

            <!-- EQUIPMENT SUMMARY -->

            <div
                class="employee-equipment-summary"
                id="employeeEquipmentSummary"
            ></div>

            <!-- ASSIGNED EQUIPMENT RECORD -->

            <section class="employee-equipment-panel">

                <div class="employee-equipment-head">

                    <h3>Equipment Record</h3>

                    <span
                        class="employee-equipment-count"
                        id="employeeEquipmentCount"
                    >
                        0 records
                    </span>

                </div>

                <!-- SEARCH -->

                <div class="employee-equipment-search">

                    <input
                        type="search"
                        id="userEquipmentSearch"
                        placeholder="Search equipment, type, user, or office..."
                    >

                    <button
                        type="button"
                        class="button"
                        id="searchUserEquipment"
                    >
                        Search
                    </button>

                    <button
                        type="button"
                        class="button gray"
                        id="clearUserEquipmentSearch"
                    >
                        Clear
                    </button>

                </div>

                <!-- TABLE -->

                <div class="employee-equipment-table-wrap">

                    <table class="employee-equipment-table">

                        <thead>

                            <tr>
                                <th>Equipment Type</th>
                                <th>Equipment Name</th>
                                <th>Office</th>
                                <th>RAM</th>
                                <th>CPU</th>
                                <th>SSD</th>
                                <th>HD</th>
                                <th>Brand / Model</th>
                                <th>Serial No.</th>
                                <th>MAC Address</th>
                                <th>Assigned User</th>
                                <th>Remarks</th>
                                <th>Status</th>
                            </tr>

                        </thead>

                        <tbody id="userEquipmentTableBody"></tbody>

                    </table>

                    <div
                        class="employee-equipment-empty"
                        id="userEquipmentEmpty"
                    >
                        No assigned equipment records found.
                    </div>

                </div>

            </section>

        </div>

    </div>

</div>

<!-- =========================================
     ADD COMPUTER MODAL
========================================= -->

<div class="modal" id="computerModal" aria-hidden="true">

    <div class="modal-content" role="dialog" aria-modal="true">

        <div class="modal-header">

            <h2>Add Computer</h2>

            <button
                type="button"
                class="close-modal"
                id="closeComputerModal"
            >
                &times;
            </button>

        </div>

        <div class="modal-body">

            <form
                method="POST"
                action="record.php<?= $isEmbedded ? '?embed=1&assigned_to=' . urlencode($selectedUser) : '' ?>"
            >

                <input type="hidden" name="form_type" value="computer">

                <div class="field-grid">

                    <div class="field">

                        <label for="computer_name">
                            Computer Name <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="computer_name"
                            name="computer_name"
                            required
                        >

                    </div>

                    <div class="field">

                        <label for="computer_office">
                            Office <span class="required">*</span>
                        </label>

                        <select id="computer_office" name="office" required>

                            <option value="">Select Office</option>
                            <option>Administrative Office</option>
                            <option>Accounting Office</option>
                            <option>Assessors Office</option>
                            <option>HRMO - HR Office</option>
                            <option>IT Office</option>
                            <option>Other</option>

                        </select>

                    </div>

                    <div class="field">
                        <label for="computer_ram">RAM</label>
                        <input type="text" id="computer_ram" name="ram">
                    </div>

                    <div class="field">
                        <label for="computer_cpu">CPU</label>
                        <input type="text" id="computer_cpu" name="cpu">
                    </div>

                    <div class="field">
                        <label for="computer_ssd">SSD</label>
                        <input type="text" id="computer_ssd" name="ssd">
                    </div>

                    <div class="field">
                        <label for="computer_hd">HD</label>
                        <input type="text" id="computer_hd" name="hd">
                    </div>

                    <div class="field">
                        <label for="computer_brand">Brand</label>
                        <input type="text" id="computer_brand" name="brand">
                    </div>

                    <div class="field">
                        <label for="computer_model">Model</label>
                        <input type="text" id="computer_model" name="model">
                    </div>

                    <div class="field">
                        <label for="computer_serial">Serial Number</label>
                        <input type="text" id="computer_serial" name="serial_number">
                    </div>

                    <div class="field">
                        <label for="computer_mac">MAC Address</label>
                        <input type="text" id="computer_mac" name="mac_address">
                    </div>

                    <div class="field">

                        <label for="computer_assigned">
                            Assigned User
                        </label>

                        <select id="computer_assigned" name="assigned_to">

                            <option value="">Select Assigned User</option>

                            <?php foreach ($users as $user): ?>

                                <option
                                    value="<?= e($user['full_name'] ?? '') ?>"
                                    <?= $selectedUser !== '' && strcasecmp(
                                        $selectedUser,
                                        trim($user['full_name'] ?? '')
                                    ) === 0 ? 'selected' : '' ?>
                                >
                                    <?= e($user['full_name'] ?? '') ?>
                                    <?= !empty($user['office']) ? ' — ' . e($user['office']) : '' ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="field">

                        <label for="computer_status">Status</label>

                        <select id="computer_status" name="status">
                            <option>Working</option>
                            <option>For Maintenance</option>
                            <option>For Checking</option>
                            <option>Retired</option>
                        </select>

                    </div>

                    <div class="field" style="grid-column:1/-1">

                        <label for="computer_remarks">Remarks</label>

                        <textarea
                            id="computer_remarks"
                            name="remarks"
                            rows="3"
                        ></textarea>

                    </div>

                </div>

                <div class="form-actions">

                    <button
                        type="button"
                        class="button gray"
                        id="cancelComputerModal"
                    >
                        Cancel
                    </button>

                    <button type="submit" class="button">
                        Save Computer
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- =========================================
     ADD PRINTER MODAL
========================================= -->

<div class="modal" id="printerModal" aria-hidden="true">

    <div class="modal-content" role="dialog" aria-modal="true">

        <div class="modal-header">

            <h2>Add Printer</h2>

            <button
                type="button"
                class="close-modal"
                id="closePrinterModal"
            >
                &times;
            </button>

        </div>

        <div class="modal-body">

            <form
                method="POST"
                action="record.php<?= $isEmbedded ? '?embed=1&assigned_to=' . urlencode($selectedUser) : '' ?>"
            >

                <input type="hidden" name="form_type" value="printer">

                <div class="field-grid">

                    <div class="field">

                        <label for="printer_name">
                            Printer Name <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="printer_name"
                            name="printer_name"
                            required
                        >

                    </div>

                    <div class="field">

                        <label for="brand_model">Brand / Model</label>

                        <input
                            type="text"
                            id="brand_model"
                            name="brand_model"
                        >

                    </div>

                    <div class="field">

                        <label for="printer_serial">Serial Number</label>

                        <input
                            type="text"
                            id="printer_serial"
                            name="serial_number"
                        >

                    </div>

                    <div class="field">

                        <label for="printer_user">Assigned User</label>

                        <select id="printer_user" name="assigned_to">

                            <option value="">Select Assigned User</option>

                            <?php foreach ($users as $user): ?>

                                <option
                                    value="<?= e($user['full_name'] ?? '') ?>"
                                    <?= $selectedUser !== '' && strcasecmp(
                                        $selectedUser,
                                        trim($user['full_name'] ?? '')
                                    ) === 0 ? 'selected' : '' ?>
                                >
                                    <?= e($user['full_name'] ?? '') ?>
                                    <?= !empty($user['office']) ? ' — ' . e($user['office']) : '' ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="field">

                        <label for="printer_office">
                            Office <span class="required">*</span>
                        </label>

                        <select id="printer_office" name="office" required>

                            <option value="">Select Office</option>
                            <option>Administrative Office</option>
                            <option>Accounting Office</option>
                            <option>Assessors Office</option>
                            <option>HRMO - HR Office</option>
                            <option>IT Office</option>
                            <option>Other</option>

                        </select>

                    </div>

                    <div class="field">

                        <label for="printer_status">Status</label>

                        <select id="printer_status" name="status">
                            <option>Working</option>
                            <option>For Maintenance</option>
                            <option>For Checking</option>
                            <option>Retired</option>
                        </select>

                    </div>

                    <div class="field" style="grid-column:1/-1">

                        <label for="printer_remarks">Remarks</label>

                        <textarea
                            id="printer_remarks"
                            name="remarks"
                            rows="3"
                        ></textarea>

                    </div>

                </div>

                <div class="form-actions">

                    <button
                        type="button"
                        class="button gray"
                        id="cancelPrinterModal"
                    >
                        Cancel
                    </button>

                    <button type="submit" class="button">
                        Save Printer
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- =========================================
     EDIT EQUIPMENT MODAL
========================================= -->

<div class="modal" id="editModal" aria-hidden="true">

    <div class="modal-content" role="dialog" aria-modal="true">

        <div class="modal-header">

            <h2 id="editModalTitle">Update Equipment</h2>

            <button
                type="button"
                class="close-modal"
                id="closeEditModal"
            >
                &times;
            </button>

        </div>

        <div class="modal-body">

            <form
                method="POST"
                action="record.php<?= $isEmbedded ? '?embed=1&assigned_to=' . urlencode($selectedUser) : '' ?>"
                id="editForm"
            >

                <input type="hidden" name="form_type" id="editFormType">

                <input type="hidden" name="record_id" id="editRecordId">

                <!-- COMPUTER FIELDS -->

                <div class="field-grid" id="editComputerFields">

                    <div class="field">
                        <label for="edit_computer_name">Computer Name *</label>
                        <input id="edit_computer_name" name="computer_name" required>
                    </div>

                    <div class="field">

                        <label for="edit_computer_office">Office *</label>

                        <select id="edit_computer_office" name="office" required>
                            <option value="">Select Office</option>
                            <option>Administrative Office</option>
                            <option>Accounting Office</option>
                            <option>Assessors Office</option>
                            <option>HRMO - HR Office</option>
                            <option>IT Office</option>
                            <option>Other</option>
                        </select>

                    </div>

                    <div class="field">
                        <label for="edit_ram">RAM</label>
                        <input id="edit_ram" name="ram">
                    </div>

                    <div class="field">
                        <label for="edit_cpu">CPU</label>
                        <input id="edit_cpu" name="cpu">
                    </div>

                    <div class="field">
                        <label for="edit_ssd">SSD</label>
                        <input id="edit_ssd" name="ssd">
                    </div>

                    <div class="field">
                        <label for="edit_hd">HD</label>
                        <input id="edit_hd" name="hd">
                    </div>

                    <div class="field">
                        <label for="edit_brand">Brand</label>
                        <input id="edit_brand" name="brand">
                    </div>

                    <div class="field">
                        <label for="edit_model">Model</label>
                        <input id="edit_model" name="model">
                    </div>

                    <div class="field">
                        <label for="edit_computer_serial">Serial Number</label>
                        <input id="edit_computer_serial" name="serial_number">
                    </div>

                    <div class="field">
                        <label for="edit_mac">MAC Address</label>
                        <input id="edit_mac" name="mac_address">
                    </div>

                    <div class="field">

                        <label for="edit_computer_user">Assigned User</label>

                        <select id="edit_computer_user" name="assigned_to">

                            <option value="">Select Assigned User</option>

                            <?php foreach ($users as $user): ?>

                                <option value="<?= e($user['full_name'] ?? '') ?>">
                                    <?= e($user['full_name'] ?? '') ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="field">

                        <label for="edit_computer_status">Status</label>

                        <select id="edit_computer_status" name="status">
                            <option>Working</option>
                            <option>For Maintenance</option>
                            <option>For Checking</option>
                            <option>Retired</option>
                        </select>

                    </div>

                    <div class="field" style="grid-column:1/-1">

                        <label for="edit_computer_remarks">Remarks</label>

                        <textarea
                            id="edit_computer_remarks"
                            name="remarks"
                            rows="3"
                        ></textarea>

                    </div>

                </div>

                <!-- PRINTER FIELDS -->

                <div
                    class="field-grid"
                    id="editPrinterFields"
                    style="display:none"
                >

                    <div class="field">
                        <label for="edit_printer_name">Printer Name *</label>
                        <input id="edit_printer_name" name="printer_name" required>
                    </div>

                    <div class="field">
                        <label for="edit_printer_brand_model">Brand / Model</label>
                        <input id="edit_printer_brand_model" name="brand_model">
                    </div>

                    <div class="field">
                        <label for="edit_printer_serial">Serial Number</label>
                        <input id="edit_printer_serial" name="serial_number">
                    </div>

                    <div class="field">

                        <label for="edit_printer_user">Assigned User</label>

                        <select id="edit_printer_user" name="assigned_to">

                            <option value="">Select Assigned User</option>

                            <?php foreach ($users as $user): ?>

                                <option value="<?= e($user['full_name'] ?? '') ?>">
                                    <?= e($user['full_name'] ?? '') ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="field">

                        <label for="edit_printer_office">Office *</label>

                        <select id="edit_printer_office" name="office" required>
                            <option value="">Select Office</option>
                            <option>Administrative Office</option>
                            <option>Accounting Office</option>
                            <option>Assessors Office</option>
                            <option>HRMO - HR Office</option>
                            <option>IT Office</option>
                            <option>Other</option>
                        </select>

                    </div>

                    <div class="field">

                        <label for="edit_printer_status">Status</label>

                        <select id="edit_printer_status" name="status">
                            <option>Working</option>
                            <option>For Maintenance</option>
                            <option>For Checking</option>
                            <option>Retired</option>
                        </select>

                    </div>

                    <div class="field" style="grid-column:1/-1">

                        <label for="edit_printer_remarks">Remarks</label>

                        <textarea
                            id="edit_printer_remarks"
                            name="remarks"
                            rows="3"
                        ></textarea>

                    </div>

                </div>

                <div class="form-actions">

                    <button
                        type="button"
                        class="button gray"
                        id="cancelEditModal"
                    >
                        Cancel
                    </button>

                    <button type="submit" class="button">
                        Update Equipment
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

/* =========================================
   GENERAL MODAL FUNCTIONS
========================================= */

function showModal(modal) {

    if (!modal) return;

    modal.classList.add('show');

    modal.setAttribute('aria-hidden', 'false');

    document.body.style.overflow = 'hidden';

}

function hideModal(modal) {

    if (!modal) return;

    modal.classList.remove('show');

    modal.setAttribute('aria-hidden', 'true');

    if (!document.querySelector('.modal.show')) {
        document.body.style.overflow = '';
    }

}

/* =========================================
   ADD EQUIPMENT DROPDOWN
========================================= */

const equipmentButton =
    document.getElementById('equipmentButton');

const equipmentMenu =
    document.getElementById('equipmentMenu');

if (equipmentButton && equipmentMenu) {

    equipmentButton.addEventListener('click', function(event) {

        event.stopPropagation();

        const isOpen = equipmentMenu.classList.toggle('show');

        equipmentButton.setAttribute(
            'aria-expanded',
            isOpen ? 'true' : 'false'
        );

    });

    document.addEventListener('click', function(event) {

        if (!event.target.closest('.equipment-dropdown')) {

            equipmentMenu.classList.remove('show');

            equipmentButton.setAttribute('aria-expanded', 'false');

        }

    });

}

/* =========================================
   ADD COMPUTER MODAL
========================================= */

const computerModal =
    document.getElementById('computerModal');

function openComputerModal() {

    if (equipmentMenu) {
        equipmentMenu.classList.remove('show');
    }

    showModal(computerModal);

    document.getElementById('computer_name').focus();

}

function closeComputerModal() {

    hideModal(computerModal);

}

document.getElementById('openComputerModal')
    ?.addEventListener('click', openComputerModal);

document.getElementById('closeComputerModal')
    ?.addEventListener('click', closeComputerModal);

document.getElementById('cancelComputerModal')
    ?.addEventListener('click', closeComputerModal);

computerModal.addEventListener('click', function(event) {

    if (event.target === computerModal) {
        closeComputerModal();
    }

});

/* =========================================
   ADD PRINTER MODAL
========================================= */

const printerModal =
    document.getElementById('printerModal');

function openPrinterModal() {

    if (equipmentMenu) {
        equipmentMenu.classList.remove('show');
    }

    showModal(printerModal);

    document.getElementById('printer_name').focus();

}

function closePrinterModal() {

    hideModal(printerModal);

}

document.getElementById('openPrinterModal')
    ?.addEventListener('click', openPrinterModal);

document.getElementById('closePrinterModal')
    ?.addEventListener('click', closePrinterModal);

document.getElementById('cancelPrinterModal')
    ?.addEventListener('click', closePrinterModal);

printerModal.addEventListener('click', function(event) {

    if (event.target === printerModal) {
        closePrinterModal();
    }

});

/* =========================================
   EDIT EQUIPMENT
========================================= */

const editModal =
    document.getElementById('editModal');

function closeEditModal() {

    hideModal(editModal);

}

function setEditValue(id, value) {

    const field = document.getElementById(id);

    if (!field) return;

    const safeValue = value ?? '';

    if (
        field.tagName === 'SELECT' &&
        safeValue !== '' &&
        !Array.from(field.options).some(
            option => option.value === String(safeValue)
        )
    ) {

        const legacyOption = document.createElement('option');

        legacyOption.value = safeValue;

        legacyOption.textContent =
            safeValue + ' (not in user list)';

        field.appendChild(legacyOption);

    }

    field.value = safeValue;

}

document.querySelectorAll('.edit-button').forEach(button => {

    button.addEventListener('click', function() {

        const type = this.dataset.type;

        const record = JSON.parse(this.dataset.record);

        document.getElementById('editRecordId').value =
            this.dataset.id;

        document.getElementById('editFormType').value =
            type === 'computer'
                ? 'update_computer'
                : 'update_printer';

        document.getElementById('editModalTitle').textContent =
            type === 'computer'
                ? 'Edit Computer'
                : 'Edit Printer';

        const computerFields =
            document.getElementById('editComputerFields');

        const printerFields =
            document.getElementById('editPrinterFields');

        computerFields.style.display =
            type === 'computer' ? 'grid' : 'none';

        printerFields.style.display =
            type === 'printer' ? 'grid' : 'none';

        computerFields
            .querySelectorAll('input, select, textarea')
            .forEach(field => {

                field.disabled = type !== 'computer';

            });

        printerFields
            .querySelectorAll('input, select, textarea')
            .forEach(field => {

                field.disabled = type !== 'printer';

            });

        if (type === 'computer') {

            setEditValue('edit_computer_name', record.computer_name);

            setEditValue('edit_computer_office', record.office);

            setEditValue('edit_ram', record.ram);

            setEditValue('edit_cpu', record.cpu);

            setEditValue('edit_ssd', record.ssd);

            setEditValue('edit_hd', record.hd);

            setEditValue('edit_brand', record.brand);

            setEditValue('edit_model', record.model);

            setEditValue(
                'edit_computer_serial',
                record.serial_number
            );

            setEditValue('edit_mac', record.mac_address);

            setEditValue(
                'edit_computer_user',
                record.assigned_to
            );

            setEditValue(
                'edit_computer_status',
                record.status || 'Working'
            );

            setEditValue(
                'edit_computer_remarks',
                record.remarks
            );

        } else {

            setEditValue(
                'edit_printer_name',
                record.printer_name
            );

            setEditValue(
                'edit_printer_brand_model',
                record.brand_model
            );

            setEditValue(
                'edit_printer_serial',
                record.serial_number
            );

            setEditValue(
                'edit_printer_user',
                record.assigned_to
            );

            setEditValue(
                'edit_printer_office',
                record.office
            );

            setEditValue(
                'edit_printer_status',
                record.status || 'Working'
            );

            setEditValue(
                'edit_printer_remarks',
                record.remarks
            );

        }

        showModal(editModal);

    });

});

document.getElementById('closeEditModal')
    .addEventListener('click', closeEditModal);

document.getElementById('cancelEditModal')
    .addEventListener('click', closeEditModal);

editModal.addEventListener('click', function(event) {

    if (event.target === editModal) {
        closeEditModal();
    }

});

/* =========================================
   EMPLOYEE PROFILE DATA
========================================= */

const userProfileData = JSON.parse(
    document.getElementById('userProfileData').textContent || '{}'
);

const userProfileModal =
    document.getElementById('userProfileModal');

const userEquipmentSearch =
    document.getElementById('userEquipmentSearch');

const userEquipmentTableBody =
    document.getElementById('userEquipmentTableBody');

let activeUserProfile = null;

/* =========================================
   SAFE ELEMENT CREATION
========================================= */

function createProfileElement(tag, className, value) {

    const element = document.createElement(tag);

    if (className) {
        element.className = className;
    }

    if (value !== undefined && value !== null) {
        element.textContent = value;
    }

    return element;

}

/* =========================================
   EMPLOYEE OVERVIEW
========================================= */

function renderEmployeeOverview(profile) {

    const employee = profile.employee || {};

    const name =
        employee.full_name || profile.name || '—';

    const position =
        employee.position || '—';

    const initials = name
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .map(part => Array.from(part)[0] || '')
        .join('')
        .slice(0, 2)
        .toUpperCase() || '?';

    document.getElementById('employeeAvatar').textContent =
        initials;

    document.getElementById('employeeName').textContent =
        name;

    document.getElementById('employeePosition').textContent =
        position;

    document.getElementById('employeeId').textContent =
        employee.id || '—';

    document.getElementById('employeeFullName').textContent =
        name;

    document.getElementById('employeePositionValue').textContent =
        position;

    document.getElementById('employeeOffice').textContent =
        employee.office || '—';

    document.getElementById('employeeJobDescription').textContent =
        employee.job_description || '—';

}

/* =========================================
   EQUIPMENT SUMMARY
========================================= */

function renderEmployeeEquipmentSummary(items) {

    const summary =
        document.getElementById('employeeEquipmentSummary');

    summary.replaceChildren();

    const computerCount = items.filter(
        item => item.type === 'Computer'
    ).length;

    const printerCount = items.filter(
        item => item.type === 'Printer'
    ).length;

    const summaryItems = [

        items.length + ' Total Equipment',

        computerCount + ' Computer(s)',

        printerCount + ' Printer(s)'

    ];

    summaryItems.forEach(label => {

        summary.appendChild(
            createProfileElement(
                'span',
                'employee-summary-item',
                label
            )
        );

    });

}

/* =========================================
   ASSIGNED EQUIPMENT TABLE
========================================= */

function renderEmployeeEquipmentTable() {

    userEquipmentTableBody.replaceChildren();

    if (!activeUserProfile) return;

    const items = activeUserProfile.equipment || [];

    const query = userEquipmentSearch.value
        .trim()
        .toLocaleLowerCase();

    const filteredItems = items.filter(item => {

        const searchable = [

            item.type,

            item.name,

            item.office,

            item.brand_model,

            item.ram,

            item.cpu,

            item.ssd,

            item.hd,

            item.serial,

            item.mac_address,

            activeUserProfile.name,

            item.remarks,

            item.status

        ].join(' ').toLocaleLowerCase();

        return searchable.includes(query);

    });

    document.getElementById('employeeEquipmentCount')
        .textContent =
            filteredItems.length +
            (filteredItems.length === 1 ? ' record' : ' records');

    document.getElementById('userEquipmentEmpty').style.display =
        filteredItems.length === 0 ? 'block' : 'none';

    filteredItems.forEach(item => {

        const row = document.createElement('tr');

        const values = [

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

            activeUserProfile.name,

            item.remarks

        ];

        values.forEach((value, index) => {

            const cell = document.createElement('td');

            cell.textContent = value || '—';

            if (index === 1) {
                cell.classList.add('equipment-name');
            }

            if (index === 10) {
                cell.classList.add('assigned-user');
            }

            row.appendChild(cell);

        });

        /* STATUS COLUMN */

        const statusCell = document.createElement('td');

        const status = item.status || '—';

        const currentClass =
            status === 'Working' ? 'working' :
            status === 'For Maintenance' ? 'maintenance' :
            status === 'Retired' ? 'retired' :
            'checking';

        const statusBadge = createProfileElement(
            'span',
            'status ' + currentClass,
            status
        );

        statusCell.appendChild(statusBadge);

        row.appendChild(statusCell);

        userEquipmentTableBody.appendChild(row);

    });

}

/* =========================================
   OPEN EMPLOYEE PROFILE
========================================= */

function openUserProfile(event, link) {

    event.preventDefault();

    const userName = link.dataset.user || '';

    const key = userName.trim().toLocaleLowerCase();

    activeUserProfile = userProfileData[key] || {

        name: userName,

        employee: {},

        equipment: []

    };

    userEquipmentSearch.value = '';

    renderEmployeeOverview(activeUserProfile);

    renderEmployeeEquipmentSummary(
        activeUserProfile.equipment || []
    );

    renderEmployeeEquipmentTable();

    showModal(userProfileModal);

}

/* =========================================
   CLICKABLE ASSIGNED USER NAMES
========================================= */

document.querySelectorAll('.assigned-user-link')
    .forEach(link => {

        link.addEventListener('click', function(event) {

            openUserProfile(event, this);

        });

    });

/* =========================================
   CLOSE EMPLOYEE PROFILE
========================================= */

function closeUserProfile() {

    hideModal(userProfileModal);

}

document.getElementById('closeUserProfileModal')
    .addEventListener('click', closeUserProfile);

/* =========================================
   SEARCH EMPLOYEE EQUIPMENT
========================================= */

document.getElementById('searchUserEquipment')
    .addEventListener('click', function() {

        renderEmployeeEquipmentTable();

    });

userEquipmentSearch.addEventListener(
    'input',
    renderEmployeeEquipmentTable
);

/* =========================================
   CLEAR EMPLOYEE EQUIPMENT SEARCH
========================================= */

document.getElementById('clearUserEquipmentSearch')
    .addEventListener('click', function() {

        userEquipmentSearch.value = '';

        renderEmployeeEquipmentTable();

        userEquipmentSearch.focus();

    });

/* =========================================
   CLOSE WHEN CLICKING OUTSIDE
========================================= */

userProfileModal.addEventListener('click', function(event) {

    if (event.target === userProfileModal) {

        closeUserProfile();

    }

});

/* =========================================
   EQUIPMENT TABLE SEARCH
========================================= */

const searchInput =
    document.getElementById('tableSearch');

function applySearch() {

    const query = searchInput.value.trim().toLowerCase();

    const rows = [
        ...document.querySelectorAll('#recordsBody tr')
    ];

    let shown = 0;

    rows.forEach(row => {

        const match = row.textContent
            .toLowerCase()
            .includes(query);

        row.style.display = match ? '' : 'none';

        if (match) {
            shown++;
        }

    });

    document.getElementById('emptyMessage').style.display =
        shown === 0 ? 'block' : 'none';

    document.getElementById('recordCount').textContent =
        shown + (shown === 1 ? ' record' : ' records');

}

document.getElementById('searchButton')
    .addEventListener('click', applySearch);

searchInput.addEventListener('input', applySearch);

document.getElementById('clearSearch')
    .addEventListener('click', function() {

        searchInput.value = '';

        applySearch();

    });

/* =========================================
   ESCAPE KEY
========================================= */

document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {

        closeComputerModal();

        closePrinterModal();

        closeEditModal();

        closeUserProfile();

        if (equipmentMenu) {
            equipmentMenu.classList.remove('show');
        }

        if (equipmentButton) {
            equipmentButton.setAttribute('aria-expanded', 'false');
        }

    }

});

/* =========================================
   REOPEN MODALS IF VALIDATION FAILS
========================================= */

<?php if ($computerError !== ''): ?>

openComputerModal();

<?php endif; ?>

<?php if ($error !== ''): ?>

<?php if (
    ($_POST['form_type'] ?? '') === 'update_computer' ||
    ($_POST['form_type'] ?? '') === 'update_printer'
): ?>

// Keep the error visible so the user can correct the record.

<?php else: ?>

openPrinterModal();

<?php endif; ?>

<?php endif; ?>

</script>

</body>
</html>
