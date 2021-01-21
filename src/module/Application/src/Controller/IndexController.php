<?php

/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }
    public function loginAction(){
        $user = $_REQUEST['user'];
        $pass = $_REQUEST['pass'];
        if (isset($user) && isset($pass)){

            $dbSecurity = new \SQLite3(APPLICATION_PATH . '/data/security.db3');
            $data = $dbSecurity->query("select * from users where user = '$user'");
            if (($data === true) || ($data === false)) {
                //No hay usuarios  con ese nombre de usuario
                $this->redirect()->toUrl('/');
            } else {
                $row = $data->fetchArray();
                //var_dump($row);
                if (md5($pass) == $row['password'] ){
                    session_start();
                    $_SESSION['user'] = $user;
                    $this->redirect()->toUrl('/fraud');
                } else {
                    $this->redirect()->toUrl('/');
                }
            }
            /*
            if (($user == 'admin') && ($pass == '123')){
                session_start();
                $_SESSION['user'] = $user;
                $this->redirect()->toUrl('/fraud');
            }
            else 
                $this->redirect()->toUrl('/');*/
        } else $this->redirect()->toUrl('/');
        
    }

    public function logoutAction(){
        //session_unset();
        $_SESSION['user'] = '';
        $_SESSION['user'] = null;
        //session_destroy();

        $this->redirect()->toUrl('/');
    }
}
