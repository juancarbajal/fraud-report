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
            if (($user == 'admin') && ($pass == '123')){
                session_start();
                $_SESSION['user'] = $user;
                $this->redirect()->toUrl('/fraud');
            }
            else 
                $this->redirect()->toUrl('/');
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
