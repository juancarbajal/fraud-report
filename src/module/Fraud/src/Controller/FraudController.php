<?php
namespace Fraud\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;

class FraudController extends AbstractActionController
{
    private function getDatabase(){
        //$link = \mssql_connect('sugoServer', 'admin', 'A8WYS9q2*z');
        $serverName = "prod-sugo-apps.cjefewagaayr.us-east-1.rds.amazonaws.com";
        $connectionOptions = array(
            "Database" => "db-sugo-vtext-01",
            "Uid" => "admin",
            "PWD" => "A8WYS9q2*z"
        );
                
        $link = sqlsrv_connect($serverName,$connectionOptions);
            return $link;

    }

    private function executeQuery($query){
        $db = $this->getDatabase();
            
        if( $db === false ) {
            die( print_r( sqlsrv_errors(), true));
        }

        sqlsrv_begin_transaction($db);

        $stmt1 = sqlsrv_prepare($db, $sql1, array());
        if( !$stmt1 ) {
            die( print_r( sqlsrv_errors(), true));
        }
        $result1 = sqlsrv_execute($stmt1);
        
        if( $result1 === false ) {
            die( print_r( sqlsrv_errors(), true));
        }
        $data = [];
        while($row = sqlsrv_fetch_object($stmt1)) {
            $data[] = $row;
        }
        return $data;
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function creditCardAction()
    {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        if (isset($from) && isset($to)){
            $sql1 = "select 
            paymentsystemname
            , cardfirstdigits
            , lastdigits
            , count(1) as cnt
            from 
            (
                select 
                distinct(email)
                , paymentsystemname
                , cardfirstdigits
                , lastdigits
                from ordenes
                where creationdate BETWEEN '$from' and '$to'
            ) as m 
            group by paymentsystemname
            , cardfirstdigits
            , lastdigits
            having count(1)>1;";

            return new ViewModel(['data' => $this->executeQuery($sql1)]);
 
        }
        else 
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