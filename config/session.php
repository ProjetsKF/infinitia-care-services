<?php

if(!function_exists("infinitia_is_https_request")){

    function infinitia_is_https_request()
    {
        if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "" && $_SERVER["HTTPS"] !== "off"){
            return true;
        }

        return isset($_SERVER["SERVER_PORT"]) && (int)$_SERVER["SERVER_PORT"] === 443;
    }

}

if(!function_exists("infinitia_cookie_path")){

    function infinitia_cookie_path()
    {
        $base_path = function_exists("app_base_url")
            ? trim((string)app_base_url())
            : "";

        if($base_path === "" || $base_path === "/"){
            return "/";
        }

        return "/" . trim($base_path, "/") . "/";
    }

}

if(!function_exists("infinitia_legacy_samesite_cookie_path")){

    function infinitia_legacy_samesite_cookie_path()
    {
        return infinitia_cookie_path() . "; SameSite=Lax";
    }

}

if(!function_exists("infinitia_session_start")){

    function infinitia_session_start()
    {
        if(session_status() === PHP_SESSION_ACTIVE){
            return true;
        }

        ini_set("session.use_only_cookies", "1");
        ini_set("session.use_strict_mode", "1");
        ini_set("session.cookie_httponly", "1");
        ini_set("session.cookie_secure", infinitia_is_https_request() ? "1" : "0");
        ini_set("session.gc_maxlifetime", "7200");

        if(PHP_VERSION_ID >= 70300){
            session_set_cookie_params(array(
                "lifetime" => 0,
                "path" => infinitia_cookie_path(),
                "domain" => "",
                "secure" => infinitia_is_https_request(),
                "httponly" => true,
                "samesite" => "Lax"
            ));
        }else{
            session_set_cookie_params(
                0,
                infinitia_legacy_samesite_cookie_path(),
                "",
                infinitia_is_https_request(),
                true
            );
        }

        return session_start();
    }

}

?>
