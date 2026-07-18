<?php

session_start();

require_once("config/database.php");
require_once("config/auth.php");

if(isset($_COOKIE["infinitia_remember"])){

    $remember_cookie = infinitia_parse_remember_cookie();

    if($remember_cookie !== false){

        infinitia_delete_token_by_selector(
            $conn,
            $remember_cookie["selector"]
        );

    }

}

if(isset($_SESSION["user_id"])){

    infinitia_delete_user_tokens(
        $conn,
        (int)$_SESSION["user_id"]
    );

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

header("Location: login.php");
exit();

?>
