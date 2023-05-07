<?php

// fix it
global $conf;

$pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;charset=$conf->charset", $conf->user, $conf->password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function is_user_project_exists($data): bool
{
    global $pdo;

    $requested_query = $pdo->prepare('
        select proj_name, creator_email from Projects
        where proj_name = ? and creator_email = ?;'
    );
    $requested_query->execute([$data['proj_name'], $data['creator_email']]);
    $requested_data = $requested_query->fetch(PDO::FETCH_ASSOC);

    if (gettype($requested_data) === "boolean") {
        return false;
    }
    return true;
}

function create_new_project($data)
{
    global $pdo;

    try {
        if (is_user_project_exists($data)) {
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

function get_project_data($data): Response
{
    global $pdo;

    try {
        $requested_query = $pdo->prepare('
            select id_component, component_name from Components
            where id_project = ?;'
        );
        $requested_query->execute([$data['proj_id']]);

        $requested_data = $requested_query->fetchAll(PDO::FETCH_ASSOC);

        $proj_columns_list = $requested_data;

        $requested_query = $pdo->prepare('
            select contributor_email, Users.login
            from Projects_list left join Users on Projects_list.contributor_email = Users.email
            where Projects_list.id_project = ?;'
        );
        $requested_query->execute([$data['proj_id']]);

        $requested_data = $requested_query->fetchAll(PDO::FETCH_ASSOC);

        $proj_contributors_list = $requested_data;

        $response_data = ["column_list" => $proj_columns_list, "contributors_list" => $proj_contributors_list];

        if (array_key_exists(0, $requested_data)) {
            return new Response(false, "REQUEST_DONE", json_encode($response_data));
        } else {
            return new Response(true, "BAD_REQUEST_TO_DB");
        }

    } catch (Exception $e) {
        return new Response(true, $e);
    }
}

function get_project_list($data)
{
    global $pdo;

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

function create_new_column($data)
{
    global $pdo;

    try {
        $requested_query = $pdo->prepare('
            insert into Components (component_name, id_project)
            values(?, ?);
        ');
        $requested_query->execute([$data['component_name'], $data['proj_id']]);

        $requested_query = $pdo->prepare('
            select id_component, component_name from Components
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

function is_user_exists($user_email): bool
{
    global $pdo;

    $requested_query = $pdo->prepare('
        select email from Users
        where email = ?; 
    ');

    $requested_query->execute([$user_email]);
    $requested_data = $requested_query->fetch(PDO::FETCH_ASSOC);
    return isset($requested_data['email']);
}

function select_user_login_by_email($user_email)
{
    global $pdo;

    $requested_query = $pdo->prepare('
        select email, login from Users
        where email = ?;
    ');
    $requested_query->execute([$user_email]);
    return $requested_query->fetch(PDO::FETCH_ASSOC);
}

function add_contributor($data): Response
{
    global $pdo;

    try {
        if (is_user_exists($data['contributor_email'])) {
            $requested_query = $pdo->prepare('
                insert into Projects_list
                values(?, ?, ?);
            ');
            $requested_query->execute([$data['proj_id'], $data['contributor_email'], $data['is_admin']]);

            return new Response(false, "USER_WAS_INVITED", json_encode(select_user_login_by_email($data['contributor_email'])));
        } else {
            return new Response(true, "USER_IS_NOT_EXIST");
        }
    } catch (Exception $e) {
        return new Response(true, $e);
    }
}

//function