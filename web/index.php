<?php 
define('APP_PATH', realpath(__DIR__.'/..'));

// Load autoloader functionality
require_once APP_PATH . '/vendor/autoload.php';

// Web app initializer
$app = require APP_PATH . '/src/app.php';

// In debug mode only the localhost access available
if ($app['debug'] && 
    (
        isset($_SERVER['HTTP_CLIENT_IP']) ||
        isset($_SERVER['HTTP_X_FORWARDED_FOR']) ||
        !in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', 'fe80::1', '::1'))
    )
) {
    header('HTTP/1.0 403 Forbidden');
    exit('A weboldal jelenleg nem elérhető!');
}

// Load common helper functions
require APP_PATH . '/src/Helpers/CommonFunctions.php';

// Route handler
require APP_PATH . '/src/routes.php';

// Starting the web app
$app->run();