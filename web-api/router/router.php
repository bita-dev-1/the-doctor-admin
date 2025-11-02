<?php

function get($route, $path_to_include){
  if( $_SERVER['REQUEST_METHOD'] == 'GET' ){ route($route, $path_to_include); }  
}
function post($route, $path_to_include){
  if( $_SERVER['REQUEST_METHOD'] == 'POST' ){ route($route, $path_to_include); }    
}
function put($route, $path_to_include){
  if( $_SERVER['REQUEST_METHOD'] == 'PUT' ){ route($route, $path_to_include); }    
}
function patch($route, $path_to_include){
  if( $_SERVER['REQUEST_METHOD'] == 'PATCH' ){ route($route, $path_to_include); }    
}
function delete($route, $path_to_include){
  if( $_SERVER['REQUEST_METHOD'] == 'DELETE' ){ route($route, $path_to_include); }    
}
function any($route, $path_to_include){ route($route, $path_to_include); }

function request_path(){
  $request_uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
  $script_name = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
  $parts = array_diff_assoc($request_uri, $script_name);
  if (empty($parts)){
      return '/';
  }
  $path = implode('/', $parts);
  if (($position = strpos($path, '?')) !== FALSE){
      $path = substr($path, 0, $position);
  }
  return $path;
}

function route($route, $path_to_include){
  
  if($route == "/404" && !is_callable($path_to_include)){
    include_once("$path_to_include");
    exit();
  }  
  $request_url = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
  $request_url = rtrim($request_url, '/');
  $request_url = strtok($request_url, '?');
  $route_parts = explode('/', $route);
  $request_url_parts = explode('/', $request_url);
  array_shift($route_parts);
  array_shift($request_url_parts);
  if( $route_parts[0] == '' && count($request_url_parts) == 0 ){
    include_once("$path_to_include");
    exit();
  }
  if( count($route_parts) != count($request_url_parts) ){ return; }  
  $parameters = [];
  for( $__i__ = 0; $__i__ < count($route_parts); $__i__++ ){
    $route_part = $route_parts[$__i__];
    if( preg_match("/^[$]/", $route_part) ){
      $route_part = ltrim($route_part, '$');
      array_push($parameters, $request_url_parts[$__i__]);
      $$route_part=$request_url_parts[$__i__];
    }
    else if( $route_parts[$__i__] != $request_url_parts[$__i__] ){
      return;
    } 
  }
  // Callback function
  if( is_callable($path_to_include) ){
    call_user_func($path_to_include);
    exit();
  }    
  include_once("$path_to_include");
  exit();
}
function out($text){echo htmlspecialchars($text);}