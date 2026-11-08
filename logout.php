
<?php

require_once __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| LOGOUT REQUEST SECURITY
|--------------------------------------------------------------------------
| Logout must be performed through POST with a valid CSRF token.
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    header('Allow: POST');

    exit('Method not allowed.');
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
*/

$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['logout_csrf'] ?? '';

if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $submittedToken === '' ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {

    http_response_code(403);

    exit(
        'Invalid logout request. Please refresh the page and try again.'
    );
}

/*
|--------------------------------------------------------------------------
| CLEAR SESSION DATA
|--------------------------------------------------------------------------
*/

$_SESSION = [];

/*
|--------------------------------------------------------------------------
| REMOVE SESSION COOKIE
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| DESTROY SESSION
|--------------------------------------------------------------------------
*/

session_destroy();

/*
|--------------------------------------------------------------------------
| REDIRECT TO LOGIN PAGE
|--------------------------------------------------------------------------
*/

header('Location: login.php');
exit;

