
<?php
session_start();
require_once __DIR__ . '/db.php';

/* =========================================
   HELPER FUNCTIONS
========================================= */

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
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

            $idNumber = trim($_POST['user_id_number'] ?? '');
            $name = trim($_POST['full_name'] ?? '');
            $position = trim($_POST['position'] ?? '');
            $jobDescription = trim($_POST['job_description'] ?? '');
            $office = trim($_POST['office'] ?? '');

            $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

            if ($name === '') {

                $error = 'Please enter the full name.';
                $showModal = true;

            } elseif ($idNumber === '') {

                $error = 'Please enter the ID number.';
                $showModal = true;

            } elseif (
                $action === 'edit_user' &&
                (!$userId || $userId <= 0)
            ) {

                $error = 'Invalid user ID.';
                $showModal = true;

            } else {

                try {

                    // Check duplicate ID number.
                    $check = $pdo->prepare("
                        SELECT id
                        FROM users
                        WHERE user_id_number = ?
                        AND id != ?
                        LIMIT 1
                    ");

                    $excludeId = $action === 'edit_user'
                        ? (int)$userId
                        : 0;

                    $check->execute([$idNumber, $excludeId]);

                    if ($check->fetch()) {

                        $error = 'This ID number is already registered.';
                        $showModal = true;

                    } else {

                        if ($action === 'add_user') {

                            $stmt = $pdo->prepare("
                                INSERT INTO users
                                (
                                    user_id_number,
                                    full_name,
                                    position,
                                    job_description,
                                    office
                                )
                                VALUES (?, ?, ?, ?, ?)
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
                    }

                } catch (PDOException $ex) {

                    error_log('Save user error: ' . $ex->getMessage());

                    $error = 'Unable to save user. Please check your database.';
                    $showModal = true;
                }
            }

        }

        /* =================================
           DELETE USER
        ================================= */

        elseif ($action === 'delete_user') {

            $id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

            if ($id && $id > 0) {

                try {

                    $stmt = $pdo->prepare("
                        DELETE FROM users WHERE id = ?
                    ");

                    $stmt->execute([$id]);

                    if ($stmt->rowCount() > 0) {
                        header('Location: user.php?deleted=1');
                        exit;
                    }

                    $error = 'User could not be found.';

                } catch (PDOException $ex) {

                    error_log('Delete user error: ' . $ex->getMessage());

                    $error = 'Unable to remove user. Check whether equipment is assigned to this user.';
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
            office
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
   SUCCESS MESSAGES
========================================= */

if (isset($_GET['added'])) {
    $message = 'User added successfully.';
} elseif (isset($_GET['updated'])) {
    $message = 'User updated successfully.';
} elseif (isset($_GET['deleted'])) {
    $message = 'User removed successfully.';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Inventory System</title>

<link rel="stylesheet" href="user.css">

<style>

/* =========================================
   CLICKABLE USER ID AND FULL NAME
========================================= */

.user-record-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;

    border: none;
    background: transparent;

    color: #334155;
    font-family: inherit;
    font-size: inherit;
    font-weight: 600;

    padding: 5px 3px;
    margin: 0;

    cursor: pointer;
    text-align: left;

    border-radius: 5px;
    transition: background .2s ease, color .2s ease;
}

.user-record-link:hover {
    background: #f1f5f9;
    color: #0f172a;
    text-decoration: underline;
    text-underline-offset: 3px;
}

.user-record-link:focus-visible {
    outline: 2px solid #64748b;
    outline-offset: 3px;
}

.user-record-link .click-icon {
    font-size: 12px;
    color: #94a3b8;
}

/* =========================================
   EDIT BUTTON
========================================= */

.edit-button {
    background: #e0edff;
    color: #1d4ed8;
    border: none;
    padding: 9px 14px;
    border-radius: 7px;
    font-weight: 600;
    cursor: pointer;
    margin-right: 5px;
}

.edit-button:hover {
    background: #c7ddff;
}

/* =========================================
   ADD / EDIT USER MODAL
========================================= */

.modal-overlay,
.user-record-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;

    background: rgba(15, 23, 42, .60);

    align-items: center;
    justify-content: center;

    padding: 15px;
}

.modal-overlay.show,
.user-record-overlay.show {
    display: flex;
}

.modal {
    background: white;
    width: 100%;
    max-width: 550px;
    max-height: 90vh;
    overflow-y: auto;

    border-radius: 13px;
    box-shadow: 0 20px 50px rgba(0,0,0,.2);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;

    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: #172554;
}

.close-modal {
    border: none;
    background: transparent;
    font-size: 26px;
    cursor: pointer;
    color: #64748b;
}

.modal-body {
    padding: 22px;
}

.field {
    margin-bottom: 17px;
}

.field label {
    display: block;
    margin-bottom: 7px;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
}

.field input,
.field textarea {
    width: 100%;
    box-sizing: border-box;

    padding: 11px 12px;

    border: 1px solid #cbd5e1;
    border-radius: 7px;

    font-family: inherit;
    font-size: 14px;
}

.field input:focus,
.field textarea:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.12);
}

.field textarea {
    resize: vertical;
    min-height: 85px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;

    padding: 17px 22px;

    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

.modal-footer button {
    padding: 10px 17px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.secondary-button {
    background: white;
    border: 1px solid #cbd5e1;
    color: #334155;
}

.primary-button {
    background: #2563eb;
    color: white;
    border: none;
}

/* =========================================
   EQUIPMENT RECORD POPUP
========================================= */

.user-record-dialog {
    display: flex;
    flex-direction: column;

    width: min(1200px, 98vw);
    height: min(850px, 92vh);

    background: #f3f6fc;
    border-radius: 13px;
    overflow: hidden;
}

.user-record-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    padding: 17px 22px;

    background: white;
    border-bottom: 1px solid #e2e8f0;
}

.user-record-header h2 {
    margin: 0;
    color: #172554;
}

.user-record-header p {
    color: #64748b;
    font-size: 13px;
    margin-bottom: 0;
}

.user-record-close {
    border: none;
    background: #f1f5f9;

    width: 38px;
    height: 38px;

    font-size: 25px;
    border-radius: 7px;
    cursor: pointer;
}

.user-record-content {
    flex: 1;
    min-height: 0;
}

.user-record-content iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}

/* =========================================
   NOTIFICATIONS
========================================= */

.notice {
    background: #dcfce7;
    color: #166534;
    padding: 12px 16px;
    border-radius: 7px;
    margin-bottom: 15px;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 12px 16px;
    border-radius: 7px;
    margin-bottom: 15px;
}

/* =========================================
   RESPONSIVE
========================================= */

@media(max-width: 650px) {

    .user-record-dialog {
        width: 100%;
        height: 95vh;
    }

    .user-record-header {
        padding: 13px;
    }

    .modal {
        max-height: 95vh;
    }
}

</style>

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
            ▦ Dashboard
        </a>

        <a class="nav-link" href="record.php">
            ▤ Equipment Record
        </a>

        <a class="nav-link active" href="user.php">
            ♙ Employee Record
        </a>

    </nav>

</aside>

<!-- =====================================
     MAIN CONTENT
===================================== -->

<main class="main">

    <header class="header">

        <h1>Assigned Employee Record</h1>

        <button
            type="button"
            class="button"
            onclick="openAddUserModal()"
        >
            Create Employee
        </button>

    </header>

    <p class="muted">
        Create and manage the list of employee who can be assigned
        to computers and printers.
    </p>

    <!-- SUCCESS MESSAGE -->

    <?php if ($message !== ''): ?>

        <div class="notice">
            <?= e($message) ?>
        </div>

    <?php endif; ?>

    <!-- ERROR MESSAGE -->

    <?php if ($error !== ''): ?>

        <div class="error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>

    <!-- =================================
         USER TABLE
    ================================== -->

    <section class="panel">

        <div class="panel-head">

            <h3></h3>

            <span class="muted">
                <?= count($users) ?>
                users
            </span>

        </div>

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>ID NUMBER</th>
                        <th>EMPLOYEE NAME</th>
                        <th>POSITION</th>
                        <th>OFFICE</th>
                        <th></th>
        
                    </tr>

                </thead>

                <tbody>

                <?php if (empty($users)): ?>

                    <tr>
                        <td colspan="5" class="empty">
                            No users added yet.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($users as $user): ?>

                    <tr>

                        <!-- CLICKABLE ID NUMBER -->

                        <td>

                            <button
                                type="button"
                                class="user-record-link"
                                data-user-name="<?= e($user['full_name']) ?>"
                                data-user-id="<?= e($user['user_id_number']) ?>"
                            >

                                <?= e($user['user_id_number'] ?: '—') ?>

                      

                            </button>

                        </td>

                        <!-- CLICKABLE FULL NAME -->

                        <td>

                            <button
                                type="button"
                                class="user-record-link"
                                data-user-name="<?= e($user['full_name']) ?>"
                                data-user-id="<?= e($user['user_id_number']) ?>"
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

                        <!-- ACTION -->

                        <td>

                            <!-- EDIT BUTTON -->

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

                            <!-- DELETE BUTTON -->

                            <form
                                method="POST"
                                action="user.php"
                                style="display:inline"
                                onsubmit="return confirm('Are you sure you want to remove this user?');"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e($_SESSION['user_csrf_token']) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete_user"
                                >

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?= (int)$user['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="button danger"
                                >
                                    Remove
                                </button>

                            </form>

                        </td>

                    </tr>

                    <?php endforeach; ?>

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
                Add New User
            </h2>

            <button
                type="button"
                class="close-modal"
                onclick="closeAddUserModal()"
            >
                &times;
            </button>

        </div>

        <form method="POST" action="user.php">

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
                    value="add_user"
                >

                <input
                    type="hidden"
                    name="user_id"
                    id="editUserId"
                >

                <!-- ID NUMBER -->

                <div class="field">

                    <label for="user_id_number">
                        ID Number <span style="color:red">*</span>
                    </label>

                    <input
                        type="text"
                        id="user_id_number"
                        name="user_id_number"
                        maxlength="50"
                        required
                    >

                </div>

                <!-- FULL NAME -->

                <div class="field">

                    <label for="full_name">
                        Employee Name <span style="color:red">*</span>
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="150"
                        required
                    >

                </div>

                <!-- POSITION -->

                <div class="field">

                    <label for="position">
                        Position
                    </label>

                    <input
                        type="text"
                        id="position"
                        name="position"
                        maxlength="150"
                    >

                </div>

                <!-- JOB DESCRIPTION -->

                <div class="field">

                    <label for="job_description">
                        Job Description
                    </label>

                    <input
                        type="text"
                        id="job_description"
                        name="position"
                        maxlength="150"
                    >


                </div>

                <!-- OFFICE -->

                <div class="field">

                    <label for="office">
                        Office
                    </label>

                    <input
                        type="text"
                        id="office"
                        name="office"
                        maxlength="150"
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
                    Add User
                </button>

            </div>

        </form>

    </div>

</div>

<!-- =====================================
     USER EQUIPMENT RECORD POPUP
===================================== -->

<div
    class="user-record-overlay"
    id="userRecordModal"
    aria-hidden="true"
>

    <section
        class="user-record-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="userRecordTitle"
    >

        <header class="user-record-header">

            <div>

                <h2 id="userRecordTitle">
                    Equipment Record
                </h2>

                <p id="userRecordDescription">
                    Equipment assigned to selected user.
                </p>

            </div>

            <button
                type="button"
                class="user-record-close"
                id="closeUserRecordModal"
                aria-label="Close equipment record"
            >
                &times;
            </button>

        </header>

        <div class="user-record-content">

            <iframe
                id="userRecordFrame"
                title="Selected user's equipment records"
                src="about:blank"
            ></iframe>

        </div>

    </section>

</div>

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
        'Add New User';

    document.getElementById('formAction').value =
        'add_user';

    document.getElementById('submitUserButton').textContent =
        'Add User';

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
        'Edit User';

    document.getElementById('formAction').value =
        'edit_user';

    document.getElementById('submitUserButton').textContent =
        'Save Changes';

    document.getElementById('editUserId').value =
        user.id;

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
   CLICKABLE ID NUMBER AND FULL NAME
===================================== */

const recordModal =
    document.getElementById('userRecordModal');

const recordFrame =
    document.getElementById('userRecordFrame');

const description =
    document.getElementById('userRecordDescription');

/*
   Both ID Number and Full Name use the
   same class: user-record-link.
*/

document.querySelectorAll('.user-record-link').forEach(function(button) {

    button.addEventListener('click', function() {

        const userName = this.dataset.userName || '';
        const userId = this.dataset.userId || '';

        // Update popup description.
        description.textContent =
            'Showing equipment assigned to: ' +
            userName +
            ' (ID: ' + userId + ')';

        // Send selected user to record.php.
        const params = new URLSearchParams({
            assigned_to: userName,
            embed: '1'
        });

        recordFrame.src =
            'record.php?' + params.toString();

        // Display popup.
        recordModal.classList.add('show');

        recordModal.setAttribute(
            'aria-hidden',
            'false'
        );

        document.body.style.overflow = 'hidden';

        document.getElementById('closeUserRecordModal').focus();

    });

});

/* =====================================
   CLOSE EQUIPMENT RECORD POPUP
===================================== */

function closeUserRecordModal() {

    recordModal.classList.remove('show');

    recordModal.setAttribute(
        'aria-hidden',
        'true'
    );

    // Stop iframe.
    recordFrame.src = 'about:blank';

    document.body.style.overflow = '';
}

document.getElementById('closeUserRecordModal')
    .addEventListener('click', closeUserRecordModal);

recordModal.addEventListener('click', function(event) {

    if (event.target === recordModal) {
        closeUserRecordModal();
    }

});

/* =====================================
   ESCAPE KEY
===================================== */

document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {

        closeAddUserModal();

        if (recordModal.classList.contains('show')) {
            closeUserRecordModal();
        }

    }

});

/* =====================================
   REOPEN MODAL AFTER VALIDATION ERROR
===================================== */

<?php if ($showModal): ?>

addUserModal.classList.add('show');

addUserModal.setAttribute('aria-hidden', 'false');

<?php endif; ?>

</script>

</body>
</html>
