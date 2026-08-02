<?php

session_start();

require_once("../config/database.php");

$user_id = $_SESSION['user_id'];

$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

$sql = "SELECT password FROM users WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

if(
    !password_verify(
        $current_password,
        $user['password']
    )
){

    $_SESSION['error'] =
    "Mot de passe actuel incorrect.";

    header("Location: " . app_url("intervenant/profil"));
    exit();

}

if($new_password != $confirm_password){

    $_SESSION['error'] =
    "Les mots de passe ne correspondent pas.";

    header("Location: " . app_url("intervenant/profil"));
    exit();

}

if(strlen($new_password) < 6){

    $_SESSION['error'] =
    "Le mot de passe doit contenir au moins 6 caractères.";

    header("Location: " . app_url("intervenant/profil"));
    exit();

}

$password_hash =
password_hash(
    $new_password,
    PASSWORD_DEFAULT
);

$sql = "
UPDATE users
SET password = ?
WHERE id = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $password_hash,
    $user_id
);

mysqli_stmt_execute($stmt);

$_SESSION['success'] =
"Mot de passe modifié avec succès.";

header("Location: " . app_url("intervenant/profil"));
exit();

?>