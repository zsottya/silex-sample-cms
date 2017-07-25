<?php 
namespace App\Categories;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silex\Provider\SessionServiceProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \App\User\UserModel;

class CategoriesController 
{
    /**
     * Listing the Categories
     *
     * @param Application $app
     * @return Twig rendered template html
     */
    public static function main($app) 
    {        
        // Getting the categories for list
        $categories = $app['db']->fetchAll('SELECT * FROM categories ORDER BY title ASC;', []);

        // This will be available in the view
        $params = [
            'categories' => $categories
        ];

        return render('Categories/Views/categories.twig', $params);
    }

    /**
     * Adding new category
     *
     * @param Application $app
     * @return Twig rendered template html
     */
    public static function add($app) 
    {        
        return self::form($app, 0);
    }

    /**
     * Editing category
     *
     * @param Application $app
     * @return Twig rendered template html
     */
    public static function edit($app) 
    {
        $categoryId = get('id');
        return self::form($app, $categoryId);        
    }

    public static function form($app, $categoryId = 0) 
    {
        $categoryId = intval($categoryId);
        $category = ['title'=>''];

        // If there is any category id
        if ($categoryId > 0) {
            $category = $app['db']->fetchAssoc('SELECT * FROM categories WHERE id = ?;', [ intval($categoryId) ]);
        }

        // Setup the variables for the template 
        $params = [
            'id' => $categoryId,
            'edit_mode' => $categoryId > 0,
            'category' => [
                'title' => get('title', $category['title'])
            ]
        ];
    
        // Return the template
        return render('Categories/Views/categories.form.twig', $params);
    }

    /**
     * Saving category
     *
     * @param Application $app
     * @return Redirect
     */
    public static function save($app, $request) 
    {
        $categoryId = intval( get('id') );
        $theTitle = get('title');

        if (!empty($theTitle)) {
            
            if ($categoryId > 0) {
                try {
                    $qb = $app['db']->createQueryBuilder();
                    $q = $qb->update('categories', 'p');
                    $q->set('p.title', $qb->expr()->literal($theTitle));
                    $q->where('id='.$categoryId);
                    $q->execute();  //this returns the updated row count
                    $status = true;
                    $returnID = $categoryId;         
                } catch(Exception $e) {
                    $status = false;
                }
            } 
            else {
                $status = $app['db']->insert('categories', ['title'=>$theTitle]);
                $returnID = $app['db']->lastInsertId();         
            }

            if ($status) {                
                $app['session']->getFlashBag()->add('message', ['Sikeres művelet!', true]);   
                return $app->redirect($app['url_generator']->generate('admin-categories-edit', ['id' => $returnID]));
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
        $categoryId = intval( get('id') );
            
        try {
            $qb = $app['db']->createQueryBuilder();
            $qb->delete('categories');
            $qb->where('categories.id = :cid');
            $qb->setParameter('cid', $categoryId);
            $qb->execute();

            $app['session']->getFlashBag()->add('message', ['Sikeres művelet', true]);
            return new RedirectResponse($request->headers->get('referer'));            
        } catch(Exception $e) {
            $app['session']->getFlashBag()->add('message', ['Sikertelen művelet', false]);
            return new RedirectResponse($request->headers->get('referer'));
        }
    }
}