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
                        $idUser; 
                       
                        if(isset($specialty_id)){
                            $extension .= " AND doctor.specialty_id = $specialty_id ";
                        }
                        if(isset($commune_id)){
                            $extension .= " AND doctor.commune_id = $commune_id ";
                        }
                         if(isset($state)){
                             if($state==0 ||$state==1 ) 
                            $extension .= " AND rdv.state = $state AND rdv.date>= DATE(NOW()) ";
                            else   $extension .= " AND rdv.state = $state ";
                           
                        }
                           /*if(isset($date)){
                            $extension .= " AND rdv.date = $date ";
                        }*/
                        
                        
                        $limit = isset($limit) ? $limit : 200;
                        $offset = isset($offset) ? $offset : 0;
                            
                        $extension .= " ORDER BY rdv.id DESC LIMIT $limit OFFSET $offset";
                        
                        $count = ( stripos(request_path(), 'rdv/me') !== false ) ? "(SELECT COUNT(r1.id) FROM rdv r1 WHERE r1.date = rdv.date AND r1.doctor_id = rdv.doctor_id AND r1.rdv_num < rdv.rdv_num AND r1.state = 1 AND r1.deleted = 0) AS previous, " : "";
                        $query = "SELECT $count doctor.*, rdv.id as idRdv, rdv.patient_id, rdv.rdv_num, rdv.date, rdv.hours, rdv.state, specialty.namefr AS specialty, willaya.willaya, communes.name FROM `rdv` LEFT JOIN doctor ON doctor.id = rdv.doctor_id LEFT JOIN specialty ON specialty.id = doctor.specialty_id LEFT JOIN communes ON communes.id = doctor.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya WHERE rdv.deleted=0 AND rdv.patient_id= $idUser ";
                    
                        if( stripos(request_path(), 'rdv/me') !== false ){
                            $query .= " AND rdv.date = CURRENT_DATE() AND state = 1 ";
                        } else
                            $query .= $extension;
                            
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