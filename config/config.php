<?php
/**
 * Configuration générale de l'application
 * Ndere City Tourism
 */

// Configuration des sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mettre à 1 en production avec HTTPS

// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fuseau horaire
date_default_timezone_set('Africa/Douala');

// Configuration de l'application
define('APP_NAME', 'Ndere City Tourism');
define('APP_URL', 'http://localhost/city');
define('BASE_PATH', __DIR__ . '/..');

// Configuration des uploads
define('UPLOAD_PATH', BASE_PATH . '/public/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('MAX_IMAGES_PER_PLACE', 10);

// Configuration email
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-password');
define('MAIL_FROM', 'noreply@nderecity.com');
define('MAIL_FROM_NAME', 'Ndere City Tourism');

// Clés de sécurité
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // 1 heure

// Autoloader
spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/models/',
        BASE_PATH . '/controllers/',
        BASE_PATH . '/core/',
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Fonction d'aide pour générer un token CSRF
function generateCsrfToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Fonction d'aide pour vérifier le token CSRF
function verifyCsrfToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Fonction d'aide pour échapper les données
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Fonction d'aide pour rediriger
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Fonction d'aide pour vérifier l'authentification
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction d'aide pour vérifier si l'utilisateur est admin
function isAdmin() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'superadmin']);
}

// Fonction d'aide pour vérifier si l'utilisateur est superadmin
function isSuperAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'superadmin';
}
