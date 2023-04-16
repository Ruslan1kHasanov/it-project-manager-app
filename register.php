<?php

require_once './Entities/Response.php';

function register_new_user($user_data){
    $user = 'root';
    $password = 'DansonU206';
    $db = 'chatted_db';
    $host = 'localhost';
    $charset = 'utf8';

    $pdo = new PDO("mysql:host=$host;dbname=$db;cahrset=$charset", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $crypto_password = md5($user_data['password']);

    $query = 'insert into users values (?, ?, ?)';
    $pdo->prepare($query)->execute([$user_data['email'], $user_data['login'], $crypto_password]);
}

function is_email_in_use($email):bool{
    $user = 'root';
    $password = 'DansonU206';
    $db = 'chatted_db';
    $host = 'localhost';
    $charset = 'utf8';

    $pdo = new PDO("mysql:host=$host;dbname=$db;cahrset=$charset", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $requested_query = $pdo->prepare('SELECT email FROM Users WHERE email = ?');
    $requested_query->execute([$email]);

    $requested_data = $requested_query->fetch(PDO::FETCH_ASSOC);

    $verificationEmail = $requested_data['email'] ?? '';

    if(strlen($verificationEmail) === 0) {
        return false;
    }

    return true;
}

$raw_data = file_get_contents('php://input');

$data = json_decode($raw_data, JSON_UNESCAPED_UNICODE);

if (is_email_in_use($data['email'])){
    $response = new Response(true, "EMAIL_ALREADY_IN_USE");
}
else if(!array_key_exists('confirmed_password', $data) || !array_key_exists('email', $data) ||
    !array_key_exists('password', $data) || !array_key_exists('login', $data)){

    $response = new Response(true, "WRONG_INPUT_JSON");

}
else if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)){
    $response = new Response(true, "WRONG_EMAIL_VALIDATION");
}
else if($data["confirmed_password"] !== $data["password"]){
    $response = new Response(true, "PASSWORDS_NOT_SAME");
}
else if(strlen($data["password"]) < 1){
    $response = new Response(true, "WRONG_PASSWORD_VALIDATION");
}
else{
    register_new_user($data);
    $response = new Response(false, "REGISTRATION_DONE");
}

echo json_encode($response);
exit();