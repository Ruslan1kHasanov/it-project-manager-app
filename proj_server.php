<?php

require_once './Entities/Response.php';
require_once 'config.php';

const GET_PROJECT_LIST = "GET_PROJECT_LIST";
const CREATE_NEW_PROJECT = "CREATE_NEW_PROJECT";


$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, JSON_UNESCAPED_UNICODE);
$conf = new Config();

function create_new_project($conf, $data){
    $pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;cahrset=$conf->charset", $conf->user, $conf->password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $requested_query = $pdo->prepare('
        select proj_name, id_project from Projects
        where id_project in (
            select id_project from Projects_list
                where contributor_email = ?
        );'
    );
}

function get_project_list($conf, $data){
    $pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;cahrset=$conf->charset", $conf->user, $conf->password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $requested_query = $pdo->prepare('
        select proj_name, id_project from Projects
        where id_project in (
            select id_project from Projects_list
                where contributor_email = ?
        );'
    );
    $requested_query->execute([$data['user_email']]);

    $requested_data = $requested_query->fetchAll(PDO::FETCH_ASSOC);
//    $requested_data['proj_name'] ??= '';

//  сделать адекватную проврку на присутствие записей в таблице

    if ($requested_data[0] !== ''){
//        print_r($requested_data);
//        echo json_encode($requested_data);
        return $requested_data;
    }else{
        $response = new Response(true, "BAD_REQUEST_TO_DB");
    }

    return $response;
}

if ($data['type'] === GET_PROJECT_LIST){

    $response = get_project_list($conf, $data);

    header("Access-Control-Allow-Headers: X-Requested-With, content-type");
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
elseif ($data['type'] === CREATE_NEW_PROJECT){

}


echo "no selected type";
exit();