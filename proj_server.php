<?php

require_once './Entities/Response.php';
require_once 'config.php';

$raw_data = file_get_contents('php://input');

$data = json_decode($raw_data, JSON_UNESCAPED_UNICODE);

$conf = new Config();

$pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;cahrset=$conf->charset", $conf->user, $conf->password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$requested_query = $pdo->prepare('SELECT id_project, proj_name FROM Projects WHERE proj_name = ?');
$requested_query->execute([$data['proj_name']]);

$requested_data = $requested_query->fetch(PDO::FETCH_ASSOC);

$requested_data['proj_name'] ??= '';

if ($requested_data['proj_name'] !== ''){
    $response = new Response(false, $requested_data['proj_name']);
}else{
    $response = new Response(true, "BAD_REQUEST_TO_DB");
}
header("Access-Control-Allow-Headers: X-Requested-With, content-type");
header('Content-Type: application/json');

echo json_encode($response);
exit();
