
<?php

require_once __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| REDIRECT ALREADY AUTHENTICATED USERS
|--------------------------------------------------------------------------
*/

if (!empty($_SESSION['account_id'])) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| INITIALIZE VARIABLES
|--------------------------------------------------------------------------
*/

$error = '';

/*
|--------------------------------------------------------------------------
| LOGIN CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (
    empty($_SESSION['login_csrf']) ||
    !is_string($_SESSION['login_csrf'])
) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | VALIDATE CSRF TOKEN
    |--------------------------------------------------------------------------
    */

    if (
        !is_string($token) ||
        !is_string($_SESSION['login_csrf']) ||
        $token === '' ||
        !hash_equals($_SESSION['login_csrf'], $token)
    ) {

        $error = 'Invalid request. Please refresh the page and try again.';

    } else {

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (
            !is_string($username) ||
            !is_string($password) ||
            $username === '' ||
            $password === ''
        ) {

            $error = 'Please enter your username and password.';

        } else {

            try {

                /*
                |--------------------------------------------------------------------------
                | FIND ACTIVE ACCOUNT
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare(
                    "SELECT id, employee_id, username, password, role, status
                     FROM accounts
                     WHERE username = ?
                     AND status = 'Active'
                     LIMIT 1"
                );

                $stmt->execute([$username]);

                $account = $stmt->fetch(PDO::FETCH_ASSOC);

                /*
                |--------------------------------------------------------------------------
                | VERIFY PASSWORD
                |--------------------------------------------------------------------------
                */

                if (
                    $account &&
                    password_verify($password, $account['password'])
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | REGENERATE SESSION ID
                    |--------------------------------------------------------------------------
                    */

                    session_regenerate_id(true);

                    /*
                    |--------------------------------------------------------------------------
                    | STORE ACCOUNT SESSION DATA
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION['account_id'] = $account['id'];
                    $_SESSION['username'] = $account['username'];
                    $_SESSION['role'] = $account['role'];
                    $_SESSION['employee_id'] = $account['employee_id'];

                    /*
                    |--------------------------------------------------------------------------
                    | GENERATE LOGOUT CSRF TOKEN
                    |--------------------------------------------------------------------------
                    | Required by the updated logout.php.
                    */

                    $_SESSION['logout_csrf'] = bin2hex(random_bytes(32));

                    /*
                    |--------------------------------------------------------------------------
                    | REMOVE LOGIN CSRF TOKEN
                    |--------------------------------------------------------------------------
                    */

                    unset($_SESSION['login_csrf']);

                    /*
                    |--------------------------------------------------------------------------
                    | REDIRECT TO DASHBOARD
                    |--------------------------------------------------------------------------
                    */

                    header('Location: index.php');
                    exit;

                } else {

                    $error = 'Invalid username or password.';

                }

            } catch (Throwable $e) {

                error_log('Login Error: ' . $e->getMessage());

                $error = 'Unable to process your login right now. Please try again later.';

            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Inventory System</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f3f6fb;
            font-family: Arial, sans-serif;
        }

        .login-container {
            width: 90%;
            max-width: 400px;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, .08);
        }

        h2 {
            margin-top: 0;
            color: #172033;
        }

        p {
            color: #718096;
        }

        label {
            display: block;
            margin: 16px 0 7px;
            color: #172033;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            outline: none;
        }

        input:focus {
            border-color: #2563eb;
        }

        button {
            width: 100%;
            padding: 13px;
            margin-top: 22px;
            background: #2563eb;
            color: white;
            border: 0;
            border-radius: 7px;
            cursor: pointer;
            font-size: 15px;
        }

        button:hover {
            background: #1d4ed8;
        }

        .error {
            color: #b91c1c;
            background: #fee2e2;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }

    </style>

</head>

<body>

    <div class="login-container">

        <h2>Inventory System</h2>

        <p>Sign in to your account</p>

        <?php if ($error): ?>

            <div class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="login.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['login_csrf'], ENT_QUOTES, 'UTF-8') ?>"
            >

            <label for="username">Username</label>

            <input
                type="text"
                id="username"
                name="username"
                required
                autocomplete="username"
                autofocus
            >

            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
            >

            <button type="submit">
                Login
            </button>

        </form>

    </div>

</body>

</html>
