<?php

require_once 'config.php';

date_default_timezone_set('UTC');

$conf = new Config();

$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, JSON_UNESCAPED_UNICODE);

function create_new_token($conf, $data): string
{
    $pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;cahrset=$conf->charset", $conf->user, $conf->password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $requested_query = $pdo->prepare('
        insert into Tokens
        values (?, ?);'
    );

    $new_token = hash('sha256', $data['email'] . $data['password'] . date('d-m-Y-H-i-s'));

    $requested_query->execute([$new_token, date('Y-m-d')]);

    return $new_token;
}

function is_token_actual($conf, $token): bool
{
    $pdo = new PDO("mysql:host=$conf->host;dbname=$conf->db;cahrset=$conf->charset", $conf->user, $conf->password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $requested_query = $pdo->prepare('
        select token, date_of_creating from Tokens
        where token = ?;
    '
    );
    $requested_query->execute([$token]);

    $requested_data = $requested_query->fetch(PDO::FETCH_ASSOC);

    if (isset($requested_data['date_of_creating']) && $requested_data['date_of_creating'] == date('Y-m-d')) {
        return true;
    } else {
        return false;
    }
}

function check_token($conf, $data) : bool
{
    if (isset($_COOKIE['token']) && is_token_actual($conf, $_COOKIE['token'])) {
        return true;
    } else {
        return false;
    }
}
