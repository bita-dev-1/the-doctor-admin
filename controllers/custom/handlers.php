<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Ensure PHPMailer is loaded
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require 'vendor/autoload.php';
}

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
        // --- NEW CASE for admin password reset ---
        case 'adminResetPassword':
            adminResetPassword();
        break;
	}
}

/**
 * NEW: Generic and secure function to send emails using settings from .env
 */
function sendEmail($recipientEmail, $recipientName, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Uncomment for debugging
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION; // PHPMailer::ENCRYPTION_SMTPS or 'tls'
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        //Recipients
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($recipientEmail, $recipientName);
    
        // Content
        $mail->isHTML(true);                                  
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // In a real app, you'd log this error instead of echoing it
        // error_log("Mailer Error: {$mail->ErrorInfo}");
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

/**
 * MODIFIED: This function now handles password recovery for any user type from the login page
 */
function forget_password() {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    // Search in the 'users' table instead of 'doctor'
    $sql = "SELECT * FROM `users` WHERE `deleted` = 0 AND `email` = ?";
    $stmt = $GLOBALS['db']->prepare($sql);
    $stmt->execute([$email]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $newPassword = generateRandomPassword();
        $password_hash = sha1($newPassword);
        
        $fullName = $user_data['first_name'].' '.$user_data['last_name'];
        $subject = 'Réinitialisation de votre mot de passe - The Doctor App';
        $body = "
            <h3>Réinitialisation de Mot de Passe</h3>
            <p>Bonjour {$fullName},</p>
            <p>Votre mot de passe a été réinitialisé. Voici vos nouvelles informations de connexion :</p>
            <p><strong>Nouveau mot de passe :</strong> {$newPassword}</p>
            <p>Nous vous recommandons de changer ce mot de passe après votre première connexion.</p>
            <p>Merci,<br>L'équipe The Doctor</p>
        ";
        
        $emailSent = sendEmail($email, $fullName, $subject, $body);

        if ($emailSent === true) {
            $GLOBALS['db']->table = "users";
            $GLOBALS['db']->data = array("password" => $password_hash);
            $GLOBALS['db']->where = 'id = ' . $user_data['id'];
            if ($GLOBALS['db']->update()) {
                echo json_encode(["state" => "true", "message" => "Un nouveau mot de passe a été envoyé à votre adresse e-mail."]);
            } else {
                echo json_encode(["state" => "false", "message" => "Erreur lors de la mise à jour du mot de passe."]);
            }
        } else {
            echo json_encode(["state" => "false", "message" => "Impossible d'envoyer l'e-mail. Veuillez contacter le support."]);
        }
    } else {
        echo json_encode(["state" => "false", "message" => "Aucun compte trouvé avec cette adresse e-mail."]);
    }
}

/**
 * NEW: Handles password reset initiated by an admin
 */
function adminResetPassword() {
    if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
        echo json_encode(["state" => "false", "message" => "Accès non autorisé."]);
        return;
    }

    $target_user_id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $admin_id = $_SESSION['user']['id'];
    $admin_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
    $is_super_admin = empty($admin_cabinet_id);

    // Fetch target user's info
    $sql = "SELECT * FROM `users` WHERE `id` = ?";
    $stmt = $GLOBALS['db']->prepare($sql);
    $stmt->execute([$target_user_id]);
    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target_user) {
        echo json_encode(["state" => "false", "message" => "Utilisateur non trouvé."]);
        return;
    }

    // Security check: Cabinet Admin can only reset passwords for users in their own cabinet
    if (!$is_super_admin && $target_user['cabinet_id'] != $admin_cabinet_id) {
        echo json_encode(["state" => "false", "message" => "Vous n'avez pas la permission de réinitialiser le mot de passe de cet utilisateur."]);
        return;
    }

    // Proceed with reset
    $newPassword = generateRandomPassword();
    $password_hash = sha1($newPassword);
        
    $fullName = $target_user['first_name'].' '.$target_user['last_name'];
    $subject = 'Votre mot de passe a été réinitialisé par un administrateur';
    $body = "
        <h3>Réinitialisation de Mot de Passe</h3>
        <p>Bonjour {$fullName},</p>
        <p>Votre mot de passe a été réinitialisé par un administrateur. Voici vos nouvelles informations de connexion :</p>
        <p><strong>Nouveau mot de passe :</strong> {$newPassword}</p>
        <p>Nous vous recommandons de changer ce mot de passe après votre prochaine connexion.</p>
        <p>Merci,<br>L'équipe The Doctor</p>
    ";

    $emailSent = sendEmail($target_user['email'], $fullName, $subject, $body);

    if ($emailSent === true) {
        $GLOBALS['db']->table = "users";
        $GLOBALS['db']->data = array("password" => $password_hash, "modified_by" => $admin_id, "modified_at" => date('Y-m-d H:i:s'));
        $GLOBALS['db']->where = 'id = ' . $target_user_id;
        if ($GLOBALS['db']->update()) {
            echo json_encode(["state" => "true", "message" => "Le mot de passe de l'utilisateur a été réinitialisé et envoyé par e-mail."]);
        } else {
            echo json_encode(["state" => "false", "message" => "Erreur lors de la mise à jour du mot de passe dans la base de données."]);
        }
    } else {
        echo json_encode(["state" => "false", "message" => "Impossible d'envoyer l'e-mail de réinitialisation."]);
    }
}


// Function to generate a random password
function generateRandomPassword($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $password = '';
    $char_length = strlen($characters);
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, $char_length - 1)];
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

    // MODIFIED: Use new session structure
    if(isset($_SESSION['user']) && !empty($_SESSION['user']['id'])):

        $conversationId = NULL;
        if(isset($_POST['conversation']) && !empty($_POST['conversation'])){
            $conversationId = ((int) str_replace('conversationId-', '', ($_POST['conversation'])));
            $conversationId = is_numeric( $conversationId ) ? $conversationId : NULL;
        }

        // MODIFIED: Pass the correct user ID
        $results = conversationsRoom($_SESSION['user']['id']);
        $global_data['chat_list'] = $results;
        $global_data['data']['messages'] = (($conversationId != NULL) ? messages($conversationId, ( isset($_POST['last']) ? ($_POST['last']) : NULL ) ) : array());
        $global_data['data']['users'] = (($conversationId != NULL) ? getConversationParticipants($conversationId) : array());

        echo json_encode($global_data);
        
    else:
        echo json_encode(array());
    endif;
}


function chat_list($conversationId = NULL){
    // MODIFIED: Use new session structure
	if(isset($_SESSION['user']) && !empty($_SESSION['user']['id'])):	
		$conversationId = is_numeric(str_replace('conversationId-', '', ($conversationId))) ? str_replace('conversationId-', '', ($conversationId)) : NULL;
	
        // MODIFIED: Pass the correct user ID
		$results = conversationsRoom($_SESSION['user']['id']);
		$global_data['chat_list'] = $results;
		$global_data['data']['messages'] = (($conversationId != NULL) ? messages($conversationId) : array());
		$global_data['data']['users'] = (($conversationId != NULL) ? getConversationParticipants($conversationId) : array());
		return $global_data;
	
	endif;
		
	return array();
}



function conversationsRoom($user_id, $limit = 20, $offset = 0){
    
    // MODIFIED: The query is now more flexible. It finds conversations where the user is either the creator/doctor side (my_particib) or the patient side (id_particib).
    // It correctly identifies the "other" participant in both scenarios.
    $query = "
    SELECT DISTINCT 
        conversation.*, 
        (
            -- This subquery now correctly fetches the OTHER participant's details
            SELECT CONCAT('[', GROUP_CONCAT(
                JSON_OBJECT(
                    'userId', CASE WHEN p.my_particib = {$user_id} THEN p.id_particib ELSE p.my_particib END,
                    'user', CASE WHEN p.my_particib = {$user_id} THEN CONCAT(patient.first_name, ' ', patient.last_name) ELSE CONCAT(users.first_name, ' ', users.last_name) END,
                    'photo', CASE WHEN p.my_particib = {$user_id} THEN patient.image ELSE users.image1 END
                )
            ), ']')
            FROM participant p
            LEFT JOIN users ON users.id = p.my_particib
            LEFT JOIN patient ON patient.id = p.id_particib
            WHERE p.id_conversation = conversation.id AND (p.my_particib = {$user_id} OR p.id_particib = {$user_id})
        ) AS participants,
        (
            SELECT m.date_send 
            FROM messages m 
            WHERE m.id_conversation = conversation.id 
            ORDER BY m.date_send DESC 
            LIMIT 1
        ) AS date_sendLast_msg,
        (
            SELECT JSON_OBJECT(
                'id', m.id,
                'message', m.message,
                'type', m.type,
                'date_send', m.date_send,
                'userId', sender.id,
                'user', CONCAT(sender.first_name, ' ', sender.last_name),
                'photo', sender.image1
            )
            FROM messages m
            LEFT JOIN users AS sender ON m.id_sender = sender.id
            WHERE m.id_conversation = conversation.id
            ORDER BY m.date_send DESC
            LIMIT 1
        ) AS last_msg
    FROM conversation
    INNER JOIN participant ON conversation.id = participant.id_conversation
    WHERE 
        conversation.deleted = 0 
        AND participant.deleted = 0 
        AND (participant.my_particib = {$user_id} OR participant.id_particib = {$user_id})
    ORDER BY date_sendLast_msg DESC 
    LIMIT {$limit} OFFSET {$offset}";

	
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
    // MODIFIED: Use new session structure
    if(isset($_SESSION['user']) && !empty($_SESSION['user']['id'])){
    
        if(isset($_POST['conversation']) && !empty($_POST['conversation'])){
            $conversationId = str_replace('conversationId-', '', ($_POST['conversation']));
        }else{
            $GLOBALS['db']->table = 'conversation';
            // MODIFIED: Use new session structure
            $GLOBALS['db']->data = array( "id_creator" => 	$_SESSION['user']['id'] );
            $conversationId = $GLOBALS['db']->insert();
            if($conversationId){
            }
        }	
        if($conversationId && is_numeric($conversationId)){
            $data = array(
                "id_conversation" => $conversationId, 
                // MODIFIED: Use new session structure
                "id_sender" => 	$_SESSION['user']['id'], 
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
	// MODIFIED: Use new session structure
    $user_id = $_SESSION['user']['id'] ?? 0;
	$data = array("id_creator" => $user_id);

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

	$inserted_conversation_id = $GLOBALS['db']->insert();
	
	if($inserted_conversation_id){
		if(isset($_POST['participants']) && !empty($_POST['participants']) ){
			
            // --- START: MODIFIED LOGIC ---
            // Create two-way participation records so the chat appears for both users.
            $subData = [];
            foreach ($_POST['participants'] as $participant_id){
                // Record for the creator seeing the participant
				$subData[] = [
                    'id_conversation' => $inserted_conversation_id,
                    'my_particib' => $user_id, // The creator (doctor/admin)
                    'id_particib' => $participant_id // The other person (patient)
                ];

                // Record for the participant seeing the creator
                $subData[] = [
                    'id_conversation' => $inserted_conversation_id,
                    'my_particib' => $participant_id, // The other person (patient)
                    'id_particib' => $user_id // The creator (doctor/admin)
                ];
			}
            // --- END: MODIFIED LOGIC ---

			$GLOBALS['db']->table = 'participant';
			$GLOBALS['db']->data = $subData;
			$GLOBALS['db']->multi = true;

			$secondinsert = $GLOBALS['db']->insert();
			
			if($secondinsert){
				echo  json_encode(["state" => "true", "message" => 'Added successfully']); 
			}else
				echo json_encode(["state" => "false", "message" => "something went wrong while adding participants"]);

		}else
			echo  json_encode(["state" => "true", "message" => 'Added successfully']); 
	}else{
		echo json_encode(["state" => "false", "message" => "something went wrong while creating conversation"]);
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
        $user_role = $_SESSION['user']['role'] ?? null;
        $user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
        $user_id = $_SESSION['user']['id'] ?? 0;
    
        $id_filter = ($id != NULL ? " AND rdv.id = " . intval($id) : "");
        
        // Build WHERE clause based on user role and cabinet
        $where_clause = "";
        if ($user_role === 'admin' && !empty($user_cabinet_id)) {
            // Cabinet Admin sees all RDVs in their cabinet
            $where_clause = " AND rdv.cabinet_id = " . intval($user_cabinet_id);
        } elseif ($user_role === 'doctor' || $user_role === 'nurse') {
            // Doctor/Nurse sees only their own RDVs
            $where_clause = " AND rdv.doctor_id = " . intval($user_id);
        }
        // Super Admin (admin with null cabinet_id) has no cabinet filter, so they see all RDVs
    
        $filters = (isset($_POST['filters']) && !empty($_POST['filters']) ? " AND rdv.state IN (".implode(', ', array_map('intval', $_POST['filters'])).")" : " AND rdv.state >= -1" ); // Default to show all states
        
        $sql = "SELECT rdv.id, rdv.patient_id, rdv.date as Date_RDV, rdv.state, rdv.rdv_num, rdv.phone,
                COALESCE(CONCAT_WS(' ', patient.first_name, patient.last_name), CONCAT_WS(' ', rdv.first_name, rdv.last_name)) AS patient_name
                FROM rdv 
                LEFT JOIN patient ON patient.id = rdv.patient_id 
                WHERE rdv.deleted = 0 $where_clause $id_filter $filters";
                
        $res =  $GLOBALS['db']->select($sql);
        
        $convertedData = []; // Initialize as empty array
        if (!empty($res)) {
            foreach ($res as $items) {
                $arrayData = [
                    'id' => $items['id'],
                    'title' => $items['patient_name'],
                    'allDay' => true,
                    'start' => $items['Date_RDV'],
                    'end' => $items['Date_RDV'],
                    'extendedProps' => [
                        'calendar' => match((int)$items['state']) {
                            0 => 'warning', // Créé
                            1 => 'info',    // Accepté
                            2 => 'success', // Complété
                            3 => 'danger',  // Annulé
                            default => 'secondary'
                        },
                        'phone' => ($items['phone'] ?? ''),
                        'num_rdv' => ($items['rdv_num'] ?? ''),
                        'Client' => ["id" => $items['patient_id'], "name" => $items['patient_name']]
                    ]
                ];
                $convertedData[] = $arrayData;
            }
        }
    
        if(empty($convertedData)){
            $arrayData = [
                'id' => '0',
                'title' => 'start calendar',
                'allDay' => false,
                'start' => '1970-01-01',
                'end' => '1970-01-01',
                'extendedProps' => [
                    'calendar' => 'secondary',
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
    
        // This logic is commented out as it relies on an old structure. 
        // The current logic correctly calculates available tickets on the fly.
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
                    "patient_id"    =>  filter_var(($_POST['patient'] ?? null), FILTER_SANITIZE_NUMBER_INT), // Allow null for new patients
                    "date"          =>  $dateString,
                    
                    "first_name"    =>  filter_var(( $_POST['first_name'] ?? "" ), FILTER_SANITIZE_STRING),
                    "last_name"     =>  filter_var(( $_POST['last_name'] ?? "" ), FILTER_SANITIZE_STRING),
                    "phone"         =>  filter_var(( $_POST['phone'] ?? "" ), FILTER_SANITIZE_STRING),
                    
                    "rdv_num"       =>  filter_var(($_POST['rdv_num'] ?? 0), FILTER_SANITIZE_NUMBER_INT),
                    // --- MODIFIED: Use new session structure ---
                    "created_by"    =>  $_SESSION['user']['id'],
                    "cabinet_id"    =>  $_SESSION['user']['cabinet_id'] ?? null // Also log the cabinet_id
                ];
    
                // --- Remove null patient_id if it's not provided ---
                if (empty($data['patient_id'])) {
                    unset($data['patient_id']);
                }
                
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
	
	// MODIFIED: Use new, correct session structure for the security check
	if( isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id']) ){
		$id = abs(filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT));
		$state = abs(filter_var($_POST['state'], FILTER_SANITIZE_NUMBER_INT));
        
        // This logic can be uncommented if you need to manage ticket counts upon cancellation
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
        //         $GLOBALS['db']->data = array("tickets_rest" => json_encode($tickets_rest), "modified_at"  =>  "$datetime", "modified_by"  =>  $_SESSION['user']['id']);
        //         $GLOBALS['db']->where = "id = $doctor[id]";

        //         $updated = $GLOBALS['db']->update();
                
        //     }
        // }

		$datetime = date('Y-m-d H:i:s');

		$GLOBALS['db']->table = 'rdv';
		// MODIFIED: Use new, correct session structure for modified_by
		$GLOBALS['db']->data = array("state" => "$state", "modified_at"  =>  "$datetime", "modified_by"  =>  $_SESSION['user']['id']);
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
            $doctor_id = filter_var(($_POST['doctor']), FILTER_SANITIZE_NUMBER_INT);
            $dateString = filter_var( ($_POST['date'] ?? date('Y-m-d')), FILTER_SANITIZE_STRING) ;
    
            $date = new DateTime($dateString);
            // Use French locale to get French day names which match the database JSON keys
            setlocale(LC_TIME, 'fr_FR.UTF-8', 'fra'); 
            $dayName = ucwords(strftime('%A', $date->getTimestamp()));
            
            // MODIFIED: Fetch from 'users' table instead of 'doctor'
            $doctor_info_sql = "SELECT tickets_day FROM users WHERE id = ?";
            $stmt = $GLOBALS['db']->prepare($doctor_info_sql);
            $stmt->execute([$doctor_id]);
            $doctor_response = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($doctor_response) {
                $tickets_day_json = $doctor_response['tickets_day'] ?? '[]';
                $tickets_day_array = json_decode($tickets_day_json, true);

                // Ensure the day name exists in the array
                $nbrTickets = isset($tickets_day_array[$dayName]) ? intval($tickets_day_array[$dayName]) : 0;
                
                $restTickets = [];
                if($nbrTickets > 0){
                    $all_possible_tickets = range(1, $nbrTickets);

                    // Fetch reserved ticket numbers for the given doctor and date
                    $reserved_sql = "SELECT rdv_num FROM `rdv` WHERE doctor_id = ? AND state != 3 AND date = ?";
                    $stmt_reserved = $GLOBALS['db']->prepare($reserved_sql);
                    $stmt_reserved->execute([$doctor_id, $dateString]);
                    $reservedTickets = $stmt_reserved->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Find the tickets that are not yet reserved
                    $restTickets = array_diff($all_possible_tickets, $reservedTickets);
                }
                
                // Format the response for Select2
                foreach($restTickets as $ticket_num){
                    $response[] = array(
                        "id" => $ticket_num,
                        "text" => $ticket_num
                    );
                }
            }
        }
        echo json_encode($response);
        
    } catch (Throwable $th) {
        // Return an empty array in case of any error
        echo json_encode([]);
    }
}