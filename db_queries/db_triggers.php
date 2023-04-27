<?php

$user = 'root';
$password = 'DansonU206';
$db = 'chatted_db';
$host = 'localhost';
$charset = 'utf8';

function drop_triggers($pdo){
    $pdo->query('drop table if exists Notes');
}

$pdo = new PDO("mysql:host=$host;dbname=$db;cahrset=$charset", $user, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// drop_triggers($pdo);

// тестовый триггер (возникла проблема из-за архитектуры БД)

$query = $pdo->query('
    create trigger if not exists insert_new_project after insert on Projects
    for each row begins
        insert into Projects_list values(NEW.id_project, NEW)
    ');
