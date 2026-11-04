<?php
session_start();
require_once 'db.php';

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function statusClass($status): string {
    if ($status === 'Working') return 'working';
    if ($status === 'For Maintenance') return 'maintenance';
    if ($status === 'Retired') return 'retired';
    return 'checking';
}

$error = '';
$computerError = '';

/* UPDATE COMPUTER */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['form_type'] ?? '') === 'update_computer'
) {
    $id = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);

    $computer_name = trim($_POST['computer_name'] ?? '');
    $office = trim($_POST['office'] ?? '');

    if ($id && $computer_name !== '' && $office !== '') {
        $stmt = $pdo->prepare("UPDATE computers SET computer_name = ?, office = ?, ram = ?, cpu = ?, ssd = ?, hd = ?, brand = ?, model = ?, serial_number = ?, mac_address = ?, assigned_to = ?, remarks = ?, status = ? WHERE id = ?");
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

        header('Location: record.php?updated=computer#inventory-list');
        exit;
    }

    $error = 'Unable to update computer. Please check the Computer Name and Office.';
}

/* UPDATE PRINTER */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['form_type'] ?? '') === 'update_printer'
) {
    $id = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);

    $printer_name = trim($_POST['printer_name'] ?? '');
    $office = trim($_POST['office'] ?? '');

    if ($id && $printer_name !== '' && $office !== '') {
        $stmt = $pdo->prepare("UPDATE printers SET printer_name = ?, brand_model = ?, serial_number = ?, assigned_to = ?, office = ?, remarks = ?, status = ? WHERE id = ?");
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

        header('Location: record.php?updated=printer#inventory-list');
        exit;
    }

    $error = 'Unable to update printer. Please check the Printer Name and Office.';
}

/* ADD COMPUTER */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['form_type'] ?? '') === 'computer'
) {
    $computer_name = trim($_POST['computer_name'] ?? '');
    $office = trim($_POST['office'] ?? '');

    if ($computer_name !== '' && $office !== '') {
        $stmt = $pdo->prepare("INSERT INTO computers (computer_name, office, ram, cpu, ssd, hd, brand, model, serial_number, mac_address, assigned_to, remarks, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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

        header('Location: record.php?added=computer#inventory-list');
        exit;
    }

    $computerError = 'Please enter the Computer Name and select an Office.';
}

/* ADD PRINTER */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['form_type'] ?? '') === 'printer'
) {
    $printer_name = trim($_POST['printer_name'] ?? '');
    $office = trim($_POST['office'] ?? '');

    if ($printer_name !== '' && $office !== '') {
        $stmt = $pdo->prepare("INSERT INTO printers (printer_name, brand_model, serial_number, assigned_to, office, remarks, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $printer_name,
            trim($_POST['brand_model'] ?? ''),
            trim($_POST['serial_number'] ?? ''),
            trim($_POST['assigned_to'] ?? ''),
            $office,
            trim($_POST['remarks'] ?? ''),
            trim($_POST['status'] ?? 'Working')
        ]);

        header('Location: record.php?added=printer#inventory-list');
        exit;
    }

    $error = 'Please enter the Printer Name and select an Office.';
}

// Fetch users for assignment dropdowns
$usersStmt = $pdo->query("SELECT * FROM users ORDER BY full_name ASC");
$users = $usersStmt->fetchAll();

// Fetch computers and printers from the PostgreSQL database
$computers = $pdo->query("SELECT * FROM computers ORDER BY id DESC")->fetchAll();
$printers = $pdo->query("SELECT * FROM printers ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Equipment Record - Inventory System</title>

<link rel="stylesheet" href="record.css">
</head>

<body>

<!-- SIDEBAR -->
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
            <span>♙</span> Assigned Users
        </a>
    </nav>

</aside>

<!-- MAIN CONTENT -->
<main class="main">

    <!-- HEADER -->
    <header class="header">

        <h1>Equipment Record</h1>

        <!-- ADD EQUIPMENT DROPDOWN -->
        <div class="equipment-dropdown">

            <button
                type="button"
                class="button"
                id="equipmentButton"
                aria-expanded="false"
                aria-controls="equipmentMenu"
            >
                + Add Equipment <span>⌄</span>
            </button>

            <div class="equipment-menu" id="equipmentMenu">

                <button
                    type="button"
                    class="equipment-option"
                    id="openComputerModal"
                >
                    + Add Computer
                </button>

                <button
                    type="button"
                    class="equipment-option"
                    id="openPrinterModal"
                >
                    + Add Printer
                </button>

            </div>

        </div>

    </header>

    <p class="muted">
        View and search the computer and printer records in your inventory.
    </p>

    <!-- SUCCESS MESSAGE -->
    <?php if (isset($_GET['added'])): ?>

        <div class="notice">
            <?= $_GET['added'] === 'printer' ? 'Printer' : 'Computer' ?>
            record added successfully to the database.
        </div>

    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="notice">
            <?= $_GET['updated'] === 'printer' ? 'Printer' : 'Computer' ?> record updated successfully.
        </div>
    <?php endif; ?>

    <!-- ERROR MESSAGE -->
    <?php if (!empty($error)): ?>

        <div class="notice">
            <?= e($error) ?>
        </div>

    <?php endif; ?>

    <!-- UNIFIED EQUIPMENT RECORD -->
    <section class="panel" id="inventory-list">

        <div class="panel-head">
            <h2>Equipment Record</h2>
            <span class="muted" style="margin:0" id="recordCount">
                <?= count($computers) + count($printers) ?> records
            </span>
        </div>

        <!-- EQUIPMENT SEARCH -->
        <div class="search">
            <input
                type="search"
                id="tableSearch"
                placeholder="Search equipment, type, user, or office..."
            >

            <button class="button" type="button" id="searchButton">
                Search
            </button>

            <button class="button gray" type="button" id="clearSearch">
                Clear
            </button>
        </div>

        <!-- ONE TABLE FOR COMPUTERS AND PRINTERS -->
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

                        <td>
                            <?= e($pc['computer_name'] ?? '') ?>
                        </td>

                        <td>
                            <?= e($pc['office'] ?? '') ?>
                        </td>

                        <td>
                            <?= e($pc['ram'] ?? '—') ?>
                        </td>

                        <td>
                            <?= e($pc['cpu'] ?? '—') ?>
                        </td>

                        <td>
                            <?= e($pc['ssd'] ?? '—') ?>
                        </td>

                        <td>
                            <?= e($pc['hd'] ?? '—') ?>
                        </td>

                        <td>
                            <?= e(trim(($pc['brand'] ?? '') . ' ' . ($pc['model'] ?? '')) ?: '—') ?>
                        </td>

                        <td>
                            <?= e($pc['serial_number'] ?? '—') ?>
                        </td>

                        <td>
                            <?= e($pc['mac_address'] ?? '—') ?>
                        </td>

                        <td>
                            <?php $assignedUser = trim((string)($pc['assigned_to'] ?? '')); ?>

                            <?php if ($assignedUser !== ''): ?>

                                <a
                                    class="assigned-user-link"
                                    href="#"
                                    data-user="<?= e($assignedUser) ?>"
                                    onclick="openUserProfile(event, this)"
                                >
                                    <?= e($assignedUser) ?>
                                </a>

                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= e($pc['remarks'] ?? '—') ?>
                        </td>

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
                                data-record="<?= e(json_encode($pc, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>"
                            >
                                Edit
                            </button>
                        </td>
                    </tr>

                <?php endforeach; ?>

                <?php foreach ($printers as $printer): ?>

                    <tr>
                        <td>Printer</td>

                        <td>
                            <?= e($printer['printer_name'] ?? '') ?>
                        </td>

                        <td>
                            <?= e($printer['office'] ?? '') ?>
                        </td>

                        <td>—</td>
                        <td>—</td>
                        <td>—</td>
                        <td>—</td>

                        <td>
                            <?= e($printer['brand_model'] ?? '—') ?>
                        </td>

                        <td>
                            <?= e($printer['serial_number'] ?? '—') ?>
                        </td>

                        <td>—</td>

                        <td>
                            <?php $assignedUser = trim((string)($printer['assigned_to'] ?? '')); ?>

                            <?php if ($assignedUser !== ''): ?>

                                <a
                                    class="assigned-user-link"
                                    href="#"
                                    data-user="<?= e($assignedUser) ?>"
                                    onclick="openUserProfile(event, this)"
                                >
                                    <?= e($assignedUser) ?>
                                </a>

                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= e($printer['remarks'] ?? '—') ?>
                        </td>

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
                                data-record="<?= e(json_encode($printer, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>"
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
                <?= (count($computers) + count($printers)) ? 'style="display:none"' : '' ?>
            >
                No equipment records yet. Choose Add Equipment to add a computer or printer.
            </div>

        </div>
    </section>

    <?php
    /* Prepare assigned-user equipment data for the profile popup. */
    $userProfileData = [];

    foreach ($computers as $pc) {

        $name = trim((string)($pc['assigned_to'] ?? ''));

        if ($name === '') continue;

        $key = mb_strtolower($name);

        if (!isset($userProfileData[$key])) {
            $userProfileData[$key] = [
                'name' => $name,
                'equipment' => []
            ];
        }

        $userProfileData[$key]['equipment'][] = [
            'type' => 'Computer',
            'name' => $pc['computer_name'] ?? '—',
            'office' => $pc['office'] ?? '—',
            'brand_model' => trim(($pc['brand'] ?? '') . ' ' . ($pc['model'] ?? '')) ?: '—',
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

    foreach ($printers as $printer) {

        $name = trim((string)($printer['assigned_to'] ?? ''));

        if ($name === '') continue;

        $key = mb_strtolower($name);

        if (!isset($userProfileData[$key])) {
            $userProfileData[$key] = [
                'name' => $name,
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
    ?>

    <!-- ASSIGNED USER EQUIPMENT CARD POPUP -->
    <div class="modal" id="userProfileModal" aria-hidden="true">

        <div
            class="modal-content user-profile-modal-content"
            role="dialog"
            aria-modal="true"
            aria-labelledby="userProfileTitle"
        >

            <div class="modal-header">

                <div>
                    <h2 id="userProfileTitle">Assigned User Profiles</h2>

                    <p class="muted" style="margin:5px 0 0">
                        View this user's profile and assigned computers or printers.
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

                <div class="user-profile-toolbar">

                    <input
                        type="search"
                        id="userEquipmentSearch"
                        placeholder="Search user, office, or equipment..."
                        aria-label="Search assigned equipment"
                    >

                    <button
                        type="button"
                        class="button gray"
                        id="clearUserEquipmentSearch"
                    >
                        Clear
                    </button>

                </div>

                <div class="user-profile-grid" id="userProfileCardGrid"></div>

                <div
                    class="user-profile-empty"
                    id="userProfileEmpty"
                    style="display:none"
                >
                    No assigned equipment matches your search.
                </div>

            </div>

        </div>

    </div>

    <script type="application/json" id="userProfileData"><?= json_encode($userProfileData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>

</main>

<!-- ADD COMPUTER POPUP MODAL -->
<div class="modal" id="computerModal" aria-hidden="true">

    <div
        class="modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="computerModalTitle"
    >

        <div class="modal-header">

            <h2 id="computerModalTitle">Add Computer</h2>

            <button
                type="button"
                class="close-modal"
                id="closeComputerModal"
                aria-label="Close"
            >
                &times;
            </button>

        </div>

        <div class="modal-body">

            <form method="POST" action="record.php">

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
                            placeholder="Enter computer name"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="computer_office">
                            Office <span class="required">*</span>
                        </label>

                        <select id="computer_office" name="office" required>
                            <option value="">Select Office</option>
                            <option value="Administrative Office">Administrative Office</option>
                            <option value="Accounting Office">Accounting Office</option>
                            <option value="Assessors Office">Assessors Office</option>
                            <option value="HRMO - HR Office">HRMO - HR Office</option>
                            <option value="IT Office">IT Office</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="computer_ram">RAM</label>
                        <input
                            type="text"
                            id="computer_ram"
                            name="ram"
                            placeholder="e.g. 8GB"
                        >
                    </div>

                    <div class="field">
                        <label for="computer_cpu">CPU</label>
                        <input
                            type="text"
                            id="computer_cpu"
                            name="cpu"
                            placeholder="e.g. Intel Core i5"
                        >
                    </div>

                    <div class="field">
                        <label for="computer_ssd">SSD</label>
                        <input
                            type="text"
                            id="computer_ssd"
                            name="ssd"
                            placeholder="e.g. 256GB"
                        >
                    </div>

                    <div class="field">
                        <label for="computer_hd">HD</label>
                        <input
                            type="text"
                            id="computer_hd"
                            name="hd"
                            placeholder="e.g. 1TB"
                        >
                    </div>

                    <div class="field">
                        <label for="computer_brand">Brand</label>
                        <input
                            type="text"
                            id="computer_brand"
                            name="brand"
                            placeholder="Enter brand"
                        >
                    </div>

                    <div class="field">
                        <label for="computer_model">Model</label>
                        <input
                            type="text"
                            id="computer_model"
                            name="model"
                            placeholder="Enter model"
                        >
                    </div>

                    <div class="field">
                        <label for="computer_serial">Serial Number</label>
                        <input
                            type="text"
                            id="computer_serial"
                            name="serial_number"
                            placeholder="Enter serial number"
                        >
                    </div>

                    <div class="field">
                        <label for="computer_mac">MAC Address</label>
                        <input
                            type="text"
                            id="computer_mac"
                            name="mac_address"
                            placeholder="Enter MAC address"
                        >
                    </div>

                    <div class="field">
                        <label for="computer_assigned">Assigned User</label>

                        <select id="computer_assigned" name="assigned_to">
                            <option value="">Select Assigned User</option>

                            <?php foreach ($users as $user): ?>
                                <option value="<?= e($user['full_name'] ?? '') ?>">
                                    <?= e($user['full_name'] ?? '') ?><?= !empty($user['office']) ? ' — ' . e($user['office']) : '' ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="field" style="grid-column: 1 / -1;">
                        <label for="computer_remarks">Remarks</label>

                        <textarea
                            id="computer_remarks"
                            name="remarks"
                            rows="3"
                            placeholder="Enter remarks or additional information"
                        ></textarea>
                    </div>

                    <div class="field">
                        <label for="computer_status">Status</label>

                        <select id="computer_status" name="status">
                            <option value="Working">Working</option>
                            <option value="For Maintenance">For Maintenance</option>
                            <option value="For Checking">For Checking</option>
                            <option value="Retired">Retired</option>
                        </select>
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

<!-- ADD PRINTER POPUP MODAL -->
<div class="modal" id="printerModal" aria-hidden="true">

    <div
        class="modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="printerModalTitle"
    >

        <div class="modal-header">

            <h2 id="printerModalTitle">Add Printer</h2>

            <button
                type="button"
                class="close-modal"
                id="closePrinterModal"
                aria-label="Close"
            >
                &times;
            </button>

        </div>

        <div class="modal-body">

            <form method="POST" action="record.php">

                <input
                    type="hidden"
                    name="form_type"
                    value="printer"
                >

                <div class="field-grid">

                    <div class="field">
                        <label for="printer_name">
                            Printer Name <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="printer_name"
                            name="printer_name"
                            placeholder="Enter printer name"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="brand_model">
                            Brand / Model
                        </label>

                        <input
                            type="text"
                            id="brand_model"
                            name="brand_model"
                            placeholder="Enter brand or model"
                        >
                    </div>

                    <div class="field">
                        <label for="printer_serial">
                            Serial Number
                        </label>

                        <input
                            type="text"
                            id="printer_serial"
                            name="serial_number"
                            placeholder="Enter serial number"
                        >
                    </div>

                    <div class="field">
                        <label for="printer_user">
                            Assigned User
                        </label>

                        <select id="printer_user" name="assigned_to">

                            <option value="">Select Assigned User</option>

                            <?php foreach ($users as $user): ?>
                                <option value="<?= e($user['full_name'] ?? '') ?>">
                                    <?= e($user['full_name'] ?? '') ?><?= !empty($user['office']) ? ' — ' . e($user['office']) : '' ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="field">
                        <label for="printer_office">
                            Office <span class="required">*</span>
                        </label>

                        <select
                            id="printer_office"
                            name="office"
                            required
                        >

                            <option value="">Select Office</option>
                            <option value="Administrative Office">Administrative Office</option>
                            <option value="Accounting Office">Accounting Office</option>
                            <option value="Assessors Office">Assessors Office</option>
                            <option value="HRMO - HR Office">HRMO - HR Office</option>
                            <option value="IT Office">IT Office</option>
                            <option value="Other">Other</option>

                        </select>
                    </div>

                    <div class="field" style="grid-column: 1 / -1;">
                        <label for="printer_remarks">Remarks</label>

                        <textarea
                            id="printer_remarks"
                            name="remarks"
                            rows="3"
                            placeholder="Enter remarks or additional information"
                        ></textarea>
                    </div>

                    <div class="field">
                        <label for="printer_status">
                            Status
                        </label>

                        <select id="printer_status" name="status">
                            <option value="Working">Working</option>
                            <option value="For Maintenance">For Maintenance</option>
                            <option value="For Checking">For Checking</option>
                            <option value="Retired">Retired</option>
                        </select>
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

<!-- EDIT EQUIPMENT MODAL -->
<div class="modal" id="editModal" aria-hidden="true">

    <div
        class="modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editModalTitle"
    >

        <div class="modal-header">

            <h2 id="editModalTitle">Update Equipment</h2>

            <button
                type="button"
                class="close-modal"
                id="closeEditModal"
                aria-label="Close"
            >
                &times;
            </button>

        </div>

        <div class="modal-body">

            <form method="POST" action="record.php" id="editForm">

                <input type="hidden" name="form_type" id="editFormType">
                <input type="hidden" name="record_id" id="editRecordId">

                <!-- COMPUTER EDIT FIELDS -->
                <div class="field-grid" id="editComputerFields">

                    <div class="field">
                        <label for="edit_computer_name">
                            Computer Name <span class="required">*</span>
                        </label>
                        <input id="edit_computer_name" name="computer_name" required>
                    </div>

                    <div class="field">
                        <label for="edit_computer_office">
                            Office <span class="required">*</span>
                        </label>

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
                                    <?= e($user['full_name'] ?? '') ?><?= !empty($user['office']) ? ' — ' . e($user['office']) : '' ?>
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

                    <div class="field" style="grid-column:1 / -1">
                        <label for="edit_computer_remarks">Remarks</label>
                        <textarea id="edit_computer_remarks" name="remarks" rows="3"></textarea>
                    </div>

                </div>

                <!-- PRINTER EDIT FIELDS -->
                <div class="field-grid" id="editPrinterFields" style="display:none">

                    <div class="field">
                        <label for="edit_printer_name">
                            Printer Name <span class="required">*</span>
                        </label>
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
                                    <?= e($user['full_name'] ?? '') ?><?= !empty($user['office']) ? ' — ' . e($user['office']) : '' ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="field">
                        <label for="edit_printer_office">
                            Office <span class="required">*</span>
                        </label>

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

                    <div class="field" style="grid-column:1 / -1">
                        <label for="edit_printer_remarks">Remarks</label>
                        <textarea id="edit_printer_remarks" name="remarks" rows="3"></textarea>
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

/* =================================
ASSIGNED USER PROFILE POPUP
================================= */

const userProfileData = JSON.parse(
    document.getElementById('userProfileData').textContent || '{}'
);

const userProfileModal = document.getElementById('userProfileModal');
const userProfileCardGrid = document.getElementById('userProfileCardGrid');
const userEquipmentSearch = document.getElementById('userEquipmentSearch');

let activeUserProfile = null;

function createUserProfileElement(tag, className, value) {

    const element = document.createElement(tag);

    if (className) {
        element.className = className;
    }

    if (value !== undefined) {
        element.textContent = value;
    }

    return element;
}

function renderClickedUserProfile() {

    userProfileCardGrid.replaceChildren();

    const profile = activeUserProfile;
    const query = userEquipmentSearch.value.trim().toLocaleLowerCase();

    if (!profile) return;

    const items = profile.equipment || [];

    const offices = [
        ...new Set(
            items.map(item => item.office || '').filter(Boolean)
        )
    ];

    const searchable = [
        profile.name,
        ...offices,
        ...items.flatMap(item => [
            item.type,
            item.name,
            item.brand_model,
            item.serial,
            item.status,
            item.remarks
        ])
    ].join(' ').toLocaleLowerCase();

    const matches = !query || searchable.includes(query);

    document.getElementById('userProfileEmpty').style.display =
        matches ? 'none' : '';

    if (!matches) return;

    const computerCount = items.filter(
        item => item.type === 'Computer'
    ).length;

    const printerCount = items.filter(
        item => item.type === 'Printer'
    ).length;

    const initials = (profile.name || '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .map(part => Array.from(part)[0] || '')
        .join('')
        .slice(0, 2)
        .toUpperCase() || '?';

    const card = createUserProfileElement(
        'article',
        'user-profile-card'
    );

    const head = createUserProfileElement(
        'div',
        'user-profile-card-head'
    );

    head.appendChild(
        createUserProfileElement(
            'div',
            'user-profile-avatar',
            initials
        )
    );

    head.appendChild(
        createUserProfileElement(
            'h3',
            'user-profile-card-name',
            profile.name
        )
    );

    head.appendChild(
        createUserProfileElement(
            'div',
            'user-profile-office',
            offices.join(', ') || 'Office not specified'
        )
    );

    const stats = createUserProfileElement(
        'div',
        'user-profile-stats'
    );

    [
        items.length + ' assigned item(s)',
        computerCount + ' computer(s)',
        printerCount + ' printer(s)'
    ].forEach(label => {

        stats.appendChild(
            createUserProfileElement(
                'span',
                'user-profile-stat',
                label
            )
        );

    });

    head.appendChild(stats);
    card.appendChild(head);

    const equipment = createUserProfileElement(
        'div',
        'user-profile-equipment'
    );

    equipment.appendChild(
        createUserProfileElement(
            'h3',
            '',
            'Assigned Equipment'
        )
    );

    items.forEach(item => {

        const itemBox = createUserProfileElement(
            'div',
            'user-profile-equipment-item'
        );

        itemBox.appendChild(
            createUserProfileElement(
                'div',
                'user-profile-equipment-title',
                (item.type || 'Equipment') + ': ' + (item.name || '—')
            )
        );

        const details = createUserProfileElement(
            'div',
            'user-profile-details'
        );

        const detailRows = [
            ['Office / Division', item.office],
            ['Brand / Model', item.brand_model],
            ['RAM', item.ram],
            ['CPU', item.cpu],
            ['SSD', item.ssd],
            ['HD', item.hd],
            ['Serial Number', item.serial],
            ['MAC Address', item.mac_address]
        ];

        detailRows.forEach(([label, value]) => {

            if (value && value !== '—') {

                const row = createUserProfileElement(
                    'div',
                    'user-profile-detail-row'
                );

                row.appendChild(
                    createUserProfileElement(
                        'span',
                        'user-profile-detail-label',
                        label
                    )
                );

                row.appendChild(
                    createUserProfileElement(
                        'span',
                        'user-profile-detail-value',
                        value
                    )
                );

                details.appendChild(row);
            }

        });

        itemBox.appendChild(details);

        const statusLine = createUserProfileElement(
            'div',
            'user-profile-status-line'
        );

        statusLine.appendChild(
            document.createTextNode('Status: ')
        );

        const statusClass =
            item.status === 'Working' ? 'working' :
            item.status === 'For Maintenance' ? 'maintenance' :
            item.status === 'Retired' ? 'retired' :
            'checking';

        statusLine.appendChild(
            createUserProfileElement(
                'span',
                'status ' + statusClass,
                item.status || '—'
            )
        );

        itemBox.appendChild(statusLine);

        if (
            (item.remarks || '').trim() &&
            item.remarks !== '—'
        ) {
            itemBox.appendChild(
                createUserProfileElement(
                    'div',
                    '',
                    'Remarks: ' + item.remarks
                )
            );
        }

        equipment.appendChild(itemBox);

    });

    card.appendChild(equipment);
    userProfileCardGrid.appendChild(card);
}

function openUserProfile(event, link) {

    event.preventDefault();

    const userName = link.dataset.user || '';

    activeUserProfile =
        userProfileData[userName.trim().toLocaleLowerCase()] ||
        {
            name: userName,
            equipment: []
        };

    document.getElementById('userProfileTitle').textContent =
        'Assigned User Profiles';

    userEquipmentSearch.value = '';

    renderClickedUserProfile();

    userProfileModal.classList.add('show');

    userProfileModal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.style.overflow = 'hidden';
}

function closeUserProfile() {

    userProfileModal.classList.remove('show');

    userProfileModal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.style.overflow = '';
}

document.getElementById('closeUserProfileModal')
    .addEventListener('click', closeUserProfile);

document.getElementById('clearUserEquipmentSearch')
    .addEventListener('click', function() {

        userEquipmentSearch.value = '';

        renderClickedUserProfile();

        userEquipmentSearch.focus();

    });

userEquipmentSearch.addEventListener(
    'input',
    renderClickedUserProfile
);

userProfileModal.addEventListener('click', function(event) {

    if (event.target === userProfileModal) {
        closeUserProfile();
    }

});

document.addEventListener('keydown', function(event) {

    if (
        event.key === 'Escape' &&
        userProfileModal.classList.contains('show')
    ) {
        closeUserProfile();
    }

});


/* =================================
ADD EQUIPMENT DROPDOWN
================================= */

const equipmentButton =
    document.getElementById('equipmentButton');

const equipmentMenu =
    document.getElementById('equipmentMenu');

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

        equipmentButton.setAttribute(
            'aria-expanded',
            'false'
        );

    }

});


/* =================================
ADD COMPUTER MODAL
================================= */

const computerModal =
    document.getElementById('computerModal');

function openComputerModal() {

    equipmentMenu.classList.remove('show');

    equipmentButton.setAttribute(
        'aria-expanded',
        'false'
    );

    computerModal.classList.add('show');

    computerModal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.getElementById('computer_name').focus();
}

function closeComputerModal() {

    computerModal.classList.remove('show');

    computerModal.setAttribute(
        'aria-hidden',
        'true'
    );
}

document.getElementById('openComputerModal')
    .addEventListener('click', openComputerModal);

document.getElementById('closeComputerModal')
    .addEventListener('click', closeComputerModal);

document.getElementById('cancelComputerModal')
    .addEventListener('click', closeComputerModal);

computerModal.addEventListener('click', function(event) {

    if (event.target === computerModal) {
        closeComputerModal();
    }

});


/* =================================
ADD PRINTER MODAL
================================= */

const printerModal =
    document.getElementById('printerModal');

function openPrinterModal() {

    equipmentMenu.classList.remove('show');

    equipmentButton.setAttribute(
        'aria-expanded',
        'false'
    );

    printerModal.classList.add('show');

    printerModal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.getElementById('printer_name').focus();
}

function closePrinterModal() {

    printerModal.classList.remove('show');

    printerModal.setAttribute(
        'aria-hidden',
        'true'
    );
}

document.getElementById('openPrinterModal')
    .addEventListener('click', openPrinterModal);

document.getElementById('closePrinterModal')
    .addEventListener('click', closePrinterModal);

document.getElementById('cancelPrinterModal')
    .addEventListener('click', closePrinterModal);

printerModal.addEventListener('click', function(event) {

    if (event.target === printerModal) {
        closePrinterModal();
    }

});

document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {

        closeComputerModal();
        closePrinterModal();
        closeEditModal();

        equipmentMenu.classList.remove('show');

        equipmentButton.setAttribute(
            'aria-expanded',
            'false'
        );

    }

});


/* =================================
EDIT EQUIPMENT
================================= */

const editModal =
    document.getElementById('editModal');

function closeEditModal() {

    editModal.classList.remove('show');

    editModal.setAttribute(
        'aria-hidden',
        'true'
    );
}

function setEditValue(id, value) {

    const field = document.getElementById(id);

    if (!field) return;

    const safeValue = value ?? '';

    if (
        field.tagName === 'SELECT' &&
        safeValue !== '' &&
        !Array.from(field.options).some(
            option => option.value === safeValue
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
        const id = this.dataset.id;
        const record = JSON.parse(this.dataset.record);

        document.getElementById('editRecordId').value = id;

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

            setEditValue(
                'edit_computer_name',
                record.computer_name
            );

            setEditValue(
                'edit_computer_office',
                record.office
            );

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

            document.getElementById(
                'edit_computer_name'
            ).focus();

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

            document.getElementById(
                'edit_printer_name'
            ).focus();

        }

        editModal.classList.add('show');

        editModal.setAttribute(
            'aria-hidden',
            'false'
        );

    });

});

document.getElementById('closeEditModal')
    .addEventListener('click', closeEditModal);

document.getElementById('cancelEditModal')
    .addEventListener('click', closeEditModal);

editModal.addEventListener('click', event => {

    if (event.target === editModal) {
        closeEditModal();
    }

});


/* =================================
COMPUTER TABLE SEARCH
================================= */

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


/* =================================
REOPEN MODAL IF VALIDATION FAILS
================================= */

<?php if (!empty($computerError)): ?>
openComputerModal();
<?php endif; ?>

<?php if (!empty($error)): ?>
openPrinterModal();
<?php endif; ?>

</script>

</body>
</html>