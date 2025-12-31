<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Ensure DB is available via Global or Include
if (!isset($GLOBALS['db'])) {
	$rootPath = dirname(__DIR__, 2);
	if (file_exists($rootPath . '/config/DB.php')) {
		require_once($rootPath . '/config/DB.php');
		$GLOBALS['db'] = new DB();
	}
}

$db = $GLOBALS['db'];

// Secure Query
$query = "SELECT rdv.*, patient.token, CONCAT_WS(' ',patient.first_name, ' ', patient.last_name) AS patient, CONCAT_WS(' ',doctor.first_name, ' ', doctor.last_name) AS doctor 
          FROM rdv 
          INNER JOIN patient ON rdv.patient_id = patient.id 
          INNER JOIN doctor ON rdv.doctor_id = doctor.id 
          WHERE rdv.state = 1 AND rdv.notification_state = 0 AND rdv.date = CURDATE() 
          LIMIT 50";

$rdvs = $db->select($query);

foreach ($rdvs as $rdv) {
	if ($rdv['token'] != null && !empty($rdv['token']))
		push_notification($rdv);
}

function push_notification($rdv)
{
	// Load FCM Key from Environment
	$fcm_key = $_ENV['FCM_SERVER_KEY'] ?? '';

	if (empty($fcm_key)) {
		error_log("FCM Key is missing in .env");
		return false;
	}

	if (isset($rdv['token']) && !empty($rdv['token'])) {
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
			CURLOPT_POSTFIELDS => json_encode([
				"to" => $rdv['token'],
				"notification" => [
					"body" => "Bonjour " . $rdv['patient'] . ", votre rendez-vous chez le médecin " . $rdv['doctor'] . " est prévu pour " . $rdv['date'] . ". En cas d'empêchement, veuillez nous prévenir.",
					"title" => "Rappel de rendez-vous"
				]
			]),
			CURLOPT_HTTPHEADER => array(
				'Authorization: key=' . $fcm_key,
				'Content-Type: application/json'
			),
		));

		$response = curl_exec($curl);
		$theInfo = curl_getinfo($curl);
		$http_code = $theInfo['http_code'];
		curl_close($curl);

		$responseObj = json_decode($response);

		if ($http_code == "200" && isset($responseObj->success) && $responseObj->success != "0") {
			echo 'true';
			// Mark as sent
			updateState($rdv['id']);
		} else {
			echo 'false';
		}
	}
	return false;
}

if (!function_exists('updateState')) {
	function updateState($id)
	{
		$GLOBALS['db']->table = "rdv";
		$GLOBALS['db']->data = array("notification_state" => '1');
		$GLOBALS['db']->where = ' id = ' . intval($id);
		$updated = $GLOBALS['db']->update();
		return $updated;
	}
}
?>