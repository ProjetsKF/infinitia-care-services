<?php

if(!function_exists("admin_delete_require_admin")){

    function admin_delete_require_admin()
    {
        if(!isset($_SESSION["user_id"]) || !isset($_SESSION["role_id"]) || (int)$_SESSION["role_id"] !== 1){
            header("Location: " . app_url("login"));
            exit();
        }
    }

}

if(!function_exists("admin_delete_csrf_token")){

    function admin_delete_csrf_token()
    {
        if(!isset($_SESSION["admin_delete_csrf"]) || !is_string($_SESSION["admin_delete_csrf"]) || $_SESSION["admin_delete_csrf"] === ""){
            $random = openssl_random_pseudo_bytes(32);

            if($random === false){
                $random = uniqid((string)mt_rand(), true);
            }

            $_SESSION["admin_delete_csrf"] = hash("sha256", $random);
        }

        return $_SESSION["admin_delete_csrf"];
    }

}

if(!function_exists("admin_delete_validate_csrf")){

    function admin_delete_validate_csrf($token)
    {
        $session_token = isset($_SESSION["admin_delete_csrf"])
            ? $_SESSION["admin_delete_csrf"]
            : "";
        $valid = is_string($token) && $token !== "" && is_string($session_token) &&
            $session_token !== "" && hash_equals($session_token, $token);

        if($valid){
            unset($_SESSION["admin_delete_csrf"]);
        }

        return $valid;
    }

}

if(!function_exists("admin_delete_count")){

    function admin_delete_count($conn, $sql, $id)
    {
        $total = -1;
        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){
            error_log("Erreur de préparation suppression admin : " . mysqli_error($conn));
            return -1;
        }

        mysqli_stmt_bind_param($stmt, "i", $id);

        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_bind_result($stmt, $total);
            mysqli_stmt_fetch($stmt);
        }else{
            error_log("Erreur de vérification suppression admin : " . mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);
        return (int)$total;
    }

}

if(!function_exists("admin_delete_execute")){

    function admin_delete_execute($conn, $sql, $id)
    {
        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){
            error_log("Erreur de préparation DELETE admin : " . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "i", $id);
        $success = mysqli_stmt_execute($stmt);

        if(!$success){
            error_log("Erreur DELETE admin : " . mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);
        return $success;
    }

}

?>
