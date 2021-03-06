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
//funkcja pobierania artykułów na główną, działa i nie ruszać
$app->get('/', function () use ($app) {
    $sql = "SELECT * FROM frontpage";
    $post = $app['db']->fetchAll($sql);

    return $app->json($post, 201);
});

// funkcja logowania i autoryzacji, dane autoryzujące pobierane z servera, porównanie z danymi z bazy, 
// jeśli zgadza się to przekierowanie na widok usera, jesli nie to przekierowanie do strony logowania
// chyba działa ale trzeba testować, dane podaje się w body/form-data
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
                // return $app->json(200);
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

//funkcja dodawania nowego użytkownika
$app->post('/user', function (Request $request ) use ($app) {
    
    $data_validation=array(
        'username'=>htmlentities($request->get('login')),
        'password'=>htmlentities($request->get('password')),
        'email'=>htmlentities($request->get('email')),
    );

    
    $constraint = new Assert\Collection(array(
        'username' => new Assert\Length(array('min' =>6, 'max' => 16)),
        'username' => new Assert\NotNull(),
        'username' => new Assert\NotBlank(),
        'password' => new Assert\Length(array('min' =>8, 'max' => 20)),
        'password' => new Assert\NotNull(),
        'password' => new Assert\NotBlank(),
        'email' => new Assert\Email(),
    ));    
    
    $errors = $app['validator']->validate($data_validation, $constraint);
    if (count($errors)===0) {
        $hash=password_hash($request->get('password'), PASSWORD_BCRYPT);
        
        $user = array(
            'login'=>$data_validation['username'],
            'password'=>$hash,
            'email'=>$data_validation['email'],
        );
        
        $app['db']->insert('users',$user);
        
        return $app->json($user, 201);
    }
    
    $response = new Response();
    $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', 'Bledne dane formularza'));
    $response->setStatusCode(406, 'Nie kombinuj z danymi');
    return $response;
});

//pobieranie danych o użytkowniku z {id} warunkiem musi być zalogowanie 
$app->get('/user/{id}', function ($id) use ($app) {
    
    if (null === $user = $app['session']->get('user')) {
        // return $app->redirect('/api/web/index.php/login');
<<<<<<< HEAD
        
=======
>>>>>>> origin/master
        $response = new Response();
        $response->setStatusCode(401, 'Please sign in.');
        return $response;
    }
    if (is_numeric($id)) {
        $sql = "SELECT id,login,email,active_lesson FROM users WHERE id = ?";
        $post = $app['db']->fetchAssoc($sql, array((int) $id));
        return $app->json($post, 201);
    }
    $response = new Response();
    $response->setStatusCode(406, 'Nie kombinuj z danymi');
    return $response;
});

//uaktualnienia danych użytkownika dane przesyłane PUT, warunke użytkownik musi być zalogowany
$app->put('/user/{id}', function (Request $request ) use ($app) {

    if (null === $user = $app['session']->get('user')) {
        // return $app->redirect('/api/web/index.php/login');
        $response = new Response();
        $response->setStatusCode(401, 'Please sign in.');
        return $response;
    }
    
    $data_validation=array(
        'username'=>htmlentities($request->get('login')),
        'password'=>htmlentities($request->get('password')),
        'email'=>htmlentities($request->get('email')),
    );
    $constraint = new Assert\Collection(array(
        'username' => new Assert\Length(array('min' =>6, 'max' => 16)),
        'username' => new Assert\NotNull(),
        'username' => new Assert\NotBlank(),
        'password' => new Assert\Length(array('min' =>8, 'max' => 20)),
        'password' => new Assert\NotNull(),
        'password' => new Assert\NotBlank(),
        'email' => new Assert\Email(),
    ));    
    
    $errors = $app['validator']->validate($data_validation, $constraint);
    if (count($errors)===0) {
        $user = array(
            'login'=>$request->get('login'),
            'password'=>$request->get('password'),
            'email'=>$request->get('email')
        );

        return $app->json($user, 201);
    }
});

//usuwanie uzytkownika z bazy danych, warunek musi być zalogowany, do przemyslenia
$app->delete('/user/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        // return $app->redirect('/api/web/index.php/login');
        $response = new Response();
        $response->setStatusCode(401, 'Please sign in.');
        return $response;
    }
});

//zwraca wszelkie dostepne kursy, dostepne dla wszystkich, działa, nie ruszać
$app->get('courses', function () use ($app) {
    $sql = "SELECT
  id_cours,name,img,description,
  COUNT(id_course) AS count_lesson
FROM
course
  LEFT JOIN
    lesson ON (
      course.id_cours=lesson.id_course
    )
GROUP BY
  name";
    $post = $app['db']->fetchAll($sql);

    return $app->json($post, 201);
});

//zwraca wszelkie dostepne lekcje w danym kursie dostepne dla wszystkich i już działa, nie ruszać 
$app->get('course/{id}', function ($id) use ($app) {
    $sql = "select * from lesson where id_course= ?";
    $post = $app['db']->fetchAll($sql, array((int) $id));

    return $app->json($post, 201);
});

<<<<<<< HEAD
//zwraca lekcje z konkretnego kursu nie działa, coś jest nie tak :(
$app->get('course/{id_course}/lesson/{id_lesson}', function ($id_course, $id_lesson) use ($app) {
    $sql = "select * from lesson where id_course='".$id_course+"' and id_lesson='".$id_lesson."'";
    //return $id['id_lesson'];
=======
//zwraca lekcje z konkretnego kursu
$app->get('course/{courseID}/{lessonID}', function ($courseID, $lessonID) use ($app) {
    
    
    
    $sql = "select * from lesson where id_course='".$courseID."' and id='".$lessonID."'";
>>>>>>> origin/master
    $post = $app['db']->fetchAssoc($sql);

    return $app->json($post, 201);
});


$app->run();
