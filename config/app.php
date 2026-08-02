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
        return htmlspecialchars(app_url($path), ENT_QUOTES, "UTF-8");
    }

}

if(!function_exists("app_url_with_query_html")){

    function app_url_with_query_html($path, $params)
    {
        return htmlspecialchars(app_url_with_query($path, $params), ENT_QUOTES, "UTF-8");
    }

}

?>
