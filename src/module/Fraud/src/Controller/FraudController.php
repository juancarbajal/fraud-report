<?php
namespace Fraud\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;

class FraudController extends AbstractActionController
{
    private function getDatabase(){
        $adapter = new Laminas\Db\Adapter\Adapter([
            'driver'   => 'Mysqli',
            'database' => 'laminas_db_example',
            'username' => 'developer',
            'password' => 'developer-password',
        ]);
        return $adapter;
    }
    public function indexAction()
    {
        return new ViewModel();
    }

    public function creditCardAction()
    {
        return new ViewModel();
    }

    public function documentAction()
    {
        return new ViewModel();
    }

    public function phoneAction()
    {
        return new ViewModel();
    }

    public function addressAction()
    {
        return new ViewModel();
    }
}