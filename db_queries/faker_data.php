<?php

//$user = 'root';
//$password = 'DansonU206';
//$db = 'chatted_db';
//$host = 'localhost';
//$charset = 'utf8';
//
//
//$pdo = new PDO("mysql:host=$host;dbname=$db;cahrset=$charset", $user, $password);
//$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//
//
//$query = $pdo->query('
//insert into Projects
//values(
//	1,
//    "admin-panel",
//    "test@mail.com",
//    now(),
//    1,
//    "this is description"
//);');
//
//
//$query = $pdo->query('
//insert into Projects_list
//values(
//	1,
//    "test@mail.com",
//    true
//);');
//
// тестовый запрос!!!
//select proj_name, id_project from Projects
//where id_project = (
//select id_project from Projects_list
//    where contributor_email = "test@mail.com"
//)


require_once './Entities/Response.php';
require_once 'config.php';

const GET_PROJECT_LIST = "GET_PROJECT_LIST";

$email = "test@mail.com";

$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, JSON_UNESCAPED_UNICODE);
$conf = new Config();

$pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;cahrset=$conf->charset", $conf->user, $conf->password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$requested_query = $pdo->prepare('
    select proj_name, id_project from Projects
    where id_project in (
        select id_project from Projects_list
            where contributor_email = ?
    );'
);
$requested_query->execute([$email]);

$requested_data = $requested_query->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($requested_data);
//print_r($requested_data);
exit();