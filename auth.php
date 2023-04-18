<?php

require_once './Entities/Response.php';
require_once 'config.php';

$conf = new Config();

$pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;cahrset=$conf->charset", $conf->user, $conf->password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$raw_data = file_get_contents('php://input');

$data = json_decode($raw_data, JSON_UNESCAPED_UNICODE);

$requested_query = $pdo->prepare('SELECT email, password FROM Users WHERE email = ? and password = ?');
$requested_query->execute([$data['email'], md5($data['password'])]);

$requested_data = $requested_query->fetch(PDO::FETCH_ASSOC);

$verificationEmail = $requested_data['email'] ?? '';
$verificationPassword = $requested_data['password'] ?? '';

if ($verificationEmail === $data['email'] and $verificationPassword === md5($data['password'])){
    $response = new Response(false, "AUTH_DONE");
}else{
    $response = new Response(true, "WRONG_PASS_OR_EMAIL");
}
echo json_encode($response);
exit();
