<?php

require_once(__DIR__ . "/app.php");

$host = "localhost";
$database = "infinitia_care_services";
$user = "root";
$password = "";

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if($conn->connect_error){

    die(
        "Erreur connexion : " .
        $conn->connect_error
    );

}

$conn->set_charset("utf8");

?>
