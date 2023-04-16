<?php

require_once './Entities/Response.php';

$user = 'root';
$password = 'DansonU206';
$db = 'chatted_db';
$host = 'localhost';
$charset = 'utf8';

$pdo = new PDO("mysql:host=$host;dbname=$db;cahrset=$charset", $user, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$email = 'kcehua_dopowehko@mail.ru';
$password = '3f19eb8e9d04f4bb812bca91383294e1';


$requested_query = $pdo->prepare('SELECT email, password FROM Users WHERE email = ? and password = ?');
$requested_query->execute([$email, $password]);

$requested_data = $requested_query->fetch(PDO::FETCH_ASSOC);

$verificationEmail = $requested_data['email'] ?? '';
$verificationPassword = $requested_data['password'] ?? '';

if ($verificationEmail === $email and $verificationPassword === $password){
    $response = new Response(false, "AUTH_DONE");
}else{
    $response = new Response(true, "WRONG_PASS_OR_EMAIL");
}

echo json_encode($response);
exit();
