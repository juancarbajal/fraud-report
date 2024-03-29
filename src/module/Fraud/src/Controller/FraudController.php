<?php
namespace Fraud\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Db\Adapter\Adapter;
use Laminas\Config\Config;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class FraudController extends AbstractActionController
{
    function _validateAccess(){
        if (isset($_SESSION['user']) && ($_SESSION['user']!='')){
            return true; 
        }
        else {
            $this->redirect()->toUrl('/');
            return false;
        }
    }
    private function getDatabase(){
        
        $config = new Config(include APPLICATION_PATH . '/config/fraud.config.php');
        $serverName = $config->fraud->database->host;
        $connectionOptions = array(
            "Database" => $config->fraud->database->dbname,
            "Uid" => $config->fraud->database->user,
            "PWD" => $config->fraud->database->pass,
            "CharacterSet" => "UTF-8"
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
        $this->_validateAccess();
        return new ViewModel();
    }
    private function _calculateLegend(&$data){
        $legend = array();
        foreach($data as $i => $row){
            $data[$i]->nro = $i+1;
            $id = $row->cnt;
            $legend["$id"]['cnt'] = (!isset($legend["$id"]['cnt']))?1:$legend["$id"]['cnt']+1; 
            if (!isset($legend["$id"]['total']))
                $legend["$id"]['total'] = $row->total;
            else 
                $legend["$id"]['total'] += $row->total; 
        }
        //arsort($legend);
        return $legend;
    }
    public function creditCardAction()
    {
        $this->_validateAccess();
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $repeat = (int)$_REQUEST['repeat'];
        $data = $this->_creditCard($from, $to, $repeat);
        $legend = $this->_calculateLegend($data);
        if (isset($from) && isset($to)){
            return new ViewModel(['data' => $data,
            'from' => $from,
            'to' => $to,
            'repeat' => $repeat,
            'legend' => $legend ]);
        }
        else 
            return new ViewModel();
    }
    private function _creditCard($from, $to, $repeat=2){
        $sql1 = "select 
            concat(cardfirstdigits, '-', lastdigits) as card
            , paymentsystemname
            , sum(totalvalue) as total
            , count(1) as cnt
            from 
            (
                select 
                distinct(email)
                , paymentsystemname
                , cardfirstdigits
                , lastdigits
                , totalvalue
                from ordenes
                where creationdate BETWEEN '$from' and '$to'
                and charindex('handling',status)>0
            ) as m 
            group by paymentsystemname
            , cardfirstdigits
            , lastdigits
            having count(1)>=$repeat
            order by cnt desc;";
        return $this->executeQuery($sql1);
    }
    public function documentAction()
    {
        $this->_validateAccess();
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $repeat = (int)$_REQUEST['repeat'];
        if (isset($from) && isset($to)){
            $data = $this->_document($from, $to, $repeat);
            $legend = $this->_calculateLegend($data);

            return new ViewModel(['data' => $data,
            'from' => $from,
            'to' => $to,
            'repeat' => $repeat,
            'legend' => $legend]);
 
        }
        else 
            return new ViewModel();
    }
    private function  _document($from, $to, $repeat=2){
        $sql1 = "select 
            clientedocument
            , sum(totalvalue) as total
            , count(1) as cnt
            from 
            (
                select 
                distinct(email)
                , clientedocument
                , totalvalue
                from ordenes
                where creationdate BETWEEN '$from' and '$to'
                and charindex('handling',status)>0
            ) as m 
            group by clientedocument
            having count(1)>=$repeat
            order by cnt desc;";
        return $this->executeQuery($sql1);
    }
    public function phoneAction()
    {
        $this->_validateAccess();
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $repeat = (int)$_REQUEST['repeat'];
        if (isset($from) && isset($to)){
            $data = $this->_phone($from, $to, $repeat);
            $legend = $this->_calculateLegend($data);

            return new ViewModel(['data' => $data,
            'from' => $from,
            'to' => $to,
            'repeat' => $repeat, 
            'legend' => $legend]);
 
        }
        else 
            return new ViewModel();
    }

    private function _phone($from, $to, $repeat=2){
        $sql1 = "select 
            phone
            , sum(totalvalue) as total
            , count(1) as cnt
            from 
            (
                select 
                distinct(email)
                , replace(phone, '+51', '') as phone
                , totalvalue
                from ordenes
                where creationdate BETWEEN '$from' and '$to'
                and charindex('handling',status)>0
            ) as m 
            group by phone
            having count(1)>=$repeat
            order by cnt desc;";
        return $this->executeQuery($sql1);
    }
    public function addressAction()
    {   
        $this->_validateAccess();
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $repeat = (int)$_REQUEST['repeat'];
        if (isset($from) && isset($to)){
            $data = $this->_address($from, $to, $repeat);
            $legend = $this->_calculateLegend($data);
            return new ViewModel(['data' => $data,
            'from' => $from,
            'to' => $to,
            'repeat' => $repeat,
            'legend' => $legend]);
 
        }
        else 
            return new ViewModel();
    }

    private function _address($from, $to, $repeat=2){
        $sql1 = "select 
        street_total
        , sum(totalvalue) as total
        , count(1) as cnt
        from 
        (
            select 
            distinct(email)
            , addresstype
            , concat(city, ', ', street, ' ', number) as street_total
            , totalvalue
            from ordenes
            where creationdate BETWEEN '$from' and '$to'
            and charindex('handling',status)>0
        ) as m 
        group by street_total
        having count(1)>=$repeat
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
    private function _detailCalculateLegend($data){
        $total = ['total' => 0, 'cantsku' => 0, 'totalsku' => 0];
        foreach($data as $row){
            $total['total']+=$row->totalvalue;
            $total['cantsku']+=$row->cantsku;
            $total['totalsku']+=$row->totalsku;
        }
        return $total;
    }
    public function creditCardDetailAction() {
        $this->_validateAccess();
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
        'legend' => $this->_detailCalculateLegend($data),
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
        and charindex('handling',status)>0
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue, concat(street, ' ', number) " . $extras;
        
        return $this->executeQuery($sql);
    }

    public function documentDetailAction() {
        $this->_validateAccess();
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $document = $_REQUEST['document'];

        $sortField = $_REQUEST['sf'];
        $sortDirection = $_REQUEST['sd'];
        $extras = $this->_sortQueryCalculate($sortField, $sortDirection);
        $data = $this->_documentDetail($from, $to, $document, $extras);

        return new ViewModel(['data' => $data, 
        'document' => $document,
        'from' => $from,
        'to' => $to,
        'legend' => $this->_detailCalculateLegend($data),
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
        , concat(street, ' ', number) as address 
        from ordenes 
        where creationdate BETWEEN '$from' and '$to'
        and clientedocument = '$document'
        and charindex('handling',status)>0
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue, concat(street, ' ', number) " . $extras;
        return $this->executeQuery($sql);
    }
    public function phoneDetailAction() {
        $this->_validateAccess();
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $phone = $_REQUEST['phone'];
        $sortField = $_REQUEST['sf'];
        $sortDirection = $_REQUEST['sd'];
        $extras = $this->_sortQueryCalculate($sortField, $sortDirection);
        $data = $this->_phoneDetail($from, $to, $phone, $extras);

        return new ViewModel(['data' => $data, 
        'phone' => $phone,
        'from' => $from,
        'to' => $to,
        'legend' => $this->_detailCalculateLegend($data),
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
        , concat(street, ' ', number) as address
        from ordenes 
        where creationdate BETWEEN '$from' and '$to'
        and phone like '%$phone'
        and charindex('handling',status)>0
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue, concat(street, ' ', number) ". $extras;
        return $this->executeQuery($sql);
    }
    public function addressDetailAction() {
        $this->_validateAccess();
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $address = $_REQUEST['address'];
        $sortField = $_REQUEST['sf'];
        $sortDirection = $_REQUEST['sd'];
        $extras = $this->_sortQueryCalculate($sortField, $sortDirection);
        $data = $this->_addressDetail($from, $to, $address, $extras);

        return new ViewModel(['data' => $data, 
        'address' => $address,
        'from' => $from,
        'to' => $to,
        'legend' => $this->_detailCalculateLegend($data),
        'sortDirection' => $sortDirection]);
    }
    private function _addressDetail($from, $to, $address, $extras= '') {
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
        and charindex('handling',status)>0
        group by orderid, creationdate, email, clientefirstname, clientelastname, totalvalue " . $extras;
        return $this->executeQuery($sql);
    }
    private function _dataToExcel(&$sheet, $data, $header, $options = array()){
        $letters = array(0=>'A', 1=>'B', 2=>'C', 3=>'D', 4=>'E', 5=>'F', 6=>'G', 7=>'H', 8=>'I', 9=>'J', 10 =>'K');
        foreach ($header as $i => $h){
            $sheet->setCellValue($letters[$i] . '1', $h);
        }
        foreach ($data as $i => $row){
            $keys = array_keys(get_object_vars($row));
            if ($options['number'] == true){
                array_pop($keys);
                array_unshift($keys, 'nro');
            } 
            foreach ($keys as $j => $k){
                $sheet->setCellValue($letters[$j] . ($i+2), $row->$k);
            }
        }
        //return $sheet;
    }
    public function exportExcelAction(){
        $this->_validateAccess();
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $repeat = $_REQUEST['repeat'];
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
                    $data = $this->_creditCard($from, $to, $repeat);
                    $this->_calculateLegend($data);
                    $this->_dataToExcel($sheet, $data, array('#', 'Número de Tarjeta', 'Tipo de Tarjeta', 'Usuarios únicos'), array('number' => true));
                    $filename = 'credit_card_' . $from . '_' . $to . $extension;
                    break;
                case 'document':
                    $data = $this->_document($from, $to, $repeat);
                    $this->_calculateLegend($data);
                    $this->_dataToExcel($sheet, $data, array('#', 'Documento de identidad', 'Usuarios únicos'), array('number' => true));
                    $filename = 'document_' . $from . '_' . $to . $extension;
                    break;
                case 'phone':
                        $data = $this->_phone($from, $to, $repeat);
                        $this->_calculateLegend($data);
                        $this->_dataToExcel($sheet, $data, array('#', 'Télefono' , 'Usuarios únicos'), array('number' => true));
                        $filename = 'phone_' . $from . '_' . $to . $extension;
                    break;
                case 'address':
                        $data = $this->_address($from, $to, $repeat);
                        $this->_calculateLegend($data);
                        $this->_dataToExcel($sheet, $data, array('#', 'Dirección' , 'Usuarios únicos'), array('number' => true));
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
                        //array_pop($detailHeader);
                        $data = $this->_phoneDetail($from, $to, $_REQUEST['phone']);
                        $this->_dataToExcel($sheet, $data, $detailHeader);
                        $filename = 'phone_' . $from . '_' . $to . '_' . $_REQUEST['phone'] . $extension;
                    break;
                case 'address-detail':
                        array_pop($detailHeader);
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