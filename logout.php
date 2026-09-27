<?php

require_once("config/auth.php");
infinitia_session_start();
require_once("config/database.php");

if($_SERVER["REQUEST_METHOD"] != "POST"){
    if(isset($_SESSION["user_id"]) && isset($_SESSION["role_id"])){
        infinitia_redirect_by_role((int)$_SESSION["role_id"]);
    }

    header("Location: " . app_url("login"));
    exit();
}

$csrf_token = isset($_POST["csrf_token"]) ? $_POST["csrf_token"] : "";

if(!infinitia_consume_csrf_token("logout_csrf", $csrf_token)){
    $_SESSION["error"] = "La demande de deconnexion a expire. Veuillez reessayer.";

    if(isset($_SESSION["role_id"])){
        infinitia_redirect_by_role((int)$_SESSION["role_id"]);
    }

    header("Location: " . app_url("login"));
    exit();
}

if(isset($_COOKIE["infinitia_remember"])){
    $remember_cookie = infinitia_parse_remember_cookie();

    if($remember_cookie !== false){
        infinitia_delete_token_by_selector($conn, $remember_cookie["selector"]);
    }
}

if(isset($_SESSION["user_id"])){
    infinitia_delete_user_tokens($conn, (int)$_SESSION["user_id"]);
}

infinitia_delete_remember_cookie();
$_SESSION = array();

if(ini_get("session.use_cookies")){
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();
header("Location: " . app_url("login"));
exit();

?>
