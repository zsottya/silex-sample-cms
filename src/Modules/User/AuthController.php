<?php 
namespace App\User;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silex\Provider\SessionServiceProvider;
use \App\User\UserModel;

class AuthController 
{
    /**
     * Login redirect page
     *
     * @param Application $app
     * @return Twig rendered html
     */
    public static function login($app, $request) 
    {        
        return render('User/Views/login.twig', [
            'error'         => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username')
        ]);
    }
    /**
     * Login redirect page
     *
     * @param Application $app
     * @return Redirect call
     */
    public static function redirect($app) 
    {
        try {
            if ($app['security.authorization_checker']->isGranted('ROLE_USER')) {
                return $app->redirect($app['url_generator']->generate('admin-home'));
            }
        } catch(Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException $error) {
            // Handle not required yet 
        }
        
        return $app->redirect($app['url_generator']->generate('login'));
    }
}