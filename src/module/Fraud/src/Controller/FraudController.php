<?php
namespace Fraud\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

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

        $serverName = "prod-sugo-apps2.cjefewagaayr.us-east-1.rds.amazonaws.com";
        $connectionOptions = array(
            "Database" => "db-sugo-vtext-02",
            "Uid" => "admin",
            "PWD" => "y8WgS7q2*z"
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
            return new ViewModel(['data' => $this->_creditCard($from, $to),
            'from' => $from,
            'to' => $to]);
 
        }
        else 
            return new ViewModel();
    }
    private function _creditCard($from, $to){
        $sql1 = "select 
            concat(cardfirstdigits, '-', lastdigits) as card
            , paymentsystemname
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
                and status='Preparando Entrega'
            ) as m 
            group by paymentsystemname
            , cardfirstdigits
            , lastdigits
            having count(1)>1
            order by cnt desc;";
        return $this->executeQuery($sql1);
    }
    public function documentAction()
    {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        if (isset($from) && isset($to)){
            return new ViewModel(['data' => $this->_document($from,$to),
            'from' => $from,
            'to' => $to]);
 
        }
        else 
            return new ViewModel();
    }
    private function  _document($from, $to){
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
                and status='Preparando Entrega'
            ) as m 
            group by clientedocument
            having count(1)>1
            order by cnt desc;";
        return $this->executeQuery($sql1);
    }
    public function phoneAction()
    {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        if (isset($from) && isset($to)){
            return new ViewModel(['data' => $this->_phone($from, $to),
            'from' => $from,
            'to' => $to]);
 
        }
        else 
            return new ViewModel();
    }

    private function _phone($from, $to){
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
                and status='Preparando Entrega'
            ) as m 
            group by phone
            having count(1)>1
            order by cnt desc;";
        return $this->executeQuery($sql1);
    }
    public function addressAction()
    {   
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        if (isset($from) && isset($to)){
            return new ViewModel(['data' => $this->_address($from, $to),
            'from' => $from,
            'to' => $to]);
 
        }
        else 
            return new ViewModel();
    }

    private function _address($from, $to){
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
            and status='Preparando Entrega'
        ) as m 
        group by street_total
        having count(1)>1
        order by cnt desc;";
        return $this->executeQuery($sql1);
    }
    //Detalle
    public function creditCardDetailAction() {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $card = explode('-', $_REQUEST['card']);
        
        $data = $this->_creditCardDetail($from, $to, $card);
        return new ViewModel(['data' => $data, 
        'card' => $card,
        'from' => $from,
        'to' => $to]
        );
    }
    private function _creditCardDetail($from, $to, $card){
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
        and status='Preparando Entrega'
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue ";
        return $this->executeQuery($sql);
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
        and status='Preparando Entrega'
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue ";
        $data = $this->executeQuery($sql);
        return new ViewModel(['data' => $data, 
        'document' => $document]);
    }

    public function phoneDetailAction() {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $phone = $_REQUEST['phone'];
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
        and status='Preparando Entrega'
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue ";
        $data = $this->executeQuery($sql);
        return new ViewModel(['data' => $data, 
        'phone' => $phone]);
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
        and status='Preparando Entrega'
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue ";
        $data = $this->executeQuery($sql);
        return new ViewModel(['data' => $data, 
        'address' => $address]);
    }
    private function _dataToExcel(&$sheet, $data, $header){
        $letters = array(0=>'A', 1=>'B', 2=>'C', 3=>'D', 4=>'E', 5=>'F', 6=>'H', 7=>'I', 8=>'J');
        foreach ($header as $i => $h){
            $sheet->setCellValue($letters[$i] . '1', $h);
        }
        foreach ($data as $i => $row){
            $keys = array_keys(get_object_vars($row));
            foreach ($keys as $j => $k){
                $sheet->setCellValue($letters[$j] . ($i+2), $row->$k);
            }
        }
        //return $sheet;
    }
    public function exportExcelAction(){

        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $p = $_REQUEST['p'];
        //if (isset($from) && isset($to) && isset($to)){
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            //$sheet->setCellValue("A1", 'Hola mundo');
            
            switch ($p){
                case 'credit-card': 
                    $data = $this->_creditCard($from, $to);
                    $this->_dataToExcel($sheet, $data, array('Número de Tarjeta', 'Tipo de Tarjeta', 'Usuarios únicos'));
                    break;
                case 'document':
                    $data = $this->_document($from, $to);
                    $this->_dataToExcel($sheet, $data, array('Documento de identidad', 'Usuarios únicos'));
                    break;
                case 'phone':
                        $data = $this->_phone($from, $to);
                        $this->_dataToExcel($sheet, $data, array('Télefono' , 'Usuarios únicos'));
                    break;
                case 'address':
                        $data = $this->_address($from, $to);
                        $this->_dataToExcel($sheet, $data, array('Dirección' , 'Usuarios únicos'));
                    break;
                case 'credit-card-detail':
                        $data = $this->_creditCardDetail($from, $to, explode('-', $_REQUEST['card']));
                        $this->_dataToExcel($sheet, $data, array('Fecha' , 'Id Orden', 'Correo', 'Nombre', 'Apellido', 'Monto', 'Cant. SKUs', 'Total SKUs'));
                    break;
            }
            $writer = new Csv($spreadsheet);
            //$writer->save('hello world.xlsx');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="'. urlencode($fileName).'.csv"');
            $writer->save('php://output');
            exit;
        //}
        
        /*$spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Hello World !');

        $writer = new Xlsx($spreadsheet);
        //$writer->save('hello world.xlsx');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($fileName).'"');
        $writer->save('php://output');
        */
    }
}