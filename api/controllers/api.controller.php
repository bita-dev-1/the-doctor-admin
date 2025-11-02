<?php

$secret_Key = "61a974deba2f8a7eddf9e2b9005e04cbea37ed781cde1cbc2266ed73b324b657";

if (!function_exists('getallheaders')) {
    
    function getallheaders(){
        $headers = [];
        foreach ($_SERVER as $name => $value){
           if (substr($name, 0, 5) == 'HTTP_')
           {
               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
           }
        }
       return $headers;
    }

}

function getBearerToken($headerAuth) {
    
    if (!empty($headerAuth)) {
        if (preg_match('/Bearer\s(\S+)/', $headerAuth, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function checkAuth($token, $payload){

    $result = hash_is_valid($payload, $token);
    
    return $result;

}
 
function hash_is_valid($payload, $signature){

    $computed_hash = hash_hmac('sha256', $payload, base64_encode($GLOBALS['secret_Key']));
    $computed_hash = base64_encode($computed_hash);
    
    return hash_equals($signature,$computed_hash);

}
 
function log_result($result, $txt = "status: " ){

    $result_to_log = $result == 1 ? "passed" : "failed";
    if (!$result) {
        logger($txt. $result_to_log);
        logger("Generated at: " . date('Y-m-d H:i:s'));
    }
}

function logger($txt){

    $log_file = "log.txt";
    $myfile = fopen($log_file, "a") or die("Unable to open file!");
    fwrite($myfile, "\n" . $txt);
    fclose($myfile);

}

function Read($db, $payload){

    extract((array) $payload);
    if(!isset($data)){
        // limit offset
        $extension = "";
        if(isset($limit)){
            if(!isset($offset)) $offset = 0;
            $extension = " LIMIT $limit OFFSET $offset";
        }
        
         
        
        // search & exact
        $search_query = "";
        if(isset($exact)){
            $search_query = " AND " . $exact;
        }
        
        if(isset($search)){
            $keys= implode(", ", array_keys((array) $search));
            $search_value= implode("%", array_values((array) $search));
            $search_query .=" AND CONCAT($keys) Like '%".$search_value."%' ";
        }
        
        // inner join
        $inner_join_query = "";
        if(isset($innerjoin)){
            $inner_join_query = " AND $table.$innerjoincol = $innerjoin.id ";
            $innerjoin = "," . $innerjoin;
        }else{
            $innerjoin = "";
        }
        
    
        
        // data
        if(isset($table)){
            $data = $db->select("SELECT * FROM $table $innerjoin WHERE 1=1 " . $inner_join_query . $search_query . $extension);
            $count_query = "SELECT * FROM $table";
        }else{
            $data = $db->select($sql . $extension); // Not working for now
        }
        
        // GET COUNT
        
        $count = $db->rowsCount($count_query);
        $count_filtred = $db->rowsCount("SELECT * FROM $table $innerjoin WHERE 1=1 " . $inner_join_query . $search_query);
            
        header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 200 OK ');
        echo json_encode(["filtred"=> $count_filtred, "count"=>$count, "data"=>$data]);
    }else{
        header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 401 Unauthorized');
        echo json_encode(["state"=> "false", "message" => "access denied"]);
    }  
    
}

function Create($db, $payload){
    extract((array) $payload);

    if( isset($data) && !isset($id) && !isset($where) ){
        
        $db->data = (array) $data;
        $db->table = $table;
        
        $result = $db->insert();
        
        if($result) 
            echo json_encode(["id" => $result]); 
        else
            echo json_encode(["state" => "false"]);
    }else{
        echo json_encode(["state" => "false", "message" => "missing data"]);
    }
    
}

function Update($db, $payload){
    extract((array) $payload);

    if( isset($data) && (isset($id) || isset($where)) ){
        $db->data = $data;
        $db->table = $table;
        $db->where = isset($id) ?  "id = $id" : $where;
        
        $result = $db->update();
        if($result)
            echo json_encode(["state" => "true"]);
        else
            echo json_encode(["state" => "false"]);
    }else{
        echo json_encode(["state" => "false", "message" => "missing data"]);
    }
}

function Request($db, $payload){

    extract((array) $payload);  
    
    if(!isset($data)){
        
        // limit offset
        $extension = "";
        if(isset($limit)){
            if(!isset($offset)) $offset = 0;
            $extension = " LIMIT $limit OFFSET $offset";
        }
        
        // search & exact
        $search_query = "";
        if(isset($exact)){
            $search_query = " AND " . $exact;
        }
        
        if(isset($search)){
            $keys= implode(", ", array_keys((array) $search));
            $search_value= implode("%", array_values((array) $search));
            $search_query .=" AND CONCAT($keys) Like '%".$search_value."%' ";
        }
        
        
        // inner join
        $inner_join_query = "";
        if(isset($innerjoin)){
            $inner_join_query = " AND $table.$innerjoincol = $innerjoin.id ";
            $innerjoin = "," . $innerjoin;
        }else{
            $innerjoin = "";
        }   
       
        
        // data
        if(isset($table)){
            $data = $db->select("SELECT * FROM $table $innerjoin WHERE 1=1 " . $inner_join_query . $search_query . $extension);
            $count_query = "SELECT * FROM $table";
        }else{
            $data = $db->select($sql . $extension); // Not working for now
        }
        
      
        
        // GET COUNT
        
        $count = $db->rowsCount($count_query);
        $count_filtred = $db->rowsCount("SELECT * FROM $table $innerjoin WHERE 1=1 " . $inner_join_query . $search_query);
            
        http_response_code(200);
        echo json_encode(["filtred"=> $count_filtred, "count"=>$count, "data"=>$data]);
        
    }else{
        
        if(isset($id) || isset($where)){
            $db->data = $data;
            $db->table = $table;
            $db->where = isset($id) ?  "id = $id" : $where;
            
            $result = $db->update();
            
            echo json_encode(["state" => "true"]);
        }else{
            $db->data = (array) $data;
            $db->table = $table;
            
            $result = $db->insert();
            
            if($result) echo json_encode(["id" => $result]); else echo json_encode(["state" => "false"]);
        }
    }

}

?>