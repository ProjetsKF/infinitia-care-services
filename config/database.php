<?php

require_once(__DIR__ . "/app.php");

$http_host = isset($_SERVER["HTTP_HOST"])
    ? strtolower($_SERVER["HTTP_HOST"])
    : "";

$is_local =
    strpos($http_host, "localhost") !== false ||
    strpos($http_host, "127.0.0.1") !== false;

if($is_local){

    // Configuration locale
    $host = "localhost";
    $database = "infinitia_care_services";
    $user = "root";
    $password = "";

}else{

    // Configuration production
    $host = "localhost";
    $database = "infiniti_infinitia_care_services";
    $user = "infiniti_francky_sabiti";
    $password = "0994699173Francky";

}

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if($conn->connect_error){

    error_log(
        "Erreur connexion MySQL : " .
        $conn->connect_error
    );

    die("Impossible de se connecter à la base de données.");

}

$conn->set_charset("utf8");

?>