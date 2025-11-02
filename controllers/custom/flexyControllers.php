<?php

if(isset($_POST['method']) && !empty($_POST['method'])){
	include_once 'config/DB.php';
	include_once 'includes/lang.php';
	global $db;
	$db = new DB();
	switch($_POST['method']){
		case 'get_user':
			get_user();
		break;
		case 'get_product':
			get_product();
		break;
		case 'postCodes':
			postCodes();
		break;
		case 'updateCodes':
			updateCodes();
		break;
		// case 'updatePayment':
        //     updatePayment();
		// break;
        case 'postPayment':
			postPayment();
		break;
        // case 'sendCodes':
		// 	sendCodes();
		// break;
        case 'postuser':
			postuser();
		break;
        case 'updateuser':
			updateuser();
		break;
        case 'get_card':
			get_card();
		break;
        case 'get_codes':
			get_codes();
		break;
        case 'state_operation':
			state_operation();
		break;
		case 'get_RDV':
			get_RDV();
		break;
        case 'postEvent':
			postEvent($db);
		break;
        case 'updateEvent':
			updateEvent($db);
		break;
		case 'moveEvent':
			moveEvent($db);
		break;
		case 'removeEvent':
			removeEvent($db);
		break;
        
	}
}

    function moveEvent($DB){

        if(isset($_POST['id']) && !empty($_POST['id']) && isset($_POST['date']) && !empty($_POST['date'])){
    
            $table = 'planning';
    
            $data= array("Date_RDV"  =>  $_POST['date'], "modified_at" => date('Y-m-d H:i:s'), "modified_by" => $_SESSION['user']['data'][0]['Id']);
                    
            $DB->table = $table;
            $DB->data = $data;
            $DB->where = 'id = ' .$_POST['id'];
    
            $updated = true && $DB->update();
                    push_notificationRDV($_POST['id']);
            //  if ($updated) 
            //   echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]);
            //  else 
            //   echo json_encode(["state" => "false", "message" => $GLOBALS['language']['something went wrong reload page and try again']]);
            
    
        }else{
            echo json_encode(["state" => "false", "message" => "missing data"]);
        }
        $DB = null;
    }

    function removeEvent($DB){

        if( isset($_POST['id']) && !empty($_POST['id']) ){
    
            $table = 'planning';
    
            $data= array("deleted"  =>  1, "modified_at" => date('Y-m-d H:i:s'), "modified_by" => $_SESSION['user']['data'][0]['Id']);
                    
            $DB->table = $table;
            $DB->data = $data;
            $DB->where = 'id = ' .$_POST['id'];
    
            $updated = true && $DB->update();
                    
            if ($updated) 
                echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Successfully Deleted']]);
            else 
                echo json_encode(["state" => "false", "message" => $GLOBALS['language']['something went wrong reload page and try again']]);
            
    
        }else{
            echo json_encode(["state" => "false", "message" => "missing id"]);
        }
        $DB = null;
    }

function updateEvent($DB){

        if(isset($_POST['id']) && !empty($_POST['id'])){
    
            $table = 'planning';
            $unique_val = $_POST['id'];
    
            $array_data= array();
            foreach($_POST['data'] as $data){
                
                if (strpos($data['name'], '__') !== false) {
                    $table_key = explode('__', $data['name'])[0];
                    $column = explode('__', $data['name'])[1];
    
                    if(stripos($column, 'password') !== false || stripos($column, 'pass') !== false){
                        $array_data[$table_key][$column] = sha1($data['value']);
                    }else{
                        // Check if the column already exists and is an array
                        if (isset($array_data[$table_key][$column]) && is_array($array_data[$table_key][$column])) {
                            $array_data[$table_key][$column][] = $data['value'];
                        } else {
                            // Create a new array only if there are multiple values
                            if (isset($array_data[$table_key][$column])) {
                                $array_data[$table_key][$column] = [$array_data[$table_key][$column], $data['value']];
                            } else {
                                // Create a new non-array value
                                $array_data[$table_key][$column] = $data['value'];
                            }
                        }
                        
                    }
                }else if(stripos($data['name'], 'csrf') !== false){
                    $csrf = $data['value'];
                    unset($data['csrf']);
                }
            }
        
            if(isset($csrf)){
                $csrf = customDecrypt($csrf);
                if(!is_csrf_valid($csrf)){
                    echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
                    exit();
                }
            } else {
                echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
                exit();
            }
            
            $filteredData = array_filter($array_data, function($key) use ($table) {
                return $key != $table;
            }, ARRAY_FILTER_USE_KEY);
            
            $restData = array_diff_key($array_data, $filteredData);
            $restData = array_values($restData)[0];
            $restData = array_merge( $restData, array("modified_at" => date('Y-m-d H:i:s'), "modified_by" => $_SESSION['user']['data'][0]['Id']) );
            
            
            
            $DB->table = $table;
            $DB->data = $restData;
            $DB->where = 'id = ' .$unique_val;
            $updated = true && $DB->update();
            if($updated && !isset($_POST['is_quote'])){
                $DB->table = 'planning_services';
                $DB->where = array('planning_id' => $unique_val);
                $DB->Delete();
            }
    
            if(is_array($filteredData) && !empty($filteredData)){
                $unique_id = 'planning_id';
                
                foreach ($filteredData as $table_name => $data) {
                    $DB->table = $table_name;
                    
                    if(is_array($data['service_id'])){
                        $extraData = array("$unique_id"  =>  $unique_val);
                        $data = array_map(function($service_id) use ($extraData) {
                            return array_merge($extraData, ['service_id' => $service_id]);
                        }, $data['service_id']);
                        $DB->multi = true;                 
    
                    }else{
                        $data = array_merge( $data, array("$unique_id"  =>  $unique_val) );
                    }
                    $DB->data = $data;
                    $updated = $updated && $DB->insert();
                }
            }
            
            if ($updated){ 
               push_notificationRDV($unique_val);
               echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]);
               
            }else{ 
               // echo json_encode(["state" => "false", "message" => $GLOBALS['language']['something went wrong reload page and try again']]);
            }
    
        }else{
            echo json_encode(["state" => "false", "message" => "missing id"]);
        }
        $DB = null;
    }

function postEvent($DB){
        $array_data = array();
        $table = 'planning';
    
        foreach($_POST['data'] as $data){
                
            if (strpos($data['name'], '__') !== false) {
                $table_key = explode('__', $data['name'])[0];
                $column = explode('__', $data['name'])[1];
    
                if(stripos($column, 'password') !== false || stripos($column, 'pass') !== false){
                    $array_data[$table_key][$column] = sha1($data['value']);
                }else{
                    if (isset($array_data[$table_key][$column]) && is_array($array_data[$table_key][$column])) {
                        $array_data[$table_key][$column][] = $data['value'];
                    } else {
                        // Create a new array only if there are multiple values
                        if (isset($array_data[$table_key][$column])) {
                            $array_data[$table_key][$column] = [$array_data[$table_key][$column], $data['value']];
                        } else {
                            // Create a new non-array value
                            $array_data[$table_key][$column] = $data['value'];
                        }
                    }
                }
            }else if(stripos($data['name'], 'csrf') !== false){
                $csrf = $data['value'];
                unset($data['csrf']);
            }
        }
    
        if(isset($csrf)){
            $csrf = customDecrypt($csrf);
            if(!is_csrf_valid($csrf)){
                echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
                exit();
            }
        } else {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    
        $filteredData = array_filter($array_data, function($key) use ($table) {
            return $key != $table;
        }, ARRAY_FILTER_USE_KEY);
    
        $restData = array_diff_key($array_data, $filteredData);
        $restData = array_values($restData)[0];
        $restData = array_merge( $restData, array("Garage_id"  =>  $_SESSION['user']['data'][0]['Id'], "created_by"  =>  $_SESSION['user']['data'][0]['Id']) );
        $DB->table 	= $table;
        $DB->data 	= $restData;
        $last_id 	= $DB->insert();
        $inserted = true && $last_id;
        
        if(is_array($filteredData) && !empty($filteredData)){
            $unique_id = ((substr($table, -1) === 's') ? substr($table, 0, -1) : $table).'_id';
            foreach ($filteredData as $table_name => $data) {
                $DB->table = $table_name;
                
                if(is_array($data['service_id'])){
                    $extraData = array("$unique_id"  =>  $last_id);
                    
                    $data = array_map(function($service_id) use ($extraData) {
                        return array_merge($extraData, ['service_id' => $service_id]);
                    }, $data['service_id']);
                    $DB->multi = true;      
                }else{
                    $data = array_merge( $data, array("$unique_id"  =>  $last_id) );
                }
                $DB->data = $data;
                $inserted = $inserted && $DB->insert();
            }
        }
    
        if($inserted){
            echo  json_encode(["state" => "true", "message" => $GLOBALS['language']['Added successfully']]); 
        } else {
            echo json_encode(["state" => "false", "message" => $inserted]);
        }
    
        $DB = null;
    }

function get_RDV($id = NULL, $return=false){
 echo "*********************";
   /*     $id = ($id != NULL ? " AND rdv.id = $id" : "");
        $doctor_id = (isset($_POST['doctor_id']) && !empty($_POST['doctor_id']) ? " AND rdv.doctor_id = ".$_POST['doctor_id'] : "");
        
    	$filters = (isset($_POST['filters']) && !empty($_POST['filters']) ? " AND rdv.state IN (".implode(', ', $_POST['filters']).")" : " AND rdv.state IN (".implode(', ', ['-999']).")" );
    	
        $sql = "SELECT rdv.*, CONCAT_WS(' ', patient.first_name, patient.last_name ) AS patient_name, rdv.date as Date_RDV, state FROM rdv LEFT JOIN patient ON patient.id = rdv.patient_id WHERE rdv.deleted = 0 $doctor_id $id $filters";
        $res =  $GLOBALS['db']->select($sql);
    	
        foreach ($res as $items) {
            $arrayData = [
                'id' => $items['id'],
                'title' => $items['patient_name'],
                'allDay' => true,
                'start' => $items['Date_RDV'],
                'end' => $items['Date_RDV'],
                'extendedProps' => [
                    'calendar' => ($items['state'] ?? ''),
                    'Client' => ["id" => $items['patient_id'], "name" => $items['patient_name']]
                ]
            ];
        
            // Add the converted data to the array
            $convertedData[] = $arrayData;
        }
    
    	if(empty($res)){
    		$arrayData = [
                'id' => '0',
                'title' => 'start calendar',
                'allDay' => false,
                'start' => '1970-01-01',
                'end' => '1970-01-01',
                'extendedProps' => [
                    'calendar' => 0,
                    'Client_id' => 0
                ]
            ];
    		$convertedData[] = $arrayData;
    	}
    
        if($return) { return $convertedData; }
    
    	echo json_encode($convertedData);*/
    }

function get_codes(){
	
    if(isset($_POST['csrf'])){
        $csrf_token = customDecrypt($_POST['csrf']);

        if(!is_csrf_valid($csrf_token)){
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    }else{
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }

    $qty = isset($_POST['qty']) && !empty($_POST['qty']) ? filter_var($_POST['qty'], FILTER_SANITIZE_NUMBER_INT) : 1;
    $card_id = filter_var($_POST['card_id'], FILTER_SANITIZE_NUMBER_INT);
    $error = 0;

    if($_SESSION['user']['data'][0]['type'] != 0){
        $details = $GLOBALS['db']->select("SELECT cards.price, users.balance FROM `cards`, users WHERE users.id = ".$_SESSION['user']['data'][0]['id']." AND cards.id = $card_id");
        $balance = !empty($details) ? $details[0]['balance'] : 0;
        $price = !empty($details) ? $details[0]['price'] : 0;

        if( ($price * $qty) > $balance)
            $error = 1;
    }else{
        $details = $GLOBALS['db']->select("SELECT cards.price FROM `cards` WHERE cards.id = $card_id");
        $price = !empty($details) ? $details[0]['price'] : 0;
    }
    
    if($error == 0){

        $ids = array_fill(0, $qty, $card_id);
        $data = array_map(function($id) use ($price) { return array( "product_id" => $id, "price" => $price, "user_id" => $_SESSION['user']['data'][0]['id'], "state" => 0 ); }, $ids);
            
        $GLOBALS['db']->table = 'operations';
        $GLOBALS['db']->data = $data;
        $GLOBALS['db']->multi = true;

        $inserted = $GLOBALS['db']->insert();

        echo json_encode([ "state" => "true", "id" => $inserted ]);

    }else
        echo json_encode([ "state" => "false", "message" => $GLOBALS['language']["balance is not enough"] ]);

    $GLOBALS['db'] = null;
}

function state_operation(){

    if( isset($_SESSION['user']) && !empty($_SESSION['user']) ){
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $res = $GLOBALS['db']->select("SELECT operations.* FROM `operations` WHERE operations.user_id = ".$_SESSION['user']['data'][0]['id']." AND operations.id = $id");
        
        if( !empty($res) ){
            if($res[0]['state'] == 0){
                echo json_encode([ "state" => "true", "message" => "false" ]);
            }else{
                if($res[0]['state'] == 1){
                    echo json_encode([ "state" => "true", "message" => "true", "code" => $res[0]['code'] ]);
                }else{
                    echo json_encode([ "state" => "true", "message" => ($res[0]['product_id'] != NULL && $res[0]['product_id'] != 0 ? $GLOBALS['language']["failed to get code"] : $GLOBALS['language']["amount transfer failed"] ) ]);
                }
            }
        }else
            echo json_encode([ "state" => "false", "message" => $GLOBALS['language']["something went wrong reload page and try again"] ]);

    }else{
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['something went wrong reload page and try again']]);
    }
    $GLOBALS['db'] = null;
}

function get_product(){
	
	$sql = "SELECT price FROM products WHERE id = ".(isset($_POST['id']) ? $_POST['id'] : 0 )."";

	$response = $GLOBALS['db']->select($sql);
	$GLOBALS['db'] = null;
	echo json_encode($response[0]);

}

function get_card(){
	
    if(isset($_POST['csrf'])){
        $csrf_token = customDecrypt($_POST['csrf']);

        if(!is_csrf_valid($csrf_token)){
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    }else{
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }

    $id = abs(filter_var(customDecrypt($_POST['id']), FILTER_SANITIZE_NUMBER_INT));
	$sql = "SELECT name, image FROM cards WHERE deleted = 1 AND id = ".(isset($_POST['id']) ? $_POST['id'] : 0 )."";

	$response = $GLOBALS['db']->select($sql);

    $GLOBALS['db'] = null;
    if( !empty($response) ){
        echo json_encode(["state" => "true", "data" => $response[0]]);
    }else{
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['missing_data']]);
    }
}

function get_user(){
	
	$sql = "SELECT first_name, last_name, email, phone1, balance, credit FROM users WHERE id = ".(isset($_POST['id']) ? $_POST['id'] : 0 )."";

	$response = $GLOBALS['db']->select($sql);
	$GLOBALS['db'] = null;
	echo json_encode($response[0]);

}

function sendCodes(){

    if(isset($_POST['csrf'])){
        $csrf_token = customDecrypt($_POST['csrf']);
        
        if( ! is_csrf_valid($csrf_token) ){
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    }else{
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }
	
    $qty = filter_var($_POST['qty'], FILTER_SANITIZE_NUMBER_INT);
    $card_id = filter_var($_POST['card_id'], FILTER_SANITIZE_NUMBER_INT);
    $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
    $parent_id = (isset($_SESSION['user']) ? ( $_SESSION['user']['data'][0]['type'] == 0 ? 0 : $_SESSION['user']['data'][0]['id'] ) : NULL );

	$sql = "SELECT * FROM codes WHERE deleted = 1 AND state = 1 AND card_id = $card_id AND user_id = $parent_id";
    $totalcodes = $GLOBALS['db']->rowsCount($sql);

    if($qty <= $totalcodes){

        $details = $GLOBALS['db']->select("SELECT cards.price, users.balance FROM `cards`, users WHERE users.id = $user_id AND cards.id = $card_id");
        $balance = !empty($details) ? $details[0]['balance'] : 0;
        $price = !empty($details) ? $details[0]['price'] : 0;
        
        if( ($price * $qty) <= $balance){
            $response = $GLOBALS['db']->select($sql." LIMIT $qty");
            $ids = array_values(array_column($response, 'id'));
            $datetime = date('Y-m-d H:i:s');
			
            $GLOBALS['db']->table = 'codes';
            $GLOBALS['db']->data = array( 'user_id'  =>  $user_id, "modified_at"  =>  "$datetime", "modified_by"  =>  $_SESSION['user']['data'][0]['id'] );
            $GLOBALS['db']->where = 'id IN ( ' . implode(', ', $ids) . ')';

            $updated = $GLOBALS['db']->update();

            if($updated){

                $stmt = $GLOBALS['db']->prepare("UPDATE `users` SET `balance`= CASE WHEN id=".$_SESSION['user']['data'][0]['id']." THEN (balance + ($price * $qty)) WHEN id=$user_id THEN (balance - ($price * $qty)) ELSE balance END WHERE id IN (".$_SESSION['user']['data'][0]['id'].", $user_id)");
                $updated_balance = $stmt->execute();

                if( $_SESSION['user']['data'][0]['type'] != 0 ){

                    $sql = "SELECT (SELECT commissions.percentage FROM commissions WHERE commissions.card_id IS NULL AND commissions.mobilis_com IS NULL AND commissions.ooredoo_com IS NULL AND commissions.djezzy_com IS NULL AND commissions.deleted = 1 AND commissions.child = ".$_SESSION['user']['data'][0]['id']." ) AS default_com,
                            (SELECT commissions.percentage FROM commissions INNER JOIN cards ON cards.id = commissions.card_id WHERE commissions.deleted = 1 AND commissions.child = ".$_SESSION['user']['data'][0]['id']." AND commissions.card_id = $card_id ) AS card_com";    

                    if($_SESSION['user']['data'][0]['parent'] != 0 && $_SESSION['user']['data'][0]['parent'] != NULL){
                        $sql = "SELECT users.balance, (SELECT commissions.percentage FROM commissions WHERE commissions.card_id IS NULL AND commissions.mobilis_com IS NULL AND commissions.ooredoo_com IS NULL AND commissions.djezzy_com IS NULL AND commissions.deleted = 1 AND commissions.child = ".$_SESSION['user']['data'][0]['id']." ) AS default_com,
                                (SELECT commissions.percentage FROM commissions INNER JOIN cards ON cards.id = commissions.card_id WHERE commissions.deleted = 1 AND commissions.child = ".$_SESSION['user']['data'][0]['id']." AND commissions.card_id = $card_id ) AS card_com FROM users WHERE users.id = ".$_SESSION['user']['data'][0]['parent']." AND users.deleted = 1 ";    
                    }

                    $commissions = $GLOBALS['db']->select($sql);
                    $commission = isset($commissions[0]['card_com']) && $commissions[0]['card_com'] != NULL ? $commissions[0]['card_com'] : ( isset($commissions[0]['default_com']) && $commissions[0]['default_com'] != NULL ? $commissions[0]['default_com'] : 0);
                    $global_commision = (($price * $qty) * $commission)/100 ;

                    if(isset($commissions[0]['balance'])){

                        $query = ( $commissions[0]['balance'] >= $global_commision ? " `balance`= (balance - $global_commision ) " : " `credit`= (credit + $global_commision ) " );

                        $stmt = $GLOBALS['db']->prepare("UPDATE `users` SET $query WHERE id = ".$_SESSION['user']['data'][0]['parent']);
                        $updated_com = $stmt->execute();

                    }

                    $stmt = $GLOBALS['db']->prepare("UPDATE `users` SET `bonus`= (bonus + $global_commision ) WHERE id = ".$_SESSION['user']['data'][0]['id']);
                    $updated_bonus = $stmt->execute();
                }

                $data = array_map(function($id){ return array( "product_id" => $id, "user_id" => $_SESSION['user']['data'][0]['id'], "state" => 1 ); }, $ids);
                
                $GLOBALS['db']->table = 'operations';
                $GLOBALS['db']->data = $data;
                $GLOBALS['db']->multi = true;

                $GLOBALS['db']->insert();

                echo json_encode([ "state" => "true", "message" => $GLOBALS['language']["sended successfully"] ]);
            }else{
                echo json_encode([ "state" => "false", "message" => $GLOBALS['language']["something went wrong reload page and try again"] ]);
            }

        }else{
            echo json_encode([ "state" => "false", "message" => $GLOBALS['language']["client balance is not enough"] ]);
        }
    }else{
        echo json_encode([ "state" => "false", "message" => $GLOBALS['language']["There are not enough codes"] ]);
    }
	
	$GLOBALS['db'] = null;

}

function getCodes($id = NULL){
        
    $byId = ($id != null) ? " AND codes.id = $id" : '';
    $query = "SELECT codes.*, category_card.id AS category_id FROM `codes` INNER JOIN cards ON cards.id = codes.card_id INNER JOIN category_card ON cards.category_id = category_card.id WHERE 1 $byId";
    $response = $GLOBALS['db']->select($query);

    // array_walk_recursive($response, function(&$item, $key){
    //     if( $key == 'code' ) $item = customDecrypt($item);
    // });

    return $response;
}

function postCodes(){
    
    $table = 'codes';
    
    if(isset($_POST['csrf'])){
        $csrf_token = customDecrypt($_POST['csrf']);
        
        if( ! is_csrf_valid($csrf_token) ){
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    }else{
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }

    if(isset($_POST['file']) && !empty($_POST['file']) && isset($_POST['card_id']) && !empty($_POST['card_id'])){
       
        $card_id = $_POST['card_id'];
        $codes = file(SITE_URL."$_POST[file]");
        
        $codes = array_map(function($item) { return customEncryption( trim( $item ) ); }, $codes);
       
        $codes_string = array_reduce($codes, function($carry, $item) {
            return $carry . "'" . $item . "',";
        }, '');
        $codes_string = rtrim($codes_string, ',');
        
        if($codes_string != ''){
            $sql = "SELECT code FROM codes WHERE card_id = $card_id AND code IN ($codes_string)";
                    
            $stmt = $GLOBALS['db']->prepare($sql);
            
            $rejected_code = ( ( $stmt->execute() ) ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [] );
        }else
            $rejected_code = [];

        // Remove rejected code from file
        $allowed_code = array_values(array_filter($codes, function ($item) use ($rejected_code) {
                            return !in_array($item, $rejected_code);
                        }));

        // prepare allowed code array
        $allowed_code = array_map(function($item) use($card_id) {
                            return array( "card_id" => $card_id, "code" => $item, "created_by" => $_SESSION['user']['data'][0]['id']);
                        }, $allowed_code);

    }else
        $codes = []; 

    // }else{
    //     $codes = array_map(function($item){
    //         $item['code'] = customEncryption( $item['code'] );
    //         $item['created_by'] = $_SESSION['user']['data'][0]['id'];
    //         return $item;
    //     }, $_POST['data']);
    // }
    if( count($codes) ){
        
        if( count($allowed_code) ){

            $GLOBALS['db']->table = $table;
            $GLOBALS['db']->data = $allowed_code;
            $GLOBALS['db']->multi = true;

            $inserted = $GLOBALS['db']->insert();

            $GLOBALS['db'] = null;

            if($inserted){
                echo  json_encode(["state" => "true", "message" => $GLOBALS['language']['inserted_code'].': '.count($allowed_code).'<br/>'.$GLOBALS['language']['codes_exist_in_queue'].': '.count($rejected_code) ]); 
            }else
                echo json_encode(["state" => "false", "message" => $GLOBALS['language']['something went wrong reload page and try again'] ]);

        }else
            echo json_encode(["state" => "true", "message" => $GLOBALS['language']['inserted_code'].': 0 <br/>'.$GLOBALS['language']['codes_exist_in_queue'].': '.count($rejected_code) ]);

    }else
        echo json_encode(["state" => "true", "message" => $GLOBALS['language']['The file does not contain any code']]);
}

function updateCodes(){

	$table = 'codes';
    
    if(isset($_POST['csrf'])){
        $csrf_token = customDecrypt($_POST['csrf']);
        
        if( ! is_csrf_valid($csrf_token) ){
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    }else{
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }

    $datetime = date('Y-m-d H:i:s');
    $array_data = array_merge( $_POST['data'], array("modified_at"  =>  "$datetime", "modified_by"  =>  $_SESSION['user']['data'][0]['id']) );
    
    array_walk_recursive($array_data, function(&$item, $key){
        if( $key == 'code' ) $item = customEncryption($item);
    });

	$GLOBALS['db']->table = $table;
	$GLOBALS['db']->data = $array_data;
	$GLOBALS['db']->where = 'id = ' . $_POST['id'];

	$updated = $GLOBALS['db']->update();
	$GLOBALS['db'] = null;

	if($updated){
        echo  json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]); 
    }else{
        echo json_encode(["state" => "false", "message" => $updated]);
    }
}

function postPayment(){
    
    $array_data = array();
    $table = 'payments';

    foreach($_POST['data'] as $data){
        $array_data[$data['name']] = $data['value'];
    }

    if(isset($array_data['csrf'])){
        $csrf_token = customDecrypt($array_data['csrf']);
        unset($array_data['csrf']);

        if(!is_csrf_valid($csrf_token)){
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    }else{
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }

    $array_data = array_merge($array_data, array( "ref" => createCode($GLOBALS['db']), "created_by"  =>  $_SESSION['user']['data'][0]['id'] ));

    $GLOBALS['db']->table = $table;
    $GLOBALS['db']->data = $array_data;

    $inserted = $GLOBALS['db']->insert();
    
    if($inserted){
        if($_SESSION['user']['data'][0]['type'] != 0){
            $stmt = $GLOBALS['db']->prepare("UPDATE `users` SET balance = (balance - $array_data[balance])  WHERE id = ".$_SESSION['user']['data'][0]['id']);
            $updated_com = $stmt->execute();
        }
        
        $GLOBALS['db']->table = 'users';
        $GLOBALS['db']->data = array( "balance" => $array_data['new_balance'], "credit" => $array_data['new_credit']);
        $GLOBALS['db']->where = 'id = ' . $array_data['user_id'];
        $GLOBALS['db']->update();

        echo  json_encode(["state" => "true", "message" => $GLOBALS['language']['Added successfully']]); 
    }else{
        echo json_encode(["state" => "false", "message" => $inserted]);
    }
    $GLOBALS['db'] = null;
}

function updatePayment(){
    $id_payment = isset($_POST['id']) && !empty($_POST['id']) ? explode("-", customDecrypt($_POST['id'])) : 0;
    $id_payment = is_array($id_payment) && is_numeric($id_payment[1]) ? $id_payment[1] : 0;

    if( $id_payment ){
        $array_data = array();
        $table = 'payments';

        foreach($_POST['data'] as $data){
            $array_data[$data['name']] = $data['value'];
        }

        if(isset($array_data['csrf'])){
            $csrf_token = customDecrypt($array_data['csrf']);
            unset($array_data['csrf']);

            if(!is_csrf_valid($csrf_token)){
                echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
                exit();
            }
        }else{
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }

        $datetime = date('Y-m-d H:i:s');
        $array_data = array_merge( $array_data, array("modified_at"  =>  "$datetime", "modified_by"  =>  $_SESSION['user']['data'][0]['id']) );

        $GLOBALS['db']->table = $table;
        $GLOBALS['db']->data = $array_data;
        $GLOBALS['db']->where = 'id = ' .$id_payment;
        $updated = $GLOBALS['db']->update();
        
        if($updated){
            $GLOBALS['db']->table = 'users';
            $GLOBALS['db']->data = array( "balance" => $array_data['new_balance'], "credit" => $array_data['new_credit'], "modified_at"  =>  "$datetime", "modified_by"  =>  $_SESSION['user']['data'][0]['id']);
            $GLOBALS['db']->where = 'id = ' . $array_data['user_id'];
            $GLOBALS['db']->update();

            echo  json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]); 
        }else{
            echo json_encode(["state" => "false", "message" => $updated]);
        }
    }else
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['missing_data']]);
    
    $GLOBALS['db'] = null;
}

function createCode($DB ,$table = "payments" , $field = "ref") {
    $DB->table = $table;
    $DB->field = $field;

    do {
        $Code = generateCode();
        $DB->value = $Code;
    } while ($DB->validateField());
    
    return $Code;
}

function generateCode() {
    $bytes = random_bytes(5);
    $encoded = base64_encode($bytes);
    $stripped = str_replace(['=', '+', '/'], '', $encoded);
    
    return  $stripped;
}

function postuser(){
    
    $array_data = array();
    $table = 'users';
    
    foreach($_POST['data'] as $data){
        if (stripos($data['name'], 'password') !== false) {
            $array_data[$data['name']] = sha1($data['value']);
        } else {
            $array_data[$data['name']] = $data['value'];
        }
    }

    if(isset($array_data['csrf'])){
        $csrf_token = customDecrypt($array_data['csrf']);
        unset($array_data['csrf']);

        if(!is_csrf_valid($csrf_token)){
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    }else{
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }

    if(!empty($array_data)){

        $default_com = array("card_id" => NULL, "percentage"  =>  ( $array_data['default_com'] ?? 0 ), "mobilis_com" => NULL , "ooredoo_com" => NULL , "djezzy_com" => NULL );
        if(isset($array_data['default_com']))
            unset($array_data['default_com']);
        
        $flexy_com = array("card_id" => NULL, "percentage"  =>  NULL, "mobilis_com" => ( $array_data['mobilis'] ?? 0 ) , "ooredoo_com" => ( $array_data['ooredoo'] ?? 0 ) , "djezzy_com" => ( $array_data['djezzy'] ?? 0 ) );   
        if(isset($array_data['mobilis']))
            unset($array_data['mobilis']);
        
        if(isset($array_data['ooredoo']))
            unset($array_data['ooredoo']);
        
        if(isset($array_data['djezzy']))
            unset($array_data['djezzy']);
        
        $type = abs(filter_var(customDecrypt($_POST['class']), FILTER_SANITIZE_NUMBER_INT));

        $array_data = array_merge( $array_data, array("type" =>  $type, "parent" => ( $_SESSION['user']['data'][0]['type'] == 0 ? ( isset($array_data['parent']) ? $array_data['parent'] : 0 ) : $_SESSION['user']['data'][0]['id'] ), "created_by" => $_SESSION['user']['data'][0]['id'] ) );
        $GLOBALS['db']->table = $table;
        $GLOBALS['db']->data = $array_data;

        $inserted = $GLOBALS['db']->insert();
        
        if($inserted){
            $users_com = array("parent" => ( $_SESSION['user']['data'][0]['type'] == 0 ? ( isset($array_data['parent']) ? $array_data['parent'] : 0 ) : $_SESSION['user']['data'][0]['id'] ), "child" => $inserted);

            $commissions = array();
            $commissions[] = array_merge($users_com , $default_com);
            $commissions[] = array_merge($users_com , $flexy_com);

            if( isset($_POST['commissions']) && !empty($_POST['commissions']) ){
                $cards_com = array_map(function($subArray) use ($users_com) {
                                return array_merge( $users_com, $subArray, array( "mobilis_com" => NULL, "ooredoo_com" => NULL, "djezzy_com" => NULL ) );
                            }, $_POST['commissions']);
                $commissions = array_merge($commissions , $cards_com);
            }

            $GLOBALS['db']->table = 'commissions';
            $GLOBALS['db']->data  = $commissions;
            $GLOBALS['db']->multi = true;
            $GLOBALS['db']->insert();

            echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Added successfully']]); 
        }else{
            echo json_encode(["state" => "false", "message" => $inserted]);
        }
    }else
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['missing_data']]);
        
    $GLOBALS['db'] = null;
}

function updateuser(){

    $id_user = abs(filter_var(customDecrypt($_POST['id']), FILTER_SANITIZE_NUMBER_INT));
    if( $id_user ){
        $array_data = array();
        $table = 'users';

        foreach($_POST['data'] as $data){
            $array_data[$data['name']] = $data['value'];
        }

        if(isset($array_data['csrf'])){
            $csrf_token = customDecrypt($array_data['csrf']);
            unset($array_data['csrf']);

            if(!is_csrf_valid($csrf_token)){
                echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
                exit();
            }
        }else{
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }

        $commissions = get_userCommissions($id_user);
        
        if(isset($array_data['default_com'])){
            $default_com = array("percentage"  =>  $array_data['default_com'] );
            unset($array_data['default_com']);

            $GLOBALS['db']->table = 'commissions';
            $GLOBALS['db']->data = $default_com;
            $GLOBALS['db']->where = 'id = ' .( $commissions['default_com']['id'] ?? 0 );
            $GLOBALS['db']->update();
        }

        if( isset($array_data['mobilis']) && isset($array_data['ooredoo']) && isset($array_data['djezzy']) ){
            $flexy_com = array("mobilis_com" => $array_data['mobilis'] , "ooredoo_com" => $array_data['ooredoo'] , "djezzy_com" => $array_data['djezzy'] );   

            unset($array_data['mobilis']);
            unset($array_data['ooredoo']);
            unset($array_data['djezzy']);

            $GLOBALS['db']->table = 'commissions';
            $GLOBALS['db']->data = $flexy_com;
            $GLOBALS['db']->where = 'id = ' .( $commissions['flexy']['id'] ?? 0 );
            $GLOBALS['db']->update();
        }
        
        $datetime = date('Y-m-d H:i:s');
        $array_data = array_merge( $array_data, array("modified_at"  =>  "$datetime", "modified_by"  =>  $_SESSION['user']['data'][0]['id']) );
        
        $GLOBALS['db']->table = $table;
        $GLOBALS['db']->data = $array_data;       
        $GLOBALS['db']->where = 'id = ' .$id_user;
        $updated = $GLOBALS['db']->update();
        
        if($updated){

            if(isset($commissions['commissions']) && !empty($commissions['commissions']) ){
                $deleted = array_values(array_column($commissions['commissions'], 'id'));
                $GLOBALS['db']->table = "commissions";
                $GLOBALS['db']->data = $deleted;
                $GLOBALS['db']->column = 'id';
                $GLOBALS['db']->multi = true;
                $GLOBALS['db']->Delete();
            }
            
            if( isset($_POST['commissions']) && !empty($_POST['commissions']) ){
                $users_com = array("parent" => ( $_SESSION['user']['data'][0]['type'] == 0 ? ( isset($array_data['parent']) ? $array_data['parent'] : 0 ) : $_SESSION['user']['data'][0]['id'] ), "child" => $id_user);
               
                $cards_com = array_map(function($subArray) use ($users_com) {
                    return array_merge($users_com, $subArray);
                }, $_POST['commissions']);

                $GLOBALS['db']->table = 'commissions';
                $GLOBALS['db']->data  = $cards_com;
                $GLOBALS['db']->multi = true;
                $GLOBALS['db']->insert();
            }

            echo  json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]); 
        }else{
            echo json_encode(["state" => "false", "message" => $updated]);
        }
    }else
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['missing_data']]);
    
    $GLOBALS['db'] = null;
}
