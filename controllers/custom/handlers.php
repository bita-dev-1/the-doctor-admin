<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if(isset($_POST['method']) && !empty($_POST['method'])){
	include_once 'config/DB.php';
	include_once 'includes/lang.php';
	global $db;
	$db = new DB();
	switch($_POST['method']){
		case 'acountState':
			acountState();
		break;
		case 'chat':
			chat();
		break;
		case 'send_msg':
			send_msg();
		break;
		case 'post_conversation':
			post_conversation();
		break;
		case 'updateState':
			updateState();
		break;
        case 'getPatients':
			getPatients(($_POST['id'] ?? null));
		break;
        case 'getRdvPatient':
			getRdvPatient();
		break;
        case 'postRdv':
			postRdv();
		break;
		case 'handleRdv_nbr':
			handleRdv_nbr();
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
		case 'forget_password':
			forget_password();
		break;
	}
}

function sendMail($subject, $body, $email, $name){
    $mail = new PHPMailer(true);

    try {
       //Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = "";                     //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->Username   = '';                  //SMTP username
        $mail->Password   = '';                     //SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
        $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        //Recipients
		$mail->setFrom('', 'Admin');
        $mail->addAddress($email, $name);     //Add a recipient
        $mail->addReplyTo('', 'Admin');
    
        $mail->isHTML(true);                                  
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        
        return true;

    } catch (Exception $e) {
        return false;
    }
}

function forget_password() {
        $email = $_POST['email'];
        $sql = "SELECT * FROM `doctor` WHERE doctor.deleted = 0 AND doctor.email = '".$email."'";
        $user_data = $GLOBALS['db']->select($sql);
        if (count($user_data)) {
			// Example usage
			$newPassword = generateRandomPassword();
			$firstLastName = $user_data[0]['first_name'].' '.$user_data[0]['last_name'];
			$recipient = 'recipient@example.com';
			$subject = 'Test Subject';
			$message = 'This is a test message.';
			$password_hash = sha1($newPassword);
				
			
			 $body = "
        ------------------------------------------------------------------<br/>
        nouveau mot de passe<br/>
        ------------------------------------------------------------------<br/>
        Cher $firstLastName,<br/>
        <br/>
        <br/>
        nouveau mot de passe : $newPassword
        <br/>
        Merci d'avoir lu.";
			$subject = 'App The Doctor';
			$result =	 sendMail($subject, $body, $email, $firstLastName);
			if($result){
				$GLOBALS['db']->table =  "doctor";
				$GLOBALS['db']->data = array("password" => "$password_hash");
                $GLOBALS['db']->where = 'id = '.$user_data[0]['id'];
                $changePassword = $GLOBALS['db']->update();
			}
			
			echo json_encode(["state" => "true", "message" => "Modified succefuly"]);
			
        } else {
            echo json_encode(["state" => "false", "message" => "Il n'y a aucun compte avec cette adresse e-mail. Vérifiez l'adresse e-mail"]);
        }
}

// Function to generate a random password (you can replace this with your own logic)
function generateRandomPassword($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

function time_ago($iTime0, $iTime1 = 0){

    if ($iTime1 == 0) { $iTime1 = time(); }
    $iTimeElapsed = $iTime1 - strtotime($iTime0);
	
    if ($iTimeElapsed < (60)) {
        $iNum = ''; $sUnit = $GLOBALS['language']['about a minute'];
    } else if ($iTimeElapsed < (60*60)) {
        $iNum = intval($iTimeElapsed / 60); $sUnit = $GLOBALS['language']['minute'];
    } else if ($iTimeElapsed < (24*60*60)) {
        $iNum = intval($iTimeElapsed / (60*60)); $sUnit = $GLOBALS['language']['hour'];
    } else if ($iTimeElapsed < (30*24*60*60)) {
        $iNum = intval($iTimeElapsed / (24*60*60)); $sUnit = $GLOBALS['language']['day'];
    } else if ($iTimeElapsed < (365*24*60*60)) {
        $iNum = intval($iTimeElapsed / (30*24*60*60)); $sUnit = $GLOBALS['language']['month'];
    } else {
        $iNum = intval($iTimeElapsed / (365*24*60*60)); $sUnit = $GLOBALS['language']['year'];
    }

    return $iNum . " " . $sUnit . (($iNum != 1 && $iNum != "") ? " " : "");
}

    function acountState(){

    	if(isset($_SESSION['user']) && !empty($_SESSION['user'])):
    
    		$conversationId = NULL;
    		if(isset($_POST['conversation']) && !empty($_POST['conversation'])){
    			$conversationId = ((int) str_replace('conversationId-', '', ($_POST['conversation'])));
    			$conversationId = is_numeric( $conversationId ) ? $conversationId : NULL;
    		}
    
    		$results = conversationsRoom($_SESSION['user']['data'][0]['id']);
    		$global_data['chat_list'] = $results;
    		$global_data['data']['messages'] = (($conversationId != NULL) ? messages($conversationId, ( isset($_POST['last']) ? ($_POST['last']) : NULL ) ) : array());
    		$global_data['data']['users'] = (($conversationId != NULL) ? getConversationParticipants($conversationId) : array());
    
    		echo json_encode($global_data);
    		
    	else:
    		echo json_encode(array());
    	endif;
    }

function chat_list($conversationId = NULL){
	if(isset($_SESSION['user']) && !empty($_SESSION['user'])):	
		$conversationId = is_numeric(str_replace('conversationId-', '', ($conversationId))) ? str_replace('conversationId-', '', ($conversationId)) : NULL;
	
		$results = conversationsRoom($_SESSION['user']['data'][0]['id']);
		$global_data['chat_list'] = $results;
		$global_data['data']['messages'] = (($conversationId != NULL) ? messages($conversationId) : array());
		$global_data['data']['users'] = (($conversationId != NULL) ? getConversationParticipants($conversationId) : array());
		return $global_data;
	
	endif;
		
	return array();

}

function conversationsRoom($user_id, $limit = 20, $offset = 0){
    
    $query = "SELECT DISTINCT conversation.*, 
(SELECT 
 CONCAT('[' , GROUP_CONCAT(
   JSON_OBJECT('userId', patient.id, 'user', CONCAT(patient.first_name, ' ', patient.last_name), 'photo', patient.image )
) , ']') 
 FROM participant p LEFT JOIN patient ON p.id_particib = patient.id WHERE p.deleted = 0 AND p.id_conversation = conversation.id  ) AS participants,
                                    
(SELECT messages.date_send FROM (messages INNER JOIN participant p ON (messages.id_sender = p.id_particib OR messages.id_sender = p.my_particib) AND p.deleted = 0 AND messages.id_conversation = p.id_conversation AND p.id_conversation = conversation.id AND messages.deleted = 0) ORDER BY messages.date_send DESC LIMIT 1) AS date_sendLast_msg,
                                    
(SELECT 
 CASE WHEN doctor.id IS NOT NULL OR doctor.id != '' THEN
 	JSON_OBJECT('id', messages.id ,'message', messages.message,'type',messages.type, 'date_send', messages.date_send , 'userId', doctor.id, 'user', CONCAT(doctor.first_name, ' ', doctor.last_name), 'photo', doctor.image1 ) 
 ELSE
 	JSON_OBJECT('id', messages.id ,'message', messages.message,'type',messages.type, 'date_send', messages.date_send , 'userId', patient.id, 'user', CONCAT(patient.first_name, ' ', patient.last_name), 'photo', patient.image ) 
 END
 
 FROM messages INNER JOIN participant p ON (messages.id_sender = p.id_particib OR messages.id_sender = p.my_particib) LEFT JOIN doctor ON p.my_particib = doctor.id LEFT JOIN patient ON p.id_particib = patient.id WHERE p.deleted = 0 AND messages.id_conversation = p.id_conversation AND p.id_conversation = conversation.id AND messages.deleted = 0 ORDER BY messages.date_send DESC LIMIT 1) AS last_msg

FROM conversation INNER JOIN participant ON conversation.id = participant.id_conversation AND conversation.deleted = 0 AND participant.deleted = 0 AND participant.my_particib = $user_id ORDER BY date_sendLast_msg DESC LIMIT $limit OFFSET $offset";

	
		$results = $GLOBALS['db']->select($query);
		array_walk_recursive($results, function(&$item, $key){
			if( $key == 'date_sendLast_msg' && !is_null($item) ) $item = time_ago($item);
			if( $key == 'participants' ) $item = json_decode($item, true);
			if( $key == 'last_msg' ){ 
					
				if(is_array(json_decode($item, true))){
					$item = json_decode($item, true);
					$item['message'] = ($item['message']);
				}
				
			}
			if( $key == 'id' ) $item = ('conversationId-'.$item);
		});
		
	
	return $results;
}

   function chat(){
    	$results = (( isset($_POST['conversation']) && is_numeric(str_replace('conversationId-', '', ($_POST['conversation']))) ) ? messages(str_replace('conversationId-', '', ($_POST['conversation']))) : array());
    	echo json_encode($results);
    }

    function messages($conversationId, $messageId = NULL, $limit = 40, $offset = 0){
        	$afterId = ($messageId != NULL && $messageId != 0 ? " AND messages.id > $messageId" : '');
        	$query ="SELECT DISTINCT messages.id, messages.type, messages.message,messages.id_sender,participant.my_particib, participant.id_particib, patient.image,
        	patient.id AS lhId FROM conversation INNER JOIN participant ON participant.id_conversation = conversation.id INNER JOIN messages ON (messages.id_sender = participant.id_particib OR messages.id_sender = participant.my_particib  )  AND messages.id_conversation = conversation.id INNER JOIN patient ON patient.id = participant.id_particib WHERE participant.deleted = 0 AND conversation.id = '$conversationId' $afterId ORDER BY messages.id";
        	$results = $GLOBALS['db']->select($query);
        	array_walk_recursive($results, function(&$item, $key){
        		if( $key == 'id' ) $item = ($item);
        		if( $key == 'message' ) $item = ($item);
        	});
		
        	return $results;
        }

   function getConversationParticipants($conversationId){
	$query ="SELECT patient.username FROM conversation INNER JOIN participant ON conversation.id = participant.id_conversation AND conversation.deleted = 1 AND participant.deleted = 1 INNER JOIN patient ON participant.id_particib = patient.id WHERE patient.id != ".$_SESSION['user']['data'][0]['id']." AND conversation.id = '$conversationId' ORDER BY participant.id";
	return $GLOBALS['db']->select($query);
}

function is_image($path){

	$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    $imageExtensions = array(
		'png' ,
		'jpe' ,
		'jpeg',
		'jpg' ,
		'gif' ,
		'bmp' ,
		'ico' ,
		'tiff',
		'tif' ,
	);
	
    if (in_array($extension, $imageExtensions)) {
        return true;
    }
    return false;
}

function is_fileExt($path){

	$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

	$filesExtensions = array(
		'txt' ,
		'json',
		'zip' ,
		'rar' ,
		'mp3' ,
		'pdf' ,
		'psd' ,
		'ai' ,
		'eps' ,
		'ps' ,
		'doc' ,
		'rtf' ,
		'xls' ,
		'ppt' ,
		'docx',
		'xlsx',
		'pptx',
	);
	
    if (in_array($extension, $filesExtensions)) {
        return true;
    }
    return false;
}

function send_msg(){
    	if(isset($_SESSION['user']) && !empty($_SESSION['user'])){
    	
    		if(isset($_POST['conversation']) && !empty($_POST['conversation'])){
    			$conversationId = str_replace('conversationId-', '', ($_POST['conversation']));
    		}else{
    			$GLOBALS['db']->table = 'conversation';
    			$GLOBALS['db']->data = array( "id_creator" => 	$_SESSION['user']['data'][0]['id'] );
    			$conversationId = $GLOBALS['db']->insert();
    			if($conversationId){
    			}
    		}	
    		if($conversationId && is_numeric($conversationId)){
    			$data = array(
    				"id_conversation" => $conversationId, 
    				"id_sender" => 	$_SESSION['user']['data'][0]['id'], 
    				"message" 	=> 	($_POST['message']),
    				"type"		=>	(isset($_POST['file']) ? ( is_image($_POST['message']) ? 1 : ( ( is_fileExt($_POST['message']) ? 2 : 0 ) ) ) : 0)
    			);
    
    			$GLOBALS['db']->table = 'messages';
    			$GLOBALS['db']->data = $data;
    
    			$inserted = $GLOBALS['db']->insert();
    			
    			if($inserted){
    				$results = messages($conversationId, (isset($_POST['last']) ? ($_POST['last']) : 0) );
    
    				echo json_encode(array("state" => "true", "data" => $results));
    				// (push_notification($conversationId, $inserted));
    			}
    			else
    				echo json_encode(array("state" => "false", "message" => "une erreur s'est produite, veuillez actualiser la page et réessayer"));
    		}else
    			echo json_encode(array("state" => "false", "message" => "une erreur s'est produite, veuillez actualiser la page et réessayer"));
    	}else
    		echo json_encode(array("state" => "false", "message" => "une erreur s'est produite, veuillez actualiser la page et réessayer"));
    }

   function post_conversation(){
	
	$data = array("id_creator" => $_SESSION['user']['data'][0]['id']);

	if(isset($_POST['name']) && !empty($_POST['name']))
		$data = array_merge($data, ["name" => $_POST['name']]);

	if(isset($_POST['csrf'])){
		$csrf_token = customDecrypt($_POST['csrf']);
	
		if( ! is_csrf_valid($csrf_token) ){
			echo json_encode(["state" => "false", "message" => 'The form is forged']);
			exit();
		}
	}else{
		echo json_encode(["state" => "false", "message" => 'The form is forged']);
		exit();
	}

	$GLOBALS['db']->table = 'conversation';
	$GLOBALS['db']->data = $data;

	$inserted = $GLOBALS['db']->insert();
	
	if($inserted){
		if(isset($_POST['participants']) && !empty($_POST['participants']) ){
			
			foreach ($_POST['participants'] as $key => $value){
				$object = new stdClass();
				$object->id_particib = $value;
				$object->id_conversation = $inserted;
                $object->my_particib = $_SESSION['user']['data'][0]['id'];
				$subData[] = $object;
			}
/*
			$object = new stdClass();
			$object->id_particib = $_SESSION['user']['data'][0]['id'];
			$object->id_conversation = $inserted;
			$subData[] = $object;*/

			$GLOBALS['db']->table = 'participant';
			$GLOBALS['db']->data = $subData;
			$GLOBALS['db']->multi = true;

			$secondinsert = $GLOBALS['db']->insert();
			
			if($secondinsert){
			/*	$tokens = $GLOBALS['db']->select("SELECT client.token FROM `client` WHERE client.token IS NOT NULL AND client.id IN (".$_SESSION['user']['data'][0]['id'].', '.implode(', ', $_POST['participants']).")");
				if(!empty($tokens))
					subscribeToTopic($tokens, "chat_$inserted");*/
				
				echo  json_encode(["state" => "true", "message" => 'Added successfully']); 
			}else
				echo json_encode(["state" => "false", "message" => "something went wrong"]);

		}else
			echo  json_encode(["state" => "true", "message" => 'Added successfully']); 
	}else{
		echo json_encode(["state" => "false", "message" => "something went wrong"]);
	}
}

function subscribeToTopic($tokens, $topic){
	
	foreach($tokens as $token){
		$curl = curl_init();

		curl_setopt_array($curl, array(
		CURLOPT_URL => "https://iid.googleapis.com/iid/v1/$token[token]/rel/topics/$topic",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_HTTPHEADER => array(
			'Content-Type: application/json',
			'Authorization: Bearer AAAAPiEtOI4:APA91bHdSiAII41N4XyIPgvWG8mSapghX1KiLWHycZsTQpcHuyqixmropj3T2Iav-6yny77FwOMbu63YPnEBlkxBCF7CizuqIOn5EW-NglsMN5S_4nFVFntjL_NKTtSP-k7HqK7Ruqoz'
		),
		));

		$response = curl_exec($curl);

		curl_close($curl);
	}
	return $response;
}

function push_notification($conversationId, $messageId){

	$query ="SELECT DISTINCT messages.*, lhuissier.username FROM conversation INNER JOIN participant ON participant.id_conversation = conversation.id INNER JOIN messages ON messages.id_sender = participant.id_particib AND messages.id_conversation = conversation.id INNER JOIN lhuissier ON lhuissier.id = participant.id_particib WHERE participant.deleted = 0 AND conversation.id = '$conversationId'  AND messages.id = $messageId";
	$message = $GLOBALS['db']->select($query);
	$message = $message[0];

	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS =>
	  '{
			"to": "/topics/chat_'.$conversationId.'",
			"notification": {
			
				"title": "You received a message from '.$message['username'].'"
			},	"data": '.json_encode($message).',"content_available": true,
		}',
	  CURLOPT_HTTPHEADER => array(
		'Authorization: key= AAAAPiEtOI4:APA91bHdSiAII41N4XyIPgvWG8mSapghX1KiLWHycZsTQpcHuyqixmropj3T2Iav-6yny77FwOMbu63YPnEBlkxBCF7CizuqIOn5EW-NglsMN5S_4nFVFntjL_NKTtSP-k7HqK7Ruqoz',
		'Content-Type: application/json'
	  ),
	));
	
	$response = curl_exec($curl);
	
	curl_close($curl);
	return $response;
}


    function moveEvent($DB){

        if(isset($_POST['id']) && !empty($_POST['id']) && isset($_POST['date']) && !empty($_POST['date'])){
    
            $table = 'rdv';
    
            $data= array("date"  =>  $_POST['date'], "modified_at" => date('Y-m-d H:i:s'), "modified_by" => $_SESSION['user']['data'][0]['id']);
                    
            $DB->table = $table;
            $DB->data = $data;
            $DB->where = 'id = ' .$_POST['id'];
    
            $updated = true && $DB->update();
                  //  push_notificationRDV($_POST['id']);
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
    
            $table = 'rdv';
    
            $data= array("deleted"  =>  1, "modified_at" => date('Y-m-d H:i:s'), "modified_by" => $_SESSION['user']['data'][0]['d']);
                    
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
    
            $table = 'rdv';
            $unique_val = $_POST['id'];
            $state = $_POST['rdv__state'];
    
            $DB->table = $table;
            $GLOBALS['db']->data = array("state" => "$state");
            $DB->where = 'id = ' .$unique_val;
            $updated = true && $DB->update();
            
            if ($updated){ 
             //  push_notificationRDV($unique_val);
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
        $restData = array_merge( $restData, array("Garage_id"  =>  $_SESSION['user']['data'][0]['id'], "created_by"  =>  $_SESSION['user']['data'][0]['id']) );
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
        $id = ($id != NULL ? " AND rdv.id = $id" : "");
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
					'phone' => ($items['phone'] ?? ''),
					'num_rdv' => ($items['rdv_num'] ?? ''),
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
    
    	echo json_encode($convertedData);
    }

function postRdv(){

    $doctor = filter_var(( $_POST['doctor'] ?? 0 ), FILTER_SANITIZE_NUMBER_INT);
    $dateString = filter_var(( $_POST['date'] ?? date("Y-m-d") ), FILTER_SANITIZE_STRING);

    // $date = new DateTime($dateString);
    // setlocale(LC_TIME, 'fr_FR');
    // $dayName = ucwords(strftime('%A', $date->getTimestamp()));
    // $datetime = date('Y-m-d H:i:s');
	
    // $response = $GLOBALS['db']->select("SELECT * FROM doctor WHERE doctor.id = $doctor")[0] ?? [];
    // $tickets_rest = json_decode($response['tickets_rest'], true);
    // $ticketThisDay = $tickets_rest[$dayName];
    
    // if( !empty($response) )
    //     if( $ticketThisDay > 0 ){

            $data = [
                "doctor_id"     =>  $doctor,
                "patient_id"    =>  filter_var(($_POST['patient'] ?? 0), FILTER_SANITIZE_NUMBER_INT),
                "date"          =>  $dateString,
                
                "first_name"    =>  filter_var(( $_POST['first_name'] ?? "" ), FILTER_SANITIZE_STRING),
                "last_name"     =>  filter_var(( $_POST['last_name'] ?? "" ), FILTER_SANITIZE_STRING),
                "phone"         =>  filter_var(( $_POST['phone'] ?? "" ), FILTER_SANITIZE_STRING),
                
                "rdv_num"       =>  filter_var(($_POST['rdv_num'] ?? 0), FILTER_SANITIZE_NUMBER_INT), //(json_decode($response['tickets_day'], true)[$dayName] - ($ticketThisDay-1)),
                "created_by"    =>  $_SESSION['user']['data'][0]['id']
            ];

            // $tickets_rest[$dayName] = $tickets_rest[$dayName] - 1;
            
            // $GLOBALS['db']->table = 'doctor';
            // $GLOBALS['db']->data = array("tickets_rest" => json_encode($tickets_rest), "modified_at"  =>  "$datetime", "modified_by"  =>  $_SESSION['user']['data'][0]['id']);
            // $GLOBALS['db']->where = "id = $doctor";

            // $updated = $GLOBALS['db']->update();

            
            $GLOBALS['db']->table = 'rdv';
            $GLOBALS['db']->data = $data;

            $res = $GLOBALS['db']->insert();

            $GLOBALS['db'] = null;
            
            if($res)
                echo json_encode( ["state" => "true", "message" => $GLOBALS['language']['Added successfully']] );
            else
                echo json_encode( ["state" => "false", "message" => $GLOBALS['language']['something went wrong reload page and try again']] );

        // }else{
        //     echo json_encode( ["state" => "false", "message" => "Il n\'y a pas de billet pour aujourd\'hui"] );
        // }    

}

function getPatients($id, $return = false){

	$id = abs(filter_var($id, FILTER_SANITIZE_NUMBER_INT));
	$sql = "SELECT patient.*, communes.id as communeId, communes.name as communeName, willaya.id as willayaId, willaya.willaya FROM patient LEFT JOIN communes ON communes.id = patient.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya WHERE patient.deleted = 0 AND patient.id = $id";

	$response = $GLOBALS['db']->select($sql);
	$GLOBALS['db'] = null;
    
    if($return)
        return $response;
    else
        echo  json_encode($response);

}

function getUsers($id){
	
    $id = abs(filter_var($id, FILTER_SANITIZE_NUMBER_INT));
	$sql = "SELECT doctor.*, communes.id as communeId, willaya.id as willayaId FROM doctor LEFT JOIN communes ON communes.id = doctor.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya WHERE doctor.deleted = 0 AND doctor.id = $id";

	$response = $GLOBALS['db']->select($sql);
	$GLOBALS['db'] = null;
	return $response;

}

function updateState(){
	
	if( isset($_SESSION['user']['data'][0]['id']) && !empty($_SESSION['user']['data'][0]['id']) ){
		$id = abs(filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT));
		$state = abs(filter_var($_POST['state'], FILTER_SANITIZE_NUMBER_INT));
        
        // if($state == 3){

        //     $doctor = getRdvDoctor($id);
        //     $dateString = filter_var($doctor['rdv_date'], FILTER_SANITIZE_STRING) ;

            
        //     if($dateString != ""){
        //         $date = new DateTime($dateString);
        //         setlocale(LC_TIME, 'fr_FR');
        //         $dayName = ucwords(strftime('%A', $date->getTimestamp()));
                
        //         $datetime = date('Y-m-d H:i:s');

        //         $tickets_rest = json_decode($doctor['tickets_rest'], true);
        //         $tickets_rest[$dayName] = $tickets_rest[$dayName] + 1;
                
        //         $GLOBALS['db']->table = 'doctor';
        //         $GLOBALS['db']->data = array("tickets_rest" => json_encode($tickets_rest), "modified_at"  =>  "$datetime", "modified_by"  =>  $_SESSION['user']['data'][0]['id']);
        //         $GLOBALS['db']->where = "id = $doctor[id]";

        //         $updated = $GLOBALS['db']->update();
                
        //     }
        // }

		$datetime = date('Y-m-d H:i:s');

		$GLOBALS['db']->table = 'rdv';
		$GLOBALS['db']->data = array("state" => "$state", "modified_at"  =>  "$datetime", "modified_by"  =>  $_SESSION['user']['data'][0]['id']);
		$GLOBALS['db']->where = "id = $id";

		$updated = $GLOBALS['db']->update();
		if($updated){
			echo  json_encode(["state" => $updated, "message" => $GLOBALS['language']['Edited successfully']]); 
		}else{
			echo json_encode(["state" => "false", "message" => $updated]);
		}
	}else
		echo json_encode( ["state" => "false", "message" => "missing id"] );

}

function getRdvPatient(){

	$id = abs(filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT));
	$sql = "SELECT patient.*, communes.name as communeName, willaya.willaya FROM rdv LEFT JOIN patient ON patient.id = rdv.patient_id LEFT JOIN communes ON communes.id = patient.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya WHERE rdv.id = $id";

	$response = $GLOBALS['db']->select($sql);
	$GLOBALS['db'] = null;
    
    echo  json_encode($response);

}

function getRdvDoctor($id){

	$id = abs(filter_var($id, FILTER_SANITIZE_NUMBER_INT));
	$sql = "SELECT doctor.*, rdv.date as rdv_date FROM rdv LEFT JOIN doctor ON doctor.id = rdv.doctor_id WHERE rdv.id = $id";

	$response = $GLOBALS['db']->select($sql);
    
    return $response[0] ?? [];
}

function handleRdv_nbr(){
    
    try {
    
        $response = [];
        if(isset($_POST['doctor']) && !empty($_POST['doctor']) ){
            $doctor = filter_var(($_POST['doctor']), FILTER_SANITIZE_NUMBER_INT);
            $dateString = filter_var( ($_POST['date'] ?? date('Y-m-d')), FILTER_SANITIZE_STRING) ;
    
            $date = new DateTime($dateString);
            setlocale(LC_TIME, 'fr_FR');
            $dayName = ucwords(strftime('%A', $date->getTimestamp()));
            
            $response = $GLOBALS['db']->select("SELECT * FROM doctor WHERE doctor.id = $doctor")[0] ?? [];
            $nbrTickets = json_decode(($response['tickets_day'] ?? '[]'), true)[$dayName] ?? 0;
            
            $restTickets = [];
            if($nbrTickets > 0){
                $tickets = range(1, $nbrTickets);
                $reservedTickets = $GLOBALS['db']->select("SELECT rdv_num FROM `rdv` WHERE rdv.doctor_id = $doctor AND rdv.state != 3 AND rdv.date = '$dateString'");
                $reservedTickets = array_values(array_column($reservedTickets, 'rdv_num'));
                
                $restTickets = array_diff($tickets, $reservedTickets);
            }
            
            $response = array();
            foreach($restTickets as $res){
                $response[] = array(
                    "id" => $res,
                    "text" => $res
                );
            }
            
        }
        echo json_encode($response);
        
    } catch (Throwable $th) {
        echo json_encode([]);
    }
}

function sendEmail($recipient, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 2; // Enable verbose debug output
        $mail->isSMTP(); // Set mailer to use SMTP
        $mail->Host = 'smtp.example.com'; // Specify main and backup SMTP servers
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = 'your_username@example.com'; // SMTP username
        $mail->Password = 'your_password'; // SMTP password
        $mail->SMTPSecure = 'tls'; // Enable TLS encryption, 'ssl' also accepted
        $mail->Port = 587; // TCP port to connect to

        // Sender info
        $mail->setFrom('your_email@example.com', 'Your Name');

        // Recipient
        $mail->addAddress($recipient);

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body = $message;

        // Send the email
        $mail->send();

        return true; // Email sent successfully
    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}