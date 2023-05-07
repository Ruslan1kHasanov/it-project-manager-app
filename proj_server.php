<?php

require_once './Entities/Response.php';
require_once './tested.php';
require_once  './db_functions/main_functions.php';
require_once 'config.php';

const GET_PROJECT_LIST = "GET_PROJECT_LIST";
const GET_PROJECT_DATA = "GET_PROJECT_DATA";
const CREATE_NEW_PROJECT = "CREATE_NEW_PROJECT";
const MUST_BE_AUTHORIZED = "MUST_BE_AUTHORIZED";
const CREATE_NEW_NOTE = "CREATE_NEW_NOTE";
const CREATE_NEW_COLUMN = "CREATE_NEW_COLUMN";
const INVITE_CONTRIBUTOR = "INVITE_CONTRIBUTOR";

$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, JSON_UNESCAPED_UNICODE);
$conf = new Config();

//if (check_token($conf, $data)) {
//    if ($data['type'] === GET_PROJECT_LIST) {
//
//        $response = get_project_list($conf, $data);
//
//        header("Access-Control-Allow-Headers: X-Requested-With, content-type");
//        header('Content-Type: application/json');
//        echo json_encode($response);
//        exit();
//    } elseif ($data['type'] === CREATE_NEW_PROJECT) {
//        header('Content-Type: application/json');
//        $response = create_new_project($conf, $data);
//        echo json_encode($response);
//        exit();
//    }
//} else {
//    http_response_code(401);
//    echo json_encode(new Response(true, MUST_BE_AUTHORIZED));
//}

switch ($data['type']) {
    case GET_PROJECT_LIST:
    {
        http_response_code(200);
        $response = get_project_list($data);
        break;
    }
    case CREATE_NEW_PROJECT:
    {
        http_response_code(201);
        $response = create_new_project($data);
        break;
    }
    case GET_PROJECT_DATA:
    {
        http_response_code(200);
        $response = get_project_data($data);
        break;
    }
    case CREATE_NEW_COLUMN:
    {
        http_response_code(201);
        $response = create_new_column($data);
        break;
    }
    case INVITE_CONTRIBUTOR:
    {
        http_response_code(201);
        $response = add_contributor($data);
        break;
    }
    case CREATE_NEW_NOTE: {
        http_response_code(201);
//        $response = add_contributor($conf, $data);
        break;
    }
    default:
    {
        http_response_code(400);
        $response = new Response(true, "BAD_REQUEST_TO_SERVER");
        break;
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
exit();