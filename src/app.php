<?php
use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Provider\MonologServiceProvider;
use Silex\Application\MonologTrait;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Monolog\Logger;
use \App\User\UserProvider;

$app = new Application();

// Load app configurations
require APP_PATH . '/config/config.php';

// Registering other required providers for base functionality (including session and database providers)
$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new HttpFragmentServiceProvider());
$app->register(new SessionServiceProvider());
$app->register(new DoctrineServiceProvider());

// Log provider
$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../var/app.log',
    'monolog.level' => $app['debug'] ? Logger::DEBUG : Logger::WARNING
));

// Twig Provider setup 
$app->register(new TwigServiceProvider(), [
    'twig.path' => __DIR__.'/../src',
    'twig.options' => ['cache' => __DIR__.'/../var/cache/twig']
]);

// Firewall setup 
// Login path is: "/"
// Secured path started with: "/admin/"
$app->register(new SecurityServiceProvider(), [
    'security.firewalls' => [
        'secured' => [
            'pattern' => '^/admin',
            'form' => [
                'login_path' => '/login', 
                'check_path' => '/admin/login_check',
                'always_use_default_target_path' => true,
                'default_target_path' => '/admin'
            ],
            'logout' => [
                'logout_path' => '/admin/logout', 
                'invalidate_session' => true
            ],
            'users' => function() use ($app) {
                return new UserProvider($app['db']);
            }
        ]
    ]
]);
$app['security.access_rules'] = array(
    ['^.*$', 'IS_AUTHENTICATED_ANONYMOUSLY'],
    ['^/admin', 'ROLE_USER']
);

// Booting for the providers 
$app->boot();

// Twig extend
$app['twig'] = $app->extend('twig', function ($twig, $app) {
    return $twig;
});

// Checking user login status, if not logged in and the url is /admin then it will redirect the user to the front page
$app->before(function (Request $request) use ($app) {
    if (strpos(trim($request->getRequestUri()), '/admin') === false) {
        return;
    }

    try {
        if ($app['security.authorization_checker']->isGranted('ROLE_USER')) {
            $token = $app['security.token_storage']->getToken();
            
            if (null !== $token) {
                $user = $token->getUser();
            } 
            else {
                throw new AuthenticationCredentialsNotFoundException;
            }
        }
    } catch(AuthenticationCredentialsNotFoundException $e) {
        $user = null;
    }

    if ($user === null) {
        $app['session']->set('user', null);

        return new RedirectResponse('/');
    } 
    else if ($app['session']->get('user') === null) {
        $uid = 0;
        $dbUser = $app['db']->fetchAssoc('SELECT id FROM users WHERE username = ?;', [ $user->getUsername() ]);
        if ($dbUser) {
            $uid = intval($dbUser['id']);
        }

        $app['session']->set('user', [
            'id' => $uid,
            'name' => $user->getUsername(),
            'roles' => $user->getRoles()
        ]);
    }
});

// Handle url errors (with base error codes)
$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    $templates = [
        $app['cms.theme'] . '/Errors/'.$code.'.twig',
        $app['cms.theme'] . '/Errors/'.substr($code, 0, 2).'x.twig',
        $app['cms.theme'] . '/Errors/'.substr($code, 0, 1).'xx.twig',
        $app['cms.theme'] . '/Errors/default.twig',
    ];

    return new Response($app['twig']->resolveTemplate($templates)->render(['code' => $code]), $code);
});

// Returning the $app Silex Application object
return $app;
