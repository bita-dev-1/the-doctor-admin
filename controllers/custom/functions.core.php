<?php
global $DB;
$DB = new DB();

function getSelected($request){

    $data	    =   json_decode(customDecrypt($request));
    $table 		=   $data->table;
    $select_val =   $data->value;
    $select_txt =   implode(",' ',",$data->text);
    $where 		=   isset($data->where) && !empty($data->where) ? " AND ".$data->where : "";

    $join_query = '';
    if (isset($data->join) && is_array($data->join) && !empty($data->join)) {
        $join_query = implode(' ', array_map(function($j) {
            return $j['type'].' '.$j['table'].' ON '.$j['condition'];
        }, $data->join));
    }

    $selected = " AND ".$select_val;
    if(isset($data->selected) && !empty($data->selected))
        $selected .= ( is_array($data->selected) ? " IN (". implode(',' , $data->selected).") " : " = ".$data->selected );
    
    $sql = "SELECT $select_val, CONCAT_WS(' ',$select_txt) AS select_txt FROM $table $join_query WHERE 1 $where $selected LIMIT 10";
    
    $response = $GLOBALS['DB']->select($sql);

    foreach($response as $res){
        echo '<option value="'.$res[$select_val].'" selected="selected">'.$res['select_txt'].'</option>';
    }
}

function dataById($data, $table, $join = []){

    $join_query = '';
    if (!empty($join)) {
        $join_query = implode(' ', array_map(function($j) {
            return $j['type'].' '.$j['table'].' ON '.$j['condition'];
        }, $join));
    }

    $sql = "SELECT * FROM $table $join_query WHERE $table.$data[column] = $data[val]";

    $response = $GLOBALS['DB']->select($sql);

    return $response;
}
