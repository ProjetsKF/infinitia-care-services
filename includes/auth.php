<?php

require_once(dirname(__DIR__) . "/config/auth.php");

if(!function_exists("infinitia_require_authenticated_role")){

    function infinitia_require_authenticated_role($conn, $allowed_roles)
    {
        if(!isset($_SESSION["user_id"]) || !isset($_SESSION["role_id"])){
            header("Location: " . app_url("login"));
            exit();
        }

        $role_id = (int)$_SESSION["role_id"];

        if(!is_array($allowed_roles) || !in_array($role_id, $allowed_roles, true)){
            header("Location: " . app_url("login"));
            exit();
        }

        $user_id = (int)$_SESSION["user_id"];
        $status = "";
        $sql = "SELECT status FROM users WHERE id = ? AND role_id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){
            error_log("Access control lookup prepare error: " . mysqli_error($conn));
            header("Location: " . app_url("login"));
            exit();
        }

        mysqli_stmt_bind_param($stmt, "ii", $user_id, $role_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $status);
        $found = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if(!$found || $status != "active"){
            $_SESSION = array();
            session_destroy();
            header("Location: " . app_url("login"));
            exit();
        }
    }

}

?>
