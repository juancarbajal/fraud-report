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

        $stmt1 = sqlsrv_prepare($db, $query, array());
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
        sqlsrv_close($db);
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

            return new ViewModel(['data' => $this->executeQuery($sql1),
            'from' => $from,
            'to' => $to]);
 
        }
        else 
            return new ViewModel();
    }

    public function documentAction()
    {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        if (isset($from) && isset($to)){
            $sql1 = "select 
            clientedocument
            , count(1) as cnt
            from 
            (
                select 
                distinct(email)
                , clientedocument
                from ordenes
                where creationdate BETWEEN '$from' and '$to'
            ) as m 
            group by clientedocument
            having count(1)>1;";

            return new ViewModel(['data' => $this->executeQuery($sql1),
            'from' => $from,
            'to' => $to]);
 
        }
        else 
            return new ViewModel();
    }

    public function phoneAction()
    {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        if (isset($from) && isset($to)){
            $sql1 = "select 
            phone
            , count(1) as cnt
            from 
            (
                select 
                distinct(email)
                , replace(phone, '+51', '') as phone
                from ordenes
                where creationdate BETWEEN '$from' and '$to'
            ) as m 
            group by phone
            having count(1)>1;";

            return new ViewModel(['data' => $this->executeQuery($sql1),
            'from' => $from,
            'to' => $to]);
 
        }
        else 
            return new ViewModel();
    }

    public function addressAction()
    {   
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        if (isset($from) && isset($to)){
            $sql1 = "select 
            street_total
            , count(1) as cnt
            from 
            (
                select 
                distinct(email)
                , addresstype
                , concat(city, ', ', street, ' ', number) as street_total
                , number
                from ordenes
                where creationdate BETWEEN '$from' and '$to'
            ) as m 
            group by street_total
            having count(1)>1;";

            return new ViewModel(['data' => $this->executeQuery($sql1),
            'from' => $from,
            'to' => $to]);
 
        }
        else 
            return new ViewModel();
    }

    //Detalle
    public function creditCardDetailAction() {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $card = explode('-', $_REQUEST['card']);
        $sql = "select 
        orderid
        , creationdate
        , email
        , clientefirstname
        , clientelastname
        , totalvalue
        , count(skuquantity) as cantsku
        , sum(skuquantity) as totalsku 
        from ordenes 
        where creationdate BETWEEN '$from' and '$to'
        and cardfirstdigits = '$card[0]' and lastdigits = '$card[1]'
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue ";
        $data = $this->executeQuery($sql);
        return new ViewModel(['data' => $data, 
        'card' => $card]);
    }
    public function documentDetailAction() {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $document = $_REQUEST['document'];
        $sql = "select 
        orderid
        , creationdate
        , email
        , clientefirstname
        , clientelastname
        , totalvalue
        , count(skuquantity) as cantsku
        , sum(skuquantity) as totalsku 
        from ordenes 
        where creationdate BETWEEN '$from' and '$to'
        and clientedocument = '$document'
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue ";
        $data = $this->executeQuery($sql);
        return new ViewModel(['data' => $data, 
        'document' => $document]);
    }

    public function phoneDetailAction() {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $document = $_REQUEST['phone'];
        $sql = "select 
        orderid
        , creationdate
        , email
        , clientefirstname
        , clientelastname
        , totalvalue
        , count(skuquantity) as cantsku
        , sum(skuquantity) as totalsku 
        from ordenes 
        where creationdate BETWEEN '$from' and '$to'
        and phone like '%$phone'
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue ";
        $data = $this->executeQuery($sql);
        return new ViewModel(['data' => $data, 
        'document' => $phone]);
    }

    public function addressDetailAction() {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $address = $_REQUEST['address'];
        $sql = "select 
        orderid
        , creationdate
        , email
        , clientefirstname
        , clientelastname
        , totalvalue
        , count(skuquantity) as cantsku
        , sum(skuquantity) as totalsku 
        from ordenes 
        where creationdate BETWEEN '$from' and '$to'
        and concat(city, ', ', street, ' ', number) = '$address'
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue ";
        $data = $this->executeQuery($sql);
        return new ViewModel(['data' => $data, 
        'address' => $address]);
    }
}