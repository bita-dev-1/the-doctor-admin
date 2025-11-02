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
                        extract((array) $payload);
                        $extension = "";
                         
                        if(isset($specialty_id)){
                            $extension .= " AND doctor.specialty_id = $specialty_id ";
                        }
                        if(isset($commune_id)){
                            $extension .= " AND doctor.commune_id = $commune_id ";
                        }
                        
                        $limit = isset($limit) ? $limit : 20;
                        $offset = isset($offset) ? $offset : 0;
                            
                        $extension .= " LIMIT $limit OFFSET $offset";
                        
                        $query = "SELECT doctor.*, specialty.namefr AS specialty, willaya.willaya, communes.name FROM doctor LEFT JOIN specialty ON specialty.id = doctor.specialty_id LEFT JOIN communes ON communes.id = doctor.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya  WHERE doctor.deleted=0  $extension";
                    
                        $data = $GLOBALS['db']->select($query);
                    
                        echo json_encode(["data"=>$data]);
                         
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
