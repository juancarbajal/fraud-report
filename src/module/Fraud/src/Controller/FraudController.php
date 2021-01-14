<?php
namespace Fraud\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

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
            return new ViewModel(['data' => $this->_document($from, $to),
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
    private function _sortQueryCalculate($sortField, &$sortDirection){
        if (!isset($sortDirection) && !isset($sortField)){
            //Orden por defecto: Primera vez ordenado por fecha asc
            $sortQuery = 'order by creationdate asc';
            $sortDirection = 'desc'; //siguiente ordenamiento 
        } else {
            if ($sortField == 'date'){
                $sortQuery = ($sortDirection == 'desc')? 'order by creationdate desc': 'order by creationdate asc';
            } else {
                $sortQuery = ($sortDirection == 'desc')? 'order by email desc': 'order by email asc';
            }
            $sortDirection = ($sortDirection == 'asc')?'desc':'asc';
        }
        return $sortQuery;
    }
    public function creditCardDetailAction() {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $card = explode('-', $_REQUEST['card']);
        $sortField = $_REQUEST['sf'];
        $sortDirection = $_REQUEST['sd'];
        $extras = $this->_sortQueryCalculate($sortField, $sortDirection);
        
        $data = $this->_creditCardDetail($from, $to, $card, $extras);
        return new ViewModel(['data' => $data, 
        'card' => $card,
        'from' => $from,
        'to' => $to,
        'sortDirection' => $sortDirection]
        );
    }
    private function _creditCardDetail($from, $to, $card, $extras= ''){
        $sql = "select 
        creationdate
        , orderid
        , email
        , clientefirstname
        , clientelastname
        , totalvalue
        , count(skuquantity) as cantsku
        , sum(skuquantity) as totalsku 
        , concat(street, ' ', number) as address 
        from ordenes 
        where creationdate BETWEEN '$from' and '$to'
        and cardfirstdigits = '$card[0]' and lastdigits = '$card[1]'
        and status='Preparando Entrega'
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue, concat(street, ' ', number) " . $extras;
        
        return $this->executeQuery($sql);
    }

    public function documentDetailAction() {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $document = $_REQUEST['document'];

        $sortField = $_REQUEST['sf'];
        $sortDirection = $_REQUEST['sd'];
        $extras = $this->_sortQueryCalculate($sortField, $sortDirection);

        return new ViewModel(['data' => $this->_documentDetail($from, $to, $document, $extras), 
        'document' => $document,
        'from' => $from,
        'to' => $to,
        'sortDirection' => $sortDirection]);
    }
    private function _documentDetail($from, $to, $document, $extras= ''){
        $sql = "select 
        creationdate
        , orderid
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
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue " . $extras;
        return $this->executeQuery($sql);
    }
    public function phoneDetailAction() {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $phone = $_REQUEST['phone'];
        $sortField = $_REQUEST['sf'];
        $sortDirection = $_REQUEST['sd'];
        $extras = $this->_sortQueryCalculate($sortField, $sortDirection);
        
        return new ViewModel(['data' => $this->_phoneDetail($from, $to, $phone, $extras), 
        'phone' => $phone,
        'from' => $from,
        'to' => $to,
        'sortDirection' => $sortDirection]);
    }
    private function _phoneDetail($from, $to, $phone , $extras= ''){
        $sql = "select 
        creationdate
        , orderid
        , email
        , clientefirstname
        , clientelastname
        , totalvalue
        , count(skuquantity) as cantsku
        , sum(skuquantity) as totalsku
        , substring(concat(street, ' ', number),1,30) as address 
        from ordenes 
        where creationdate BETWEEN '$from' and '$to'
        and phone like '%$phone'
        and status='Preparando Entrega'
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue , substring(concat(street, ' ', number),1,30) ". $extras;
        return $this->executeQuery($sql);
    }
    public function addressDetailAction() {
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $address = $_REQUEST['address'];
        $sortField = $_REQUEST['sf'];
        $sortDirection = $_REQUEST['sd'];
        $extras = $this->_sortQueryCalculate($sortField, $sortDirection);

        return new ViewModel(['data' => $this->_addressDetail($from, $to, $address, $extras), 
        'address' => $address,
        'from' => $from,
        'to' => $to,
        'sortDirection' => $sortDirection]);
    }
    private function _addressDetail($form, $to, $address, $extras= '') {
        $sql = "select 
        creationdate
        , orderid
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
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue " . $extras;
        return $this->executeQuery($sql);
    }
    private function _dataToExcel(&$sheet, $data, $header){
        $letters = array(0=>'A', 1=>'B', 2=>'C', 3=>'D', 4=>'E', 5=>'F', 6=>'G', 7=>'H', 8=>'I', 9=>'J', 10 =>'K');
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
            $extension = '.xls';
            $filename = 'export' . $extension;
            $detailHeader = array('Fecha' , 'Id Orden', 'Correo', 'Nombre', 'Apellido', 'Monto', 'Cant. SKUs', 'Total SKUs', 'Dirección');
            switch ($p){
                case 'credit-card': 
                    $data = $this->_creditCard($from, $to);
                    $this->_dataToExcel($sheet, $data, array('Número de Tarjeta', 'Tipo de Tarjeta', 'Usuarios únicos'));
                    $filename = 'credit_card_' . $from . '_' . $to . $extension;
                    break;
                case 'document':
                    $data = $this->_document($from, $to);
                    $this->_dataToExcel($sheet, $data, array('Documento de identidad', 'Usuarios únicos'));
                    $filename = 'document_' . $from . '_' . $to . $extension;
                    break;
                case 'phone':
                        $data = $this->_phone($from, $to);
                        $this->_dataToExcel($sheet, $data, array('Télefono' , 'Usuarios únicos'));
                        $filename = 'phone_' . $from . '_' . $to . $extension;
                    break;
                case 'address':
                        $data = $this->_address($from, $to);
                        $this->_dataToExcel($sheet, $data, array('Dirección' , 'Usuarios únicos'));
                        $filename = 'address_' . $from . '_' . $to . $extension;
                    break;
                case 'credit-card-detail':
                        $data = $this->_creditCardDetail($from, $to, explode('-', $_REQUEST['card']));
                        $this->_dataToExcel($sheet, $data, $detailHeader);
                        $filename = 'credit_card_' . $from . '_' . $to . '_' . $_REQUEST['card'] . $extension;
                    break;
                case 'document-detail':
                        $data = $this->_documentDetail($from, $to, $_REQUEST['document']);
                        $this->_dataToExcel($sheet, $data, $detailHeader);
                        $filename = 'document_' . $from . '_' . $to . '_' . $_REQUEST['document'] . $extension;
                    break;
                case 'phone-detail':
                        $data = $this->_phoneDetail($from, $to, $_REQUEST['phone']);
                        $this->_dataToExcel($sheet, $data, $detailHeader);
                        $filename = 'phone_' . $from . '_' . $to . '_' . $_REQUEST['phone'] . $extension;
                    break;
                case 'address-detail':
                        $data = $this->_addressDetail($from, $to, $_REQUEST['address']);
                        $this->_dataToExcel($sheet, $data, $detailHeader);
                        $filename = 'address_' . $from . '_' . $to . '_' . md5($_REQUEST['address']) . $extension;
                    break;
            }
            $writer = new Xls($spreadsheet);
            //$writer->save('hello world.xlsx');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="'. $filename .'"');
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