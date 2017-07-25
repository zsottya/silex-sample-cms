<?php 
namespace App\Products;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silex\Provider\SessionServiceProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \App\User\UserModel;

class ProductsController 
{
    /**
     * Listing the products
     *
     * @param Application $app
     * @return Twig rendered template html
     */
    public static function main($app) 
    {        
        $user = getUser();

        // Getting the products for list
        $products = $app['db']->fetchAll('SELECT * FROM products WHERE user_id = ? ORDER BY title ASC;', [ $user['id'] ]);

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
            'products' => $products
        ];

        return render('Products/Views/products.twig', $params);
    }

    /**
     * Adding new product
     *
     * @param Application $app
     * @return Twig rendered template html
     */
    public static function add($app) 
    {        
        return self::form($app, 0);
    }

    /**
     * Editing product
     *
     * @param Application $app
     * @return Twig rendered template html
     */
    public static function edit($app) 
    {
        $productId = get('id');
        return self::form($app, $productId);        
    }

    public static function form($app, $productId = 0) 
    {
        $productId = intval($productId);
        $user = getUser();

        // Console types
        $consoletypes = self::getConsoleTypes();        

        // Categories      
        $categories = self::getCategories();

        // If there is any product id
        $product = ['title'=>'', 'console'=>'', 'category'=>'', 'release_date'=>'', 'publisher'=>'', 'url'=>'', 'tags'=>'', 'metascore'=>'', 'image'=>''];
        if ($productId > 0) {
            $product = $app['db']->fetchAssoc('SELECT * FROM products WHERE user_id = ? AND id = ?;', [ $user['id'], intval($productId) ]);
        }

        // Setup the variables for the template 
        $params = [
            'id' => $productId,
            'edit_mode' => $productId > 0,
            'consoletypes' => $consoletypes,
            'categories' => $categories,
            'product' => [
                'title' => get('title', $product['title']),
                'console' => get('console', $product['console']),
                'category' => (int)get('category', $product['category']),
                'release_date' => get('release_date', $product['release_date']),
                'publisher' => get('publisher', $product['publisher']),
                'url' => get('url', $product['url']),
                'tags' => get('tags', $product['tags']),
                'metascore' => get('metascore', $product['metascore']),
                'image' => get('image', $product['image'])
            ]
        ];
    
        // Return the template
        return render('Products/Views/products.form.twig', $params);
    }

    /**
     * Saving product
     *
     * @param Application $app
     * @return Redirect
     */
    public static function save($app, $request) 
    {
        $user = getUser();
        $productId = intval( get('id') );
        $product = [];

        $image = $request->files->get('image');
        if ($image !== null) {
            $target =  APP_PATH . '/web/uploads/';
            if ($image->move($target, $image->getClientOriginalName())) {
                $product['image'] = $image->getClientOriginalName();
            }
        }

        $product['title'] = get('title');
        $product['console'] = get('console');
        $product['category'] = get('category');
        $product['release_date'] = get('release_date');
        $product['publisher'] = get('publisher');
        $product['url'] = get('url');
        $product['tags'] = get('tags');
        $product['metascore'] = get('metascore');

        if (!empty($product['title']) && !empty($product['console']) && !empty($product['category'])) {
            
            if ($productId > 0) {
                try {
                    $qb = $app['db']->createQueryBuilder();
                    $q = $qb->update('products', 'p');

                    foreach($product as $k=>$v) {
                        $q->set('p.'.$k, $qb->expr()->literal($v));
                    }

                    $q->where('id='.$productId);
                    $q->andWhere('user_id='.$user['id']);
                    $q->execute();  //this returns the updated row count
                    $status = true;
                    $returnID = $productId;         
                } catch(Exception $e) {
                    $status = false;
                }
            } 
            else {
                $product['user_id'] = $user['id'];

                if (!isset($product['image'])) {
                    $product['image'] = '';
                }

                $status = $app['db']->insert('products', $product);
                $returnID = $app['db']->lastInsertId();         
            }

            if ($status) {    
                $app['session']->getFlashBag()->add('message', ['Sikeres művelet!', true]);               
                return $app->redirect($app['url_generator']->generate('admin-products-edit', ['id' => $returnID]));
            }

            $app['session']->getFlashBag()->add('message', ['Sikertelen művelet!', false]);            
            return new RedirectResponse($request->headers->get('referer'));
        } 
        else {
            $app['session']->getFlashBag()->add('message', ['Kérlek tölts ki minden csillaggal jelölt mezőt!', false]);            
            return new RedirectResponse($request->headers->get('referer'));
        }
    }

    /**
     *  Deleting product
     *
     * @param Application $app
     * @return Twig rendered template html
     */
    public static function delete($app, $request) 
    {
        $user = getUser();
        $productId = intval( get('id') );
            
        try {
            $qb = $app['db']->createQueryBuilder();
            $qb->delete('products');
            $qb->where('products.id = :pid');
            $qb->andWhere('products.user_id = :pusid');
            $qb->setParameter('pid', $productId);
            $qb->setParameter('pusid', $user['id']);
            $qb->execute();

            $app['session']->getFlashBag()->add('message', ['Sikeres művelet', true]);
            return new RedirectResponse($request->headers->get('referer'));            
        } catch(Exception $e) {
            $app['session']->getFlashBag()->add('message', ['Sikertelen művelet', false]);
            return new RedirectResponse($request->headers->get('referer'));
        }
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