<?php

require_once __DIR__.'/../vendor/autoload.php';

use Silex\Provider;
use Silex\Aplication;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Validator\Constraints as Assert;

$app = new Silex\Application();
$app [ 'debug'] = true;

$adres="/api/web/index.php";

$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());

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

// $app->get('user/{id}', 'web\\user\\UserController::ShowUser');
// $app->post('user', 'web\\user\\UserController::CreateUser');



// funkcja logowania i autoryzacji, dane autoryzujące pobierane z servera, porównanie z danymi z bazy, 
// jeśli zgadza się to przekierowanie na widok usera, jesli nie to przekierowanie do strony logowania
$app->post('/login', function (Request $request) use ($app) {
    
    $user=array(
        'username'=>htmlentities($request->get('login')),
        'password'=>htmlentities($request->get('password')),
    );
    
    $constraint = new Assert\Collection(array(
        'username' => new Assert\Length(array('min' =>6, 'max' => 16)),
        'username' => new Assert\NotNull(),
        'username' => new Assert\NotBlank(),
        'password' => new Assert\Length(array('min' =>8, 'max' => 20)),
        'password' => new Assert\NotNull(),
        'password' => new Assert\NotBlank(),
    ));    
    
    $errors = $app['validator']->validate($user, $constraint);
    if (count($errors)===0) {
       
            $sql= "select * FROM users WHERE login='".$user['username']."'";
            $post = $app['db']->fetchAssoc($sql);
            
            if (password_verify($user['password'], $post['password'])) 
            {
                $app['session']->set('user', array('user_id' => $post['id'],
                                                'login' => $post['login'],
                                                'email' => $post['email']
                                                ));
                return $app->redirect('/api/web/index.php/user/'.$post['id']);
            }
            
            $response = new Response();
            $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', 'Blędne dane logowania'));
            $response->setStatusCode(401, 'Please sign in.');
            return $response;
    }
    $response = new Response();
    $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', 'brak danych w formularzu'));
    $response->setStatusCode(401, 'Please sign in.');
    return $response;
    
});

$app->get('/logout', function (Request $request) use ($app) {
    $app['session']->clear();
    
    // return $app->redirect('/api/web/index.php/login'); //nie można przekierować tak bo przekierowuje na get a nie post a logowanie wymaga post
    return $app->json( 201);
});

//pobieranie danych o użytkowniku z {id} warunkiem musi być zalogowanie 
$app->get('/user/{id}', function ($id) use ($app) {
    
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/api/web/index.php/login');
        
        // $response = new Response();
        // $response->setStatusCode(401, 'Please sign in.');
        // return $response;
    }
    
    $sql = "SELECT id,login,email,active_lesson FROM users WHERE id = ?";
    $post = $app['db']->fetchAssoc($sql, array((int) $id));

    return $app->json($post, 201);
});

//funkcja dodawania nowego użytkownika//
$app->post('/user', function (Request $request ) use ($app) {
    
    $hash=password_hash($request->get('password'), PASSWORD_BCRYPT);
    
    $user = array(
        'login'=>$request->get('login'),
        'password'=>$hash,
        'email'=>$request->get('email')
    );
    
    $app['db']->insert('users',$user);
    
    return $app->json($user, 201);
});

//uaktualnienia danych użytkownika dane przesyłane PUT, warunke użytkownik musi być zalogowany
$app->put('/user/{id}', function (Request $request ) use ($app) {

    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/api/web/index.php/login');
    }
    
    $user = array(
        'login'=>$request->get('name'),
        'password'=>$request->get('password'),
        'email'=>$request->get('email')
    );

    return $app->json($user, 201);
});

$app->run();
