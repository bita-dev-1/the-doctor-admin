<?php

    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: PUT,GET,POST");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


    try {
        $payload = file_get_contents('php://input');
        $headers = getallheaders();

            // $hash = hash_hmac('sha256', $payload, base64_encode('61a974deba2f8a7eddf9e2b9005e04cbea37ed781cde1cbc2266ed73b324b657'));
            // $hash = base64_encode($hash);
            // echo $hash;
        
        if ( array_key_exists("Authorization", $headers) && !empty(getBearerToken($headers["Authorization"]))) {

            $token      =  getBearerToken($headers["Authorization"]);
            $checkAuth  =  checkAuth($token, $payload);

            if ($checkAuth) {
                
                $payload = json_decode($payload);
                
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'POST':
                        Create($GLOBALS['db'], $payload);  
                    break;
                    case 'GET':
                        Read($GLOBALS['db'], $payload);
                    break;
                    case 'PUT':
                        Update($GLOBALS['db'], $payload);
                    break;
                    default:
                        echo json_encode(array("messages" => "Bad Request")); //400 Bad Request
                    break;
                }

            }else
                echo json_encode(array("messages" => "Unauthorized access")); //401 Unauthorized
            
        } else 
            echo json_encode(array("messages" => "Unauthorized access")); //401 Unauthorized
        
        header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 200 OK ');
        
    } catch (Exception $e) {

        logger("\nException: " . $e->getMessage() . "\n");
        header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 500 Internal Server Error ');

    }


?>