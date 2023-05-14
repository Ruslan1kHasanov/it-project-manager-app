<?php

require_once "db_functions.php";

// fix it
global $conf;

try {
    $pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;charset=$conf->charset", $conf->user, $conf->password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $exception) {

//    log it
    echo $exception;
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

        $requested_query = $pdo->prepare('
            select * from Notes
            where Notes.id_component in (
                select Components.id_component from Components
                where Components.id_project = ?
            )'
        );
        $requested_query->execute([$data['proj_id']]);
        $requested_data = $requested_query->fetchAll(PDO::FETCH_ASSOC);
        $proj_notes = $requested_data;

        $proj_note_contributors_list[] = select_note_contributors_list($data['proj_id']);

        for ($i = 0; $i < sizeof($proj_notes); $i++) {
            for ($j = 0; $j < sizeof($proj_note_contributors_list[0]); $j++) {
                if ($proj_note_contributors_list[0][$j]['id_note'] === $proj_notes[$i]['id_note']) {
                    $proj_notes[$i]['developers_array'][] = $proj_note_contributors_list[0][$j];
                }
            }
        }

        $response_data = [
            "column_list" => $proj_columns_list,
            "contributors_list" => $proj_contributors_list,
            "notes_list" => $proj_notes
        ];

        return new Response(false, "REQUEST_DONE", json_encode($response_data));

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

function add_note($data): Response
{
    global $pdo;

    try {
        if (is_user_belongs_to_project($data['creator_email'], $data['proj_id'])) {
            $requested_query = $pdo->prepare('
                insert into Notes (id_component, sub_project_name, creator_email, short_text, full_text, date_of_creating, date_of_deadline, priority)
                values(?, ?, ?, ?, ?, ?, ?, ?);
            ');

            $requested_query->execute([$data['id_component'], $data['sub_project_name'], $data['creator_email'],
                $data['short_text'], $data['full_text'], date('Y-m-d'), $data['date_of_creating'], $data['priority']]);

//            select last (current) note id
            $requested_query = $pdo->prepare('
                select max(id_note) as id_note from Notes;
            ');
            $requested_query->execute();

            $requested_data = $requested_query->fetch(PDO::FETCH_ASSOC);
            $is_attached = attach_users_to_note($data, $requested_data['id_note']);

            if ($is_attached->error) {
                return $is_attached;
            }

            return new Response(false, "NOTE_WERE_CREATED",);
        }
        return new Response(true, "USER_NOT_BELONGS_TO_CURRENT_PROJECT");
    } catch (Exception $exception) {
        return new Response(true, $exception);
    }
}

function attach_users_to_note($data, $id_note): Response
{
    global $pdo;

    $inserted_contrib_email = [];
    $wrong_contrib_email = [];

    $is_inserting_contributor_error = false;

    for ($i = 0; $i < sizeof($data['contributors_email']); $i++) {
        if (is_user_belongs_to_project($data['contributors_email'][$i], $data['proj_id'])) {
            $inserted_contrib_email[] = $data['contributors_email'][$i];
        } else {
            $is_inserting_contributor_error = true;
            $wrong_contrib_email[] = $data['contributors_email'][$i];
        }
    }

    if ($is_inserting_contributor_error) {
        return new Response(true, "CONTRIBUTORS_NOT_BELONG_TO_PROJ_ERROR", json_encode($wrong_contrib_email));
    }

    $multiply_sql_insert_string = '';
    foreach ($inserted_contrib_email as $email) {
        $multiply_sql_insert_string = $multiply_sql_insert_string . '(' . '"' . $email . '"' . ', ' . $id_note . '),';
    }
    $multiply_sql_insert_string = rtrim($multiply_sql_insert_string, ',');
    $multiply_sql_insert_string = 'insert into Contributors_task_list (contributor_email, id_note) values ' . $multiply_sql_insert_string;

    try {
        $pdo->prepare($multiply_sql_insert_string)->execute();
        return new Response(false, "tmp");
    } catch (Exception $exception) {
        return new Response(true, $exception);
    }
}

