<?php

require_once(dirname(dirname(__DIR__)) . "/config/google-oauth.php");
infinitia_session_start();

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    $_SESSION["error"] = "Choix de compte Google invalide.";
    header("Location: " . app_url("login"));
    exit();
}

$csrf_token = isset($_POST["csrf_token"]) ? $_POST["csrf_token"] : "";

if(!infinitia_consume_csrf_token("google_role_choice_csrf", $csrf_token)){
    $_SESSION["error"] = "Le choix du compte Google a expire.";
    header("Location: " . app_url("login"));
    exit();
}

$account_type = isset($_POST["account_type"]) ? (string)$_POST["account_type"] : "";
$intent = $account_type === "client"
    ? "register_client"
    : ($account_type === "candidate" ? "register_candidate" : "");

if($intent === "" || !infinitia_google_update_pending_intent($intent)){
    $_SESSION["error"] = "Le type de compte Google selectionne est invalide.";
    header("Location: " . app_url("login"));
    exit();
}

header("Location: " . app_url(infinitia_google_return_path($intent)));
exit();

?>
