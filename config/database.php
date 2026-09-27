<?php

require_once(__DIR__ . "/app.php");

$http_host = isset($_SERVER["HTTP_HOST"])
    ? strtolower($_SERVER["HTTP_HOST"])
    : "";

$is_local_request =
    strpos($http_host, "localhost") !== false ||
    strpos($http_host, "127.0.0.1") !== false;

// Les valeurs de développement ne sont utilisées que pour une requête locale.
// En production, les variables INFINITIA_* ou config/local.php sont obligatoires.
$default_host = $is_local_request ? "localhost" : "";
$default_database = $is_local_request ? "infinitia_care_services" : "";
$default_user = $is_local_request ? "root" : "";
$default_password = "";

$host = app_config_value("db_host", $default_host);
$database = app_config_value("db_name", $default_database);
$user = app_config_value("db_user", $default_user);
$password = app_config_value("db_password", $default_password);

if($host === "" || $database === "" || $user === ""){
    error_log("Configuration de base de donnees incomplete.");
    die("Impossible de se connecter à la base de données.");
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

$conn->set_charset("utf8mb4");

?>
