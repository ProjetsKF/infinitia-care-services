<?php

if(!function_exists("app_base_url")){

    function app_base_url()
    {
        $host = isset($_SERVER["HTTP_HOST"])
            ? strtolower($_SERVER["HTTP_HOST"])
            : "";

        if(strpos($host, "127.0.0.1") !== false || strpos($host, "localhost") !== false){
            return "/infinitia-group-sarlu";
        }

        return "";
    }

}

if(!function_exists("app_config_value")){

    function app_config_value($key, $default)
    {
        static $local_config = NULL;

        if($local_config === NULL){
            $local_config = array();
            $local_file = __DIR__ . "/local.php";

            if(file_exists($local_file)){
                $loaded = require($local_file);

                if(is_array($loaded)){
                    $local_config = $loaded;
                }
            }
        }

        $environment_key = "INFINITIA_" . strtoupper($key);
        $environment_value = getenv($environment_key);

        if($environment_value !== false && $environment_value !== ""){
            return $environment_value;
        }

        if(isset($local_config[$key]) && $local_config[$key] !== ""){
            return $local_config[$key];
        }

        return $default;
    }

}

if(!function_exists("app_origin")){

    function app_origin()
    {
        $configured_origin = rtrim((string)app_config_value("app_origin", ""), "/");

        if($configured_origin !== ""){
            return $configured_origin;
        }

        $host = isset($_SERVER["HTTP_HOST"]) ? strtolower($_SERVER["HTTP_HOST"]) : "";

        if($host === "localhost" || strpos($host, "localhost:") === 0 ||
           $host === "127.0.0.1" || strpos($host, "127.0.0.1:") === 0){
            $https = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "" && $_SERVER["HTTPS"] !== "off";
            return ($https ? "https" : "http") . "://" . $host;
        }

        return "https://infinitia-group.com";
    }

}

if(!function_exists("app_url")){

    function app_url($path)
    {
        $base = app_base_url();
        $path = ltrim((string)$path, "/");
        $local_prefix = "infinitia-group-sarlu";

        if($path === $local_prefix){
            $path = "";
        }elseif(strpos($path, $local_prefix . "/") === 0){
            $path = substr($path, strlen($local_prefix) + 1);
        }

        if($path === ""){
            return $base . "/";
        }

        return $base . "/" . $path;
    }

}

if(!function_exists("app_absolute_url")){

    function app_absolute_url($path)
    {
        $origin = app_origin();

        if($origin === ""){
            return "";
        }

        return $origin . app_url($path);
    }

}

if(!function_exists("app_url_with_query")){

    function app_url_with_query($path, $params)
    {
        $url = app_url($path);

        if(is_array($params) && count($params) > 0){
            $url .= "?" . http_build_query($params);
        }

        return $url;
    }

}

if(!function_exists("app_url_html")){

    function app_url_html($path)
    {
        $url = app_url($path);
        $asset_path = ltrim((string)$path, "/");

        if(strpos($asset_path, "assets/") === 0 && strpos($asset_path, "..") === false){
            $query_position = strpos($asset_path, "?");

            if($query_position !== false){
                $asset_path = substr($asset_path, 0, $query_position);
            }

            $local_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(
                "/",
                DIRECTORY_SEPARATOR,
                $asset_path
            );

            if(is_file($local_path)){
                $modified_at = filemtime($local_path);

                if($modified_at !== false){
                    $url .= (strpos($url, "?") === false ? "?" : "&") . "v=" . $modified_at;
                }
            }
        }

        return htmlspecialchars($url, ENT_QUOTES, "UTF-8");
    }

}

if(!function_exists("app_url_with_query_html")){

    function app_url_with_query_html($path, $params)
    {
        return htmlspecialchars(app_url_with_query($path, $params), ENT_QUOTES, "UTF-8");
    }

}

?>
