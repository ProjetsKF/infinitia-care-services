<?php

require_once(dirname(dirname(__DIR__)) . "/config/google-oauth.php");
infinitia_session_start();
require_once(dirname(dirname(__DIR__)) . "/config/database.php");

$state = isset($_GET["state"]) ? (string)$_GET["state"] : "";
$transaction = infinitia_google_consume_transaction($state);

if(!$transaction["valid"]){
    $_SESSION["error"] = $transaction["error"];
    header("Location: " . app_url("login"));
    exit();
}

$intent = $transaction["intent"];

if(isset($_GET["error"])){
    infinitia_google_fail_and_redirect($intent, "La connexion Google a ete annulee ou refusee.");
}

$code = isset($_GET["code"]) ? (string)$_GET["code"] : "";
$config = infinitia_google_config();
$config_error = infinitia_google_config_error($config);

if($config_error !== ""){
    infinitia_google_fail_and_redirect($intent, $config_error);
}

$exchange = infinitia_google_exchange_code($code, $transaction["code_verifier"], $config);

if(!$exchange["success"]){
    infinitia_google_fail_and_redirect($intent, $exchange["error"]);
}

$identity = infinitia_google_verify_id_token(
    $exchange["id_token"],
    $transaction["nonce"],
    $config["client_id"]
);
unset($exchange);

if(!$identity["valid"]){
    error_log("Google ID token validation failed without logging token contents.");
    infinitia_google_fail_and_redirect($intent, $identity["error"]);
}

$linked_user = infinitia_google_find_provider_user($conn, $identity["sub"]);

if($linked_user !== false){
    $decision = infinitia_google_existing_user_decision($linked_user, $intent);

    if(!$decision["allowed"]){
        infinitia_google_fail_and_redirect($intent, $decision["message"]);
    }

    infinitia_google_clear_pending_identity();
    infinitia_apply_user_session($linked_user);
    infinitia_google_update_last_login($conn, (int)$linked_user["id"]);
    infinitia_redirect_by_role((int)$linked_user["role_id"]);
}

$email_user = infinitia_google_find_email_user($conn, $identity["email"]);

if($email_user !== false){
    infinitia_google_clear_pending_identity();
    $_SESSION["error"] = "Un compte INFINITIA utilise deja cette adresse e-mail. Connectez-vous avec votre methode actuelle. La liaison Google devra etre confirmee depuis un compte deja authentifie.";
    header("Location: " . app_url("login"));
    exit();
}

if(!infinitia_google_set_pending_identity($identity, $intent)){
    infinitia_google_fail_and_redirect($intent, "Impossible de preparer votre parcours Google.");
}

if($intent === "login"){
    header("Location: " . app_url("auth/google/choose-account.php"));
    exit();
}

header("Location: " . app_url(infinitia_google_return_path($intent)));
exit();

?>
