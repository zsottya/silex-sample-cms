<?php
// The public site
$siteAction = action('App\AppFrontend\AppFrontendController::main');
$app->get('/', $siteAction)->bind('app-frontend');
$app->post('/', $siteAction)->bind('app-frontend');

// Handle login requests
$app->get('/login', action('App\User\AuthController::login'))->bind('login');

// This method is responsible for login and admin url redirections (depending on the auth token)
$app->get('/redirect', action('App\User\AuthController::redirect'))->bind('login-redirect');

// Handle /admin/ request with AdminController (the sub methods automatically gets the $app variable)
$app->get('/admin/', action('App\Admin\AdminController::dashboard'))->bind('admin-home');

// Products 
$app->get('/admin/products/', action('App\Products\ProductsController::main'))->bind('admin-products-main');
$app->post('/admin/products/save/{id}', action('App\Products\ProductsController::save'))->bind('admin-products-save');
$app->get('/admin/products/add/', action('App\Products\ProductsController::add'))->bind('admin-products-add');
$app->get('/admin/products/edit/{id}', action('App\Products\ProductsController::edit'))->bind('admin-products-edit');
$app->get('/admin/products/delete/{id}', action('App\Products\ProductsController::delete'))->bind('admin-products-delete');

// Categories
$app->get('/admin/categories/', action('App\Categories\CategoriesController::main'))->bind('admin-categories-main');
$app->post('/admin/categories/save/{id}', action('App\Categories\CategoriesController::save'))->bind('admin-categories-save');
$app->get('/admin/categories/add/', action('App\Categories\CategoriesController::add'))->bind('admin-categories-add');
$app->get('/admin/categories/edit/{id}', action('App\Categories\CategoriesController::edit'))->bind('admin-categories-edit');
$app->get('/admin/categories/delete/{id}', action('App\Categories\CategoriesController::delete'))->bind('admin-categories-delete');
