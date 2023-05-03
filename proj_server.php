<?php

require_once './Entities/Response.php';
require_once 'config.php';
require_once './tested.php';
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

function is_user_project_exists($pdo, $data): bool
{
    $requested_query = $pdo->prepare('
        select proj_name, creator_email from Projects
        where proj_name = ? and creator_email = ?;'
    );
    $requested_query->execute([$data['proj_name'], $data['creator_email']]);

    $requested_data = $requested_query->fetch(PDO::FETCH_ASSOC);
//    print_r($requested_data);
    if (gettype($requested_data) === "boolean") {
        return false;
    }
    return true;
}

function create_new_project($conf, $data)
{
    try {
        $pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;cahrset=$conf->charset", $conf->user, $conf->password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (is_user_project_exists($pdo, $data)) {
            return new Response(true, "PROJECT_ALREADY_EXISTS");
        }

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
        $response_project_id = $requested_data['id_project'];
        $response_project_name = $data['proj_name'];

        $requested_query->execute([$requested_data['id_project'], $requested_data['creator_email'], 1]);

        if (isset($response_project_id)) {
            return ["id_project" => $response_project_id, "proj_name" => $response_project_name];
        } else {
            return new Response(true, "BAD_REQUEST_TO_DB");
        }
    } catch (Exception $e) {
        return new Response(true, $e);
    }

}

function get_project_data($conf, $data)
{
    try {
        $pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;cahrset=$conf->charset", $conf->user, $conf->password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $requested_query = $pdo->prepare('
            select id_component, component_name from components
            where id_project = ?;'
        );
        $requested_query->execute([$data['proj_id']]);

        $requested_data = $requested_query->fetchAll(PDO::FETCH_ASSOC);

        if (array_key_exists(0, $requested_data)) {
            return ["column_list" => $requested_data];
        } else {
            return new Response(true, "BAD_REQUEST_TO_DB");
        }

    } catch (Exception $e) {
        return new Response(true, $e);
    }
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
        return $requested_data;
    } else {
        $response = new Response(true, "BAD_REQUEST_TO_DB");
    }

    return $response;
}

function create_new_column($conf, $data)
{
    try {
        $pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;cahrset=$conf->charset", $conf->user, $conf->password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $requested_query = $pdo->prepare('
            insert into components (component_name, id_project)
            values(?, ?);
        ');
        $requested_query->execute([$data['component_name'], $data['proj_id']]);

        $requested_query = $pdo->prepare('
            select id_component, component_name from components
            where component_name = ? and id_project = ?
        ');
        $requested_query->execute([$data['component_name'], $data['proj_id']]);

        $requested_data = $requested_query->fetch(PDO::FETCH_ASSOC);

        $request_id = $requested_data['id_component'];
        $request_name = $requested_data['component_name'];

        if (isset($request_id)) {
            return ["id_component" => $request_id, "component_name" => $request_name];
        } else {
            return new Response(true, "BAD_REQUEST_TO_DB");
        }

    } catch (Exception $e) {
        return new Response(true, $e);
    }
}

function is_user_exists($conf, $user_email): bool
{
    $pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;cahrset=$conf->charset", $conf->user, $conf->password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $requested_query = $pdo->prepare('
        select email from Users
        where email = ?; 
    ');

    $requested_query->execute([$user_email]);
    $requested_data = $requested_query->fetch(PDO::FETCH_ASSOC);
    return isset($requested_data['email']);
}

function select_user_login_email($conf, $user_email)
{
    $pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;cahrset=$conf->charset", $conf->user, $conf->password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $requested_query = $pdo->prepare('
        select email, login from Users
        where email = ?;
    ');
    $requested_query->execute([$user_email]);
    return $requested_query->fetch(PDO::FETCH_ASSOC);
}

function add_contributor($conf, $data): Response
{
    try {
        $pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;cahrset=$conf->charset", $conf->user, $conf->password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (is_user_exists($conf, $data['contributor_email'])) {
            $requested_query = $pdo->prepare('
                insert into Projects_list
                values(?, ?, ?);
            ');
            $requested_query->execute([$data['proj_id'], $data['contributor_email'], $data['is_admin']]);

            return new Response(false, "USER_WAS_INVITED", json_encode(select_user_login_email($conf, $data['contributor_email'])));
        } else {
            return new Response(true, "USER_IS_NOT_EXIST");
        }
    } catch (Exception $e) {
        return new Response(true, $e);
    }
}

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
        $response = get_project_list($conf, $data);
        break;
    }
    case CREATE_NEW_PROJECT:
    {
        http_response_code(201);
        $response = create_new_project($conf, $data);
        break;
    }
    case GET_PROJECT_DATA:
    {
        http_response_code(200);
        $response = get_project_data($conf, $data);
        break;
    }
    case CREATE_NEW_COLUMN:
    {
        http_response_code(201);
        $response = create_new_column($conf, $data);
        break;
    }
    case INVITE_CONTRIBUTOR:
    {
        http_response_code(201);
        $response = add_contributor($conf, $data);
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