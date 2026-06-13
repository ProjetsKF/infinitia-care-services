<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION['user_id'])){

    header("Location: login.php");
    exit();

}

$user_id = $_SESSION['user_id'];

$nationality = trim($_POST['nationality']);
$education_level = trim($_POST['education_level']);
$experience_years = (int)$_POST['experience_years'];
$bio = trim($_POST['bio']);
$availability_status = trim($_POST['availability_status']);

if(
    empty($nationality) ||
    empty($education_level) ||
    empty($bio) ||
    empty($availability_status)
){

    $_SESSION['error'] =
    "Veuillez remplir tous les champs professionnels.";

    header("Location: profil.php");
    exit();

}

$sql = "

UPDATE candidates

SET

    nationality = ?,
    education_level = ?,
    experience_years = ?,
    bio = ?,
    availability_status = ?

WHERE user_id = ?

";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(

    $stmt,

    "ssissi",

    $nationality,
    $education_level,
    $experience_years,
    $bio,
    $availability_status,
    $user_id

);

if(mysqli_stmt_execute($stmt)){

    $_SESSION['success'] =
    "Informations professionnelles mises à jour avec succès.";

}else{

    $_SESSION['error'] =
    "Erreur lors de la mise à jour.";

}

header("Location: profil.php");
exit();

?>