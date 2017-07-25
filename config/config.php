<?php
// ini_set('display_errors', 0);

// CMS settings
$app['cms.title'] = 'Nyilvántartó rendszer';
$app['cms.theme'] = '/Themes/Default';

// Debug mod
$app['debug'] = true;

// MySQL connection settings
$app['db.options'] = array(
    'driver'   => 'pdo_mysql',
    'host'     => 'localhost',
    'dbname'   => 'silextest',
    'user'     => 'root',
    'password' => '',
);

// User password generator, just for testing
// echo $app['security.encoder.bcrypt']->encodePassword('password', '');