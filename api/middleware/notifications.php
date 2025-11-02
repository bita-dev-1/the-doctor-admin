<?php

    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST, GET");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    
    // Includes
    include_once('../config/DB.php');
    
    $db = new DB();

    $query ="SELECT rdv.*, patient.token, CONCAT_WS(' ',patient.first_name, ' ', patient.last_name) AS patient, CONCAT_WS(' ',doctor.first_name, ' ', doctor.last_name) AS doctor FROM rdv INNER JOIN patient ON rdv.patient_id = patient.id INNER JOIN doctor ON rdv.doctor_id = doctor.id WHERE rdv.state = 1 AND rdv.notification_state = 0 AND rdv.date = CURDATE() LIMIT 50";
    $rdvs = $db->select($query);
    
    
    foreach($rdvs as $rdv){
        if($rdv['token'] != null && !empty($rdv['token']) )
            push_notification($rdv);
    }


    function push_notification($rdv){
    
    	if(isset($rdv['token']) && !empty($rdv['token'])){
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
        			"to": "'.$rdv['token'].'",
        			"notification": {
        				"body": "Bonjour '.$rdv['patient'].', votre rendez-vous chez le médecin '.$rdv['doctor'].' est prévu pour '.$rdv['date'].'. En cas d\'empêchement, veuillez nous prévenir.",
        				"title": "Rappel de rendez-vous"
        			}
        		}',
        	  CURLOPT_HTTPHEADER => array(
        		'Authorization: key=AAAAlrE5MMM:APA91bG_tRekl7pW8LEcWvqsdyb9oB1i1g3HbCEMh2pdVwzG68wab0TcnfY_Qd9gMQYptbPpT_MOkTtSmyZUb1nQaeT_CvXJhm4uLtIS89fkLHYnkoHT3pMAx5BkgvO1drwFE2aXVUEi',
        		'Content-Type: application/json'
        	  ),
        	));
        	
        	$response = curl_exec($curl);
        	
        	$theInfo = curl_getinfo($curl);
            $http_code = $theInfo['http_code'];
            
        	curl_close($curl);
        // 	print_r($response);
        	if($http_code == "200" && $response->success != "0"){
        	    echo 'true';
        	   // updateState($rdv['id']);
        	}else
        	    echo 'false';
        
    	}
    	return false;
    }
    
    function updateState($id){
        
        $GLOBALS['db']->table = "rdv";
    	$GLOBALS['db']->data  = array("notification_state"  =>  '1');
    	$GLOBALS['db']->where = ' id = ' . $id;
    
    	$updated = $GLOBALS['db']->update();
    	
    	return $updated;
    	
    }
    