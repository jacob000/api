<?php
require_once __DIR__.'/../vendor/autoload.php';

use Silex\Provider;
use Silex\Aplication;

$app = new Silex\Application();
$app [ 'debug'] = true;




$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'dbs.options' => array (
        'mysql_read' => array(
            'driver'    => 'pdo_mysql',
            'host'      => 'localhost',
            'dbname'    => 'inzynierka',
            'user'      => 'root',
            'password'  => 'kuba_pttk1',
            'charset'   => 'utf8mb4',
        ),
        'mysql_write' => array(
            'driver'    => 'pdo_mysql',
            'host'      => 'localhost',
            'dbname'    => 'inzynierka',
            'user'      => 'root',
            'password'  => 'kuba_pttk1',
            'charset'   => 'utf8mb4',
        ),
    ),
));
?>