<?php

require_once(dirname(dirname(__DIR__)) . "/config/google-oauth.php");
infinitia_session_start();

$intent = isset($_POST["intent"]) ? (string)$_POST["intent"] : "login";
$return_path = infinitia_google_return_path($intent);

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    $_SESSION["error"] = "Le demarrage de Google doit etre confirme depuis INFINITIA.";
    header("Location: " . app_url($return_path));
    exit();
}

$csrf_token = isset($_POST["csrf_token"]) ? $_POST["csrf_token"] : "";

if(!infinitia_consume_csrf_token("google_oauth_start_csrf", $csrf_token)){
    $_SESSION["error"] = "La demande Google a expire. Veuillez reessayer.";
    header("Location: " . app_url($return_path));
    exit();
}

if(isset($_SESSION["role_id"]) && (int)$_SESSION["role_id"] === 1){
    $_SESSION["error"] = "La connexion Google n'est pas autorisee pour les administrateurs.";
    header("Location: " . app_url("admin/tableau-de-bord"));
    exit();
}

$transaction = infinitia_google_begin_transaction($intent);

if(!$transaction["success"]){
    $_SESSION["error"] = $transaction["error"];
    header("Location: " . app_url($return_path));
    exit();
}

header("Location: " . $transaction["authorization_url"]);
exit();

?>
