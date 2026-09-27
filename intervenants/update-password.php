<?php

require_once("../config/auth.php");
infinitia_session_start();
require_once("../config/database.php");

function candidate_password_redirect($message, $success)
{
    $_SESSION[$success ? "success" : "error"] = $message;
    header("Location: " . app_url("intervenant/profil"));
    exit();
}

if($_SERVER["REQUEST_METHOD"] != "POST"){
    candidate_password_redirect("Acces refuse.", false);
}

if(!isset($_SESSION["user_id"]) || !isset($_SESSION["role_id"]) || (int)$_SESSION["role_id"] !== 3){
    header("Location: " . app_url("login"));
    exit();
}

$csrf_token = isset($_POST["csrf_token"]) ? $_POST["csrf_token"] : "";

if(!infinitia_consume_csrf_token("candidate_password_csrf", $csrf_token)){
    candidate_password_redirect("La session du formulaire a expire. Veuillez reessayer.", false);
}

$user_id = (int)$_SESSION["user_id"];
$current_password = isset($_POST["current_password"]) ? $_POST["current_password"] : "";
$new_password = isset($_POST["new_password"]) ? $_POST["new_password"] : "";
$confirm_password = isset($_POST["confirm_password"]) ? $_POST["confirm_password"] : "";

if($current_password == "" || $new_password == "" || $confirm_password == ""){
    candidate_password_redirect("Veuillez remplir tous les champs.", false);
}

if($new_password != $confirm_password){
    candidate_password_redirect("Les mots de passe ne correspondent pas.", false);
}

$password_error = infinitia_validate_password_strength($new_password);

if($password_error != ""){
    candidate_password_redirect($password_error, false);
}

$password_hash_current = "";
$sql = "SELECT password FROM users WHERE id = ? AND role_id = 3 LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){
    error_log("Candidate password lookup prepare error: " . mysqli_error($conn));
    candidate_password_redirect("Une erreur est survenue. Veuillez reessayer.", false);
}

mysqli_stmt_bind_param($stmt, "i", $user_id);

if(!mysqli_stmt_execute($stmt)){
    error_log("Candidate password lookup execute error: " . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    candidate_password_redirect("Une erreur est survenue. Veuillez reessayer.", false);
}

mysqli_stmt_bind_result($stmt, $password_hash_current);
$found = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if(!$found
    || $password_hash_current === NULL
    || $password_hash_current === ""
    || !password_verify($current_password, $password_hash_current)){
    candidate_password_redirect("Mot de passe actuel incorrect.", false);
}

$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

if($password_hash === false){
    error_log("Candidate password hashing failed.");
    candidate_password_redirect("Une erreur est survenue. Veuillez reessayer.", false);
}

$sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ? AND role_id = 3";
$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){
    error_log("Candidate password update prepare error: " . mysqli_error($conn));
    candidate_password_redirect("Une erreur est survenue. Veuillez reessayer.", false);
}

mysqli_stmt_bind_param($stmt, "si", $password_hash, $user_id);
$updated = mysqli_stmt_execute($stmt);

if(!$updated){
    error_log("Candidate password update execute error: " . mysqli_stmt_error($stmt));
}

mysqli_stmt_close($stmt);

if(!$updated){
    candidate_password_redirect("Une erreur est survenue. Veuillez reessayer.", false);
}

infinitia_delete_user_tokens($conn, $user_id);
candidate_password_redirect("Mot de passe modifie avec succes.", true);

?>
