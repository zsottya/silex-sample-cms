<?php 
namespace App\Admin;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silex\Provider\SessionServiceProvider;
use \App\User\UserModel;

class AdminController 
{
    /**
     * Admin dashboard page
     *
     * @param Application $app
     * @return Twig rendered template html
     */
    public static function dashboard($app) 
    {
        $params = [
            'username' => $app['session']->get('user')['name']
        ];

        return render('Admin/Views/admin.twig', $params);
    }
}