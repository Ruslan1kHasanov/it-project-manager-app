<?php

$user = 'root';
$password = 'DansonU206';
$db = 'chatted_db';
$host = 'localhost';
$charset = 'utf8';

function drop_tables($pdo){
    $pdo->query('drop table if exists Notes');
    $pdo->query('drop table if exists Components');
    $pdo->query('drop table if exists Projects_list');
    $pdo->query('drop table if exists Projects');
    $pdo->query('drop table if exists Users');
}

$pdo = new PDO("mysql:host=$host;dbname=$db;cahrset=$charset", $user, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

drop_tables($pdo);

$query = $pdo->query('
    CREATE TABLE  IF NOT EXISTS Users (
        email varchar(255),
        login varchar(127) NOT NULL,
        password varchar(32) NOT NULL,
        PRIMARY KEY(email)
)');

$query = $pdo->query('
    CREATE TABLE  IF NOT EXISTS Projects (
        id_project integer AUTO_INCREMENT,
        proj_name varchar(63) NOT NULL,
        creator_email varchar(255) NOT NULL,
        date_of_creating DATE NOT NULL,
        PRIMARY KEY(id_project)
)');

$query = $pdo->query('
    CREATE TABLE  IF NOT EXISTS Projects_list (
        id_project_list integer,
        id_project integer,
        admins_email varchar(255) NOT NULL,
        contributors_email varchar(255),
        PRIMARY KEY(id_project_list),
        FOREIGN KEY(id_project) REFERENCES Projects(id_project)
)');

$query = $pdo->query('
    CREATE TABLE  IF NOT EXISTS Components (
        id_component integer,
        id_project integer NOT NULL,
        component_name varchar(32) NOT NULL,
        PRIMARY KEY(id_component),
        FOREIGN KEY(id_project) REFERENCES Projects(id_project)
)');

$query = $pdo->query('
    CREATE TABLE  IF NOT EXISTS Notes (
        id_note integer,
        id_component integer NOT NULL,
        sub_project_name varchar(255),
        creator_email varchar(255) NOT NULL,
        short_text text,
        full_text text,
        date_of_creating DATE NOT NULL,
        date_of_deadline DATE,
        contributors_email varchar(255),
        PRIMARY KEY(id_note),
        FOREIGN KEY(contributors_email) REFERENCES Users(email)
)');

//$query = $pdo->query('insert into users values ("ruslan_dopowehko@mail.ru", "Ruslan Khasanov", "Kn9Dm3^b4")');
