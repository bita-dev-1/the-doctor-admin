<?php

    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


    try {
        $payload = file_get_contents('php://input');
        //$headers = getallheaders();
        

        //if ( array_key_exists("Authorization", $headers) && !empty(getBearerToken($headers["Authorization"]))) {

            //$token      =  getBearerToken($headers["Authorization"]);
            //$checkAuth  =  checkAuth($token, $payload);
            
            //if ($checkAuth) {
                
                $payload = json_decode($payload);
                
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'POST':
                        Request($GLOBALS['db'], $payload);  
                    break;
                    default:
                        echo json_encode(array("messages" => "Bad Request")); //400 Bad Request
                    break;
                }

            //}else
            //    echo json_encode(array("messages" => "Unauthorized access signature")); //401 Unauthorized
            
        //} else 
        //    echo json_encode(array("messages" => "Unauthorized access")); //401 Unauthorized


        header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 200 OK ');
        
    } catch (Exception $e) {

        logger("\nException: " . $e->getMessage() . "\n");
        header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 200 Internal Server Error ');

    }


?>