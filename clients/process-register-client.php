<?php

require_once("../config/auth.php");
infinitia_session_start();
require_once("../config/database.php");
require_once("../config/google-oauth.php");

function client_registration_redirect_error($message)
{
    $_SESSION["error"] = $message;
    header("Location: " . app_url("inscription/client"));
    exit();
}

if($_SERVER["REQUEST_METHOD"] != "POST"){
    client_registration_redirect_error("Acces refuse.");
}

$csrf_token = isset($_POST["csrf_token"]) ? $_POST["csrf_token"] : "";

if(!infinitia_consume_csrf_token("register_client_csrf", $csrf_token)){
    client_registration_redirect_error("La demande a expire. Veuillez reessayer.");
}

$google_onboarding_token = isset($_POST["google_onboarding_token"])
    ? trim($_POST["google_onboarding_token"])
    : "";
$google_pending = false;
$google_identity = array();
$is_google_registration = false;

if($google_onboarding_token != ""){
    $google_pending = infinitia_google_pending_registration(
        "register_client",
        $google_onboarding_token
    );

    if($google_pending === false || !isset($google_pending["identity"])){
        client_registration_redirect_error("La validation Google a expire. Veuillez recommencer.");
    }

    $google_identity = $google_pending["identity"];
    $is_google_registration = true;
}

$first_name = isset($_POST["first_name"]) ? trim($_POST["first_name"]) : "";
$last_name = isset($_POST["last_name"]) ? trim($_POST["last_name"]) : "";
$email = $is_google_registration
    ? $google_identity["email"]
    : (isset($_POST["email"]) ? trim($_POST["email"]) : "");
$phone = isset($_POST["phone"]) ? trim($_POST["phone"]) : "";
$password = isset($_POST["password"]) ? $_POST["password"] : "";
$confirm_password = isset($_POST["confirm_password"]) ? $_POST["confirm_password"] : "";
$client_type = isset($_POST["client_type"]) ? trim($_POST["client_type"]) : "";
$company_name = isset($_POST["company_name"]) ? trim($_POST["company_name"]) : "";
$address = isset($_POST["address"]) ? trim($_POST["address"]) : "";
$city = isset($_POST["city"]) ? trim($_POST["city"]) : "";
$gps_location = isset($_POST["gps_location"]) ? trim($_POST["gps_location"]) : "";

if($first_name == "" || $last_name == "" || $email == "" || $phone == "" ||
   (!$is_google_registration && ($password == "" || $confirm_password == "")) ||
   $client_type == "" ||
   $address == "" || $city == ""){
    client_registration_redirect_error("Tous les champs obligatoires doivent etre remplis.");
}

if(strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)){
    client_registration_redirect_error("Adresse email invalide.");
}

if(!in_array($client_type, array("individual", "company", "expatriate"), true)){
    client_registration_redirect_error("Type de client invalide.");
}

if(!$is_google_registration){
    if($password !== $confirm_password){
        client_registration_redirect_error("Les mots de passe ne correspondent pas.");
    }

    $password_error = infinitia_validate_password_strength($password);

    if($password_error != ""){
        client_registration_redirect_error($password_error);
    }
}

$sql = "SELECT id FROM users WHERE email = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){
    error_log("Client registration email lookup prepare error: " . mysqli_error($conn));
    client_registration_redirect_error("Une erreur est survenue. Veuillez reessayer.");
}

mysqli_stmt_bind_param($stmt, "s", $email);

if(!mysqli_stmt_execute($stmt)){
    error_log("Client registration email lookup execute error: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    client_registration_redirect_error("Une erreur est survenue. Veuillez reessayer.");
}

mysqli_stmt_store_result($stmt);
$email_exists = mysqli_stmt_num_rows($stmt) > 0;
mysqli_stmt_close($stmt);

if($email_exists){
    client_registration_redirect_error("Cette adresse email existe deja.");
}

$profile_photo = "";
$uploaded_file_path = "";

if(isset($_FILES["profile_photo"]) && (int)$_FILES["profile_photo"]["error"] !== UPLOAD_ERR_NO_FILE){
    $validation = infinitia_validate_profile_image_upload(
        $_FILES["profile_photo"],
        array("image/jpeg", "image/png"),
        5 * 1024 * 1024
    );

    if(!$validation["valid"]){
        client_registration_redirect_error($validation["error"]);
    }

    $upload_dir = "../uploads/profiles/";

    if(!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)){
        error_log("Client registration profile upload directory creation failed.");
        client_registration_redirect_error("Impossible de televerser la photo.");
    }

    $random_name = infinitia_secure_random_hex(16);

    if($random_name === false){
        client_registration_redirect_error("Impossible de televerser la photo.");
    }

    $profile_photo = $random_name . "." . $validation["extension"];
    $uploaded_file_path = $upload_dir . $profile_photo;

    if(!move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $uploaded_file_path)){
        error_log("Client registration profile photo move failed.");
        client_registration_redirect_error("Impossible de televerser la photo.");
    }
}

$password_hash = NULL;

if(!$is_google_registration){
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    if($password_hash === false){
        if($uploaded_file_path != "" && is_file($uploaded_file_path)){
            unlink($uploaded_file_path);
        }

        error_log("Client registration password hashing failed.");
        client_registration_redirect_error("Une erreur est survenue. Veuillez reessayer.");
    }
}

$role_id = 2;
$status = "active";
$user_id = 0;
$transaction_ok = true;
$stmt_user = false;
$stmt_client = false;

mysqli_autocommit($conn, false);

$sql = "
INSERT INTO users(role_id, first_name, last_name, email, phone, password, profile_photo, status)
VALUES(?, ?, ?, ?, ?, ?, ?, ?)
";
$stmt_user = mysqli_prepare($conn, $sql);

if(!$stmt_user){
    error_log("Client registration user insert prepare error: " . mysqli_error($conn));
    $transaction_ok = false;
}

if($transaction_ok){
    mysqli_stmt_bind_param(
        $stmt_user,
        "isssssss",
        $role_id,
        $first_name,
        $last_name,
        $email,
        $phone,
        $password_hash,
        $profile_photo,
        $status
    );

    if(!mysqli_stmt_execute($stmt_user)){
        error_log("Client registration user insert execute error: " . mysqli_stmt_error($stmt_user));
        $transaction_ok = false;
    }else{
        $user_id = mysqli_insert_id($conn);
    }
}

if($stmt_user){
    mysqli_stmt_close($stmt_user);
}

if($transaction_ok && $user_id <= 0){
    error_log("Client registration user insert returned an invalid identifier.");
    $transaction_ok = false;
}

if($client_type != "company"){
    $company_name = NULL;
}

if($transaction_ok){
    $sql = "
    INSERT INTO clients(user_id, client_type, company_name, address, city, gps_location)
    VALUES(?, ?, ?, ?, ?, ?)
    ";
    $stmt_client = mysqli_prepare($conn, $sql);

    if(!$stmt_client){
        error_log("Client registration profile insert prepare error: " . mysqli_error($conn));
        $transaction_ok = false;
    }
}

if($transaction_ok){
    mysqli_stmt_bind_param(
        $stmt_client,
        "isssss",
        $user_id,
        $client_type,
        $company_name,
        $address,
        $city,
        $gps_location
    );

    if(!mysqli_stmt_execute($stmt_client)){
        error_log("Client registration profile insert execute error: " . mysqli_stmt_error($stmt_client));
        $transaction_ok = false;
    }
}

if($stmt_client){
    mysqli_stmt_close($stmt_client);
}

if($transaction_ok && $is_google_registration){
    if(!infinitia_google_insert_provider($conn, $user_id, $google_identity)){
        $transaction_ok = false;
    }
}

if($transaction_ok){
    mysqli_commit($conn);
    mysqli_autocommit($conn, true);

    if($is_google_registration){
        infinitia_google_clear_pending_identity();
        infinitia_apply_user_session(array(
            "id" => $user_id,
            "role_id" => $role_id,
            "first_name" => $first_name,
            "last_name" => $last_name,
            "email" => $email
        ));
        header("Location: " . app_url("client/tableau-de-bord"));
        exit();
    }

    $_SESSION["success"] = "Compte cree avec succes. Vous pouvez maintenant vous connecter.";
    header("Location: " . app_url("login"));
    exit();
}

mysqli_rollback($conn);
mysqli_autocommit($conn, true);

if($uploaded_file_path != "" && is_file($uploaded_file_path)){
    unlink($uploaded_file_path);
}

client_registration_redirect_error("Une erreur est survenue pendant la creation du compte. Veuillez reessayer.");

?>
