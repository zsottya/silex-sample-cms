<?php 
namespace App\AppFrontend;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silex\Provider\SessionServiceProvider;
use \App\User\UserModel;

class AppFrontendController 
{
    /**
     * Main page
     *
     * @param Application $app
     * @return Twig rendered template html
     */
    public static function main($app) 
    {   
        // Handle search  
        $thisIsASearch = false;
        $searchString = '1';
        $searchValues = ['title'=>'', 'release_date'=>'', 'publisher'=>'', 'category'=>'', 'console'=>''];
        
        if (get('search', null) !== null) {
            $thisIsASearch = true;
            $postedSearchValues = get('search');

            $search = [];
            $searchString = [];

            foreach($postedSearchValues as $k => $v) {
                if (trim($v) != '') {
                    $search[] = trim($v);
                    $searchString[] = $k . ' = ? ';
                    $searchValues[ $k ] = trim($v);
                }
            }
        }

        // Fix the search string
        if (is_array($searchString)) {
            $searchString = count($searchString) ? implode(' AND ', $searchString) : 1;
        }
    
        // Getting the products for list
        $products = $app['db']->fetchAll('SELECT * FROM products WHERE '.$searchString.' ORDER BY title ASC;', $search);

        // Console types
        $consoletypes = self::getConsoleTypes();        

        // Categories      
        $categories = self::getCategories();

        // Modify the category and the console types values
        if (count($products)) {
            foreach($products as $i => $p) {
                $p['category'] = isset($categories[ $p['category'] ]) ? $categories[ $p['category'] ] : '';
                $p['console'] = isset($consoletypes[ $p['console'] ]) ? $consoletypes[ $p['console'] ] : '';

                $products[ $i ] = $p; 
            }
        }

        // This will be available in the view
        $params = [
            'products' => $products,
            'search' => $searchValues,
            'thisIsASearch' => $thisIsASearch
        ];

        return render('AppFrontend/Views/home.twig', $params);
    }

    /**
     * getCategories function
     * This method returns the associative array of categories.
     *
     * @return array Categories list (associative)
     */
    public static function getCategories() 
    {
        global $app;

        $categories = [];
        $categoriesFromDb = $app['db']->fetchAll('SELECT * FROM categories ORDER BY title ASC;', []);
        if (count($categoriesFromDb) > 0) {
            foreach($categoriesFromDb as $category) {
                $categories[ $category['id'] ] = $category['title'];
            }
        }

        return $categories;
    }

    /**
     * getConsoleTypes function
     * This method returns the associative array of console types.
     *
     * @return array Console type list (associative)
     */
    public static function getConsoleTypes() 
    {
        $consoletypes = [
            'ps3' => 'PS3',
            'ps4' => 'PS4',
            'xbox360' => 'Xbox 360',
            'xboxone' => 'Xbox One'
        ];

        return $consoletypes;
    }

}