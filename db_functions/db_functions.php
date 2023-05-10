<?php

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

function is_user_belongs_to_project($user_email, $id_project): bool
{
    global $pdo;

    $requested_query = $pdo->prepare('
        select contributor_email from Projects_list
        where id_project = ?; 
    ');

    $requested_query->execute([$id_project]);
    $requested_data = $requested_query->fetch(PDO::FETCH_ASSOC);
    return isset($requested_data['email']);
}