<?php
session_start();
require_once __DIR__ . '/db.php';

// Escape output safely.
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Create CSRF token.
if (empty($_SESSION['user_csrf_token'])) {
    $_SESSION['user_csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';
$showModal = false;

// Handle form submissions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['user_csrf_token'], $token)
    ) {
        $error = 'Your session token is invalid. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        // ADD USER
        if ($action === 'add_user') {
            $name = trim($_POST['full_name'] ?? '');
            $office = trim($_POST['office'] ?? '');

            if ($name === '') {
                $error = 'Please enter the user\'s full name.';
                $showModal = true;
            } else {
                try {
                    // Check for an existing user with the same name.
                    $check = $pdo->prepare(
                        "SELECT id FROM users WHERE LOWER(full_name) = LOWER(?) LIMIT 1"
                    );
                    $check->execute([$name]);

                    if ($check->fetch()) {
                        $error = 'This user is already in the list.';
                        $showModal = true;
                    } else {
                        $stmt = $pdo->prepare(
                            "INSERT INTO users (full_name, office) VALUES (?, ?)"
                        );
                        $stmt->execute([$name, $office]);

                        header('Location: user.php?added=1');
                        exit;
                    }
                } catch (PDOException $e) {
                    error_log('Add user failed: ' . $e->getMessage());
                    $error = 'Unable to add the user. Please check your database table and try again.';
                    $showModal = true;
                }
            }

        // DELETE USER
        } elseif ($action === 'delete_user') {
            $id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

            if ($id !== false && $id !== null && $id > 0) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);

                    if ($stmt->rowCount() > 0) {
                        header('Location: user.php?deleted=1');
                        exit;
                    }

                    $error = 'The selected user could not be found.';
                } catch (PDOException $e) {
                    error_log('Delete user failed: ' . $e->getMessage());
                    $error = 'Unable to remove the user. Please try again.';
                }
            } else {
                $error = 'The selected user could not be found.';
            }
        }
    }
}

// Fetch all users from MySQL.
try {
    $stmt = $pdo->query("SELECT id, full_name, office FROM users ORDER BY full_name ASC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Fetch users failed: ' . $e->getMessage());
    $users = [];
    $error = 'Unable to load users. Please check that the users table exists.';
}

// Success messages.
if (isset($_GET['added'])) {
    $message = 'User added successfully.';
} elseif (isset($_GET['deleted'])) {
    $message = 'User removed from the list.';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Assigned Users | Inventory System</title>

    <link rel="stylesheet" href="user.css">

</head>

<body>

<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="brand">
        Inventory System
    </div>

    <div class="section-label">
        Main Menu
    </div>

    <nav>

        <a class="nav-link" href="index.php">
            <span>▦</span> Dashboard
        </a>

        <a class="nav-link" href="record.php">
            <span>▤</span> Equipment Record
        </a>

        <a class="nav-link active" href="user.php">
            <span>♙</span> Assigned Users
        </a>

    </nav>

</aside>

<!-- MAIN CONTENT -->

<main class="main">

    <!-- HEADER -->

    <header class="header">

        <h1>Assigned User</h1>

        <button
            type="button"
            class="button"
            onclick="openAddUserModal()"
        >
            Create User
        </button>

    </header>

    <p class="muted">
        Add and manage the list of people who can be assigned
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

    <!-- USER LIST -->

    <section class="panel">

        <div class="panel-head">

            <h2>User List</h2>

            <span
                class="muted"
                style="margin:0"
                id="recordCount"
            >
                <?= count($users) ?>
                user<?= count($users) === 1 ? '' : 's' ?>
            </span>

        </div>

        <div class="table-wrap">

            <table>

                <!-- TABLE HEADER -->

                <thead>

                    <tr>

                        <th>Full Name</th>

                        <th>Office / Division</th>

                        <th>Action</th>

                    </tr>

                </thead>

                <!-- TABLE BODY -->

                <tbody>

                <?php if (empty($users)): ?>

                    <!-- EMPTY TABLE -->

                    <tr>

                        <td colspan="3" class="empty">

                            No users added yet.
                            Click the Create User button to add your first user.

                        </td>

                    </tr>

                <?php else: ?>

                    <!-- DISPLAY USERS -->

                    <?php foreach ($users as $user): ?>

                        <tr>

                            <!-- FULL NAME -->

                            <td>

                                <strong>
                                    <?= e($user['full_name'] ?? '') ?>
                                </strong>

                            </td>

                            <!-- OFFICE / DIVISION -->

                            <td>

                                <?= e(
                                    ($user['office'] ?? '') !== ''
                                        ? $user['office']
                                        : '—'
                                ) ?>

                            </td>

                            <!-- ACTION -->

                            <td>

                                <form
                                    method="POST"
                                    action="user.php"
                                    onsubmit="return confirm('Remove this user from the list?');"
                                    style="margin:0"
                                >

                                    <!-- CSRF TOKEN -->

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION['user_csrf_token']) ?>"
                                    >

                                    <!-- ACTION -->

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete_user"
                                    >

                                    <!-- USER ID -->

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= e($user['id'] ?? '') ?>"
                                    >

                                    <!-- REMOVE BUTTON -->

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

<!-- ADD USER MODAL -->

<div
    class="modal-overlay <?= $showModal ? 'show' : '' ?>"
    id="addUserModal"
    onclick="handleModalOverlayClick(event)"
>

    <div
        class="modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modalTitle"
    >

        <!-- MODAL HEADER -->

        <div class="modal-header">

            <h2 id="modalTitle">
                Add New User
            </h2>

            <button
                type="button"
                class="close-modal"
                onclick="closeAddUserModal()"
                aria-label="Close modal"
            >
                &times;
            </button>

        </div>

        <!-- USER FORM -->

        <form
            method="POST"
            action="user.php"
            class="modal-form"
        >

            <div class="modal-body">

                <!-- CSRF TOKEN -->

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['user_csrf_token']) ?>"
                >

                <!-- ACTION -->

                <input
                    type="hidden"
                    name="action"
                    value="add_user"
                >

                <!-- FULL NAME -->

                <div class="field">

                    <label for="full_name">

                        Full Name

                        <span style="color:#ef4444">*</span>

                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="150"
                        required
                    >

                </div>

                <!-- OFFICE / DIVISION -->

                <div
                    class="field"
                    style="margin-top:17px;"
                >

                    <label for="office">
                        Office / Division
                    </label>

                    <input
                        type="text"
                        id="office"
                        name="office"
                        maxlength="150"
                    >

                </div>

            </div>

            <!-- MODAL FOOTER -->

            <div class="modal-footer">

                <button
                    type="button"
                    class="button secondary"
                    onclick="closeAddUserModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="button"
                >
                    Add User
                </button>

            </div>

        </form>

    </div>

</div>

<!-- JAVASCRIPT -->

<script>

// Get modal.
const addUserModal = document.getElementById('addUserModal');

// Open modal.
function openAddUserModal() {

    addUserModal.classList.add('show');

    setTimeout(function() {

        document.getElementById('full_name').focus();

    }, 100);

}

// Close modal.
function closeAddUserModal() {

    addUserModal.classList.remove('show');

}

// Close modal when clicking outside.
function handleModalOverlayClick(event) {

    if (event.target === addUserModal) {

        closeAddUserModal();

    }

}

// Close modal using Escape key.
document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {

        closeAddUserModal();

    }

});

// Reopen modal if there is a validation error.
<?php if ($showModal): ?>

    openAddUserModal();

<?php endif; ?>

</script>

</body>

</html>
