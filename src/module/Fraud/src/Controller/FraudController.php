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
    public function indexAction()
    {
        return new ViewModel();
    }

    public function creditCardAction()
    {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        if (isset($from) && isset($to)){
            $sql = "select 
            paymentsystemname
            , cardfirstdigits
            , lastdigits
            , count(1)
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
            //$sql = "SELECT @@Version as SQL_VERSION";
            $sql1 = "select into ##tmpCreditCard 
            distinct(email) , 
            paymentsystemname , 
            cardfirstdigits , 
            lastdigits 
            from ordenes 
            where creationdate BETWEEN '2020-12-01' and '2020-12-30' ";
            $sql ="
            select into ##tmpCreditCard 
            distinct(email) , 
            paymentsystemname , 
            cardfirstdigits , 
            lastdigits 
            from ordenes 
            where creationdate BETWEEN '2020-12-01' and '2020-12-30';

            select 
            paymentsystemname
            , cardfirstdigits
            , lastdigits
            , count(1)
            from 
            ##tmpCreditCard as m 
            group by paymentsystemname
            , cardfirstdigits
            , lastdigits
            having count(1)>1;
            ";
            $db = $this->getDatabase();
            if( $db === false ) {
                die( print_r( sqlsrv_errors(), true));
            }
            //sqlsrv_query($db,$sql1);
            //print($sql);
            
            $stmt = sqlsrv_prepare($db, $sql);
            $result = sqlsrv_execute($stmt);
            while($row = sqlsrv_fetch_object($result)) {
                print_r($row);
            }
            /*$stmt = sqlsrv_query( $db, $sql);
            if( $stmt === false ) {
                die( print_r( sqlsrv_errors(), true));
            }
            while($row = sqlsrv_fetch_object($stmt)) {
                print_r($row);
            }*/

            //$data = sqlsrv_fetch_object($stmt);
            //var_dump($data);
            die;
        }
        return new ViewModel(['data' => $data]);
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