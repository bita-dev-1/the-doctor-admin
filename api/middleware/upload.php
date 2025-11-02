<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    $params = json_decode(file_get_contents("php://input"));
    
    
        
    if(isset($params->file) && !empty($params->file)){
        $target_dir = "uploads/";
        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $params->file));

        $image_content=base64_decode($params->file);
        $target_file = $target_dir . date("Y-m-d_H-i") ."_". md5(uniqid(rand(), true)) . ".". $params->ext;
        file_put_contents("".$target_file,$data);

        http_response_code(200);
        echo json_encode([ "link" => $target_file ]);
        
    }else{
        echo json_encode(["error" => "No exist any file."]);
        http_response_code(200);
    }


?>