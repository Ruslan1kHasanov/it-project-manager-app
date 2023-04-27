<?php

require_once './Entities/Response.php';
require_once 'config.php';
require_once './tested.php';
const GET_PROJECT_LIST = "GET_PROJECT_LIST";
const CREATE_NEW_PROJECT = "CREATE_NEW_PROJECT";
const MUST_BE_AUTHORIZED = "MUST_BE_AUTHORIZED";

$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, JSON_UNESCAPED_UNICODE);
$conf = new Config();

function create_new_project($conf, $data)
{
    $pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;cahrset=$conf->charset", $conf->user, $conf->password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $requested_query = $pdo->prepare('
        insert into Projects (proj_name, creator_email, date_of_creating, project_description)
        values (?, ?, ?, ?);'
    );

    $requested_query->execute([$data['proj_name'], $data['creator_email'],
        date('Y-m-d'), $data['project_description']]);

    $requested_query = $pdo->prepare('
        select id_project, creator_email, proj_name from Projects
        where creator_email = ? and proj_name = ?;
    ');

    $requested_query->execute([$data['creator_email'], $data['proj_name']]);

    $requested_data = $requested_query->fetch(PDO::FETCH_ASSOC);

    $requested_query = $pdo->prepare('
        insert into Projects_list
        values (?, ?, ?);'
    );

//    print_r($requested_data);

    $requested_query->execute([$requested_data['id_project'], $requested_data['creator_email'], 1]);

    $response = ($requested_query) ? new Response(false, "PROJECT_WERE_CREATE")
        : new Response(true, "BAD_REQUEST_TO_DB");
    return $response;
}

function get_project_list($conf, $data)
{
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

    if (array_key_exists(0, $requested_data)) {
//        print_r($requested_data);
//        echo json_encode($requested_data);
        return $requested_data;
    } else {
        $response = new Response(true, "BAD_REQUEST_TO_DB");
    }

    return $response;
}

if (check_token($conf, $data)) {
    if ($data['type'] === GET_PROJECT_LIST) {

        $response = get_project_list($conf, $data);

        header("Access-Control-Allow-Headers: X-Requested-With, content-type");
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    } elseif ($data['type'] === CREATE_NEW_PROJECT) {
        header('Content-Type: application/json');
        $response = create_new_project($conf, $data);
        echo json_encode($response);
        exit();
    }
}else{
    http_response_code(401);
    echo new Response(true, MUST_BE_AUTHORIZED);
}

exit();