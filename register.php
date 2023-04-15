<?php

class Response {
    public bool $error;
    public string $message;

    function __construct(bool $error, string $message) {
        $this->error = $error;
        $this->message = $message;
    }
}

function register_new_user($user_data){

    $hash_password = md5($user_data['password']);

    $user = 'root';
    $password = 'DansonU206';
    $db = 'chatted_db';
    $host = 'localhost';
    $charset = 'utf8';

    $pdo = new PDO("mysql:host=$host;dbname=$db;cahrset=$charset", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = 'insert into users values (?, ?, ?)';
    $pdo->prepare($query)->execute([$user_data['email'], $user_data['login'], $hash_password]);
}

$raw_data = file_get_contents('php://input');

$data = json_decode($raw_data, JSON_UNESCAPED_UNICODE);

if(!array_key_exists('confirmed_password', $data) || !array_key_exists('email', $data) ||
    !array_key_exists('password', $data) || !array_key_exists('login', $data)){

    $response = new Response(true, "WRONG_INPUT_JSON");
    echo json_encode($response);
    exit();

}
else if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)){
    $response = new Response(true, "WRONG_EMAIL_VALIDATION");
    echo json_encode($response);
    exit();
}
else if($data["confirmed_password"] !== $data["password"]){
    $response = new Response(true, "PASSWORDS_NOT_SAME");
    echo json_encode($response);
    exit();
}
else if(strlen($data["password"]) < 1){
    $response = new Response(true, "WRONG_PASSWORD_VALIDATION");
    echo json_encode($response);
    exit();
}
else{
    register_new_user($data);
    $response = new Response(false, "REGISTRATION_DONE");
    echo json_encode($response);
    exit();
}