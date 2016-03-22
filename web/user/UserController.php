<?php
namespace web\user;

use Silex\Application;
use Silex\ControllerProviderInterface;





class UserController implements ControllerProviderInterface
{
    public function ShowUser (Request $request) {
        
        $sql = "SELECT * FROM users WHERE id = ?";
        $post = $this['db']->fetchAssoc($sql, array((int) $id));


        return 'testy';
    }
    
    public function CreateUser () {
        return 'user dodany';
    }
    // public function ShowUser(Request $request, Application $app)
    // {
    //     $sql = "SELECT * FROM users WHERE id = ?";
    //     $post = $app['db']->fetchAssoc($sql, array((int) $id));

    //     return $app->json($post, 201);
    // }
}
?>