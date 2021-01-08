<?php
namespace Fraud\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;

class FraudController extends AbstractActionController
{
    private function getDatabase(){
        $link = \mssql_connect('sugoServer', 'admin', 'A8WYS9q2*z');
        return $link;

    }
    public function indexAction()
    {
        return new ViewModel();
    }

    public function creditCardAction()
    {
        $db = $this->getDatabase();

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