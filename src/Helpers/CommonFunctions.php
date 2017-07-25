<?php 

/**
 * dump function
 * More readable format of var_dump function.
 * 
 * @return html
 */
function dump() 
{
    echo '<pre>';
    $args = func_get_args();
    if (count($args) > 0) {
        foreach($args as $arg) {
            var_dump( $arg );
        }
    }
    echo '</pre>';
}

/**
 * _log function
 * Monolog helper function, only the message needed
 *
 * @param String $message The log message
 * @return void
 */
function _log($message)
{
    global $app;
    $app['monolog']->debug($message);
}

/**
 * action function
 * The given $name class and method will be returned with $app param.
 *
 * @param string $name Class and method name
 * @return Class
 */
function action($name)
{
    global $app;

    return function(\Symfony\Component\HttpFoundation\Request $req) use($name, $app) { 
        return call_user_func($name, $app, $req); 
    };
}

/**
 * render function
 * Twig template renderer
 *
 * @param string $path  Subpath in /src/Modules dir
 * @param array $params Template variables
 * @return HTML
 */
function render($path='', $params=[]) 
{
    global $app;

    $params['title'] = $app['cms.title'];
    $params['theme'] = $app['cms.theme'];

    try {
        if ($app['security.authorization_checker']->isGranted('ROLE_USER')) {
            $params['is_admin_page'] = true;
        }
    } catch(\Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException $error) {
        $params['is_admin_page'] = false;
    }

    return $app['twig']->render('/Modules/' . $path, $params);
}

/**
 * getUser function
 * Get the currently logged in user model.
 *
 * @return mixed    If there is a logged in user, then the user model, otherwise the boolean false will be returned.
 */
function getUser() 
{
    global $app;

    try {
        if ($app['session']->get('user') !== null) {
            return $app['session']->get('user');
        }
    } catch(Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException $error) {}
    
    return $app->redirect($app['url_generator']->generate('login'));
}

/**
 * get function
 *
 * @param string $paramName     Name of the requested paramter (should define it on the routing section, for eg.: {id} will be 'id')
 * @param mixed  $defaultValue  This will be returned if the searched get/post value not available
 * @return Text
 */
function get($paramName='', $defaultValue='') 
{
    global $app;
    
    try {
        $value = $app['request_stack']->getCurrentRequest()->get( $paramName );
        return !empty($value) ? $value : $defaultValue;
    } catch(Exception $e) {}

    return $defaultValue;    
}
