<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once("../config/database.php");

/* =========================================
   VERIFICATION CONNEXION
========================================= */

if(!isset($_SESSION["user_id"])){

    header("Location: ../login.php");
    exit();

}

/* =========================================
   VERIFICATION POST
========================================= */

if($_SERVER["REQUEST_METHOD"] !== "POST"){

    header("Location: profil.php");
    exit();

}

$user_id = (int)$_SESSION["user_id"];

/* =========================================
   RECUPERATION DONNEES
========================================= */

$birth_date        = $_POST['birth_date'] ?? null;
$gender            = $_POST['gender'] ?? '';
$address           = trim($_POST['address'] ?? '');
$city              = trim($_POST['city'] ?? '');
$nationality       = trim($_POST['nationality'] ?? '');
$marital_status    = trim($_POST['marital_status'] ?? '');
$education_level   = trim($_POST['education_level'] ?? '');
$experience_years  = !empty($_POST['experience_years'])
                    ? (int)$_POST['experience_years']
                    : 0;
$bio               = trim($_POST['bio'] ?? '');
$emergency_contact = trim($_POST['emergency_contact'] ?? '');

/* =========================================
   RECHERCHE CANDIDAT
========================================= */

$sql = "

SELECT id

FROM candidates

WHERE user_id = ?

LIMIT 1

";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur SQL : " . mysqli_error($conn));

}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) === 0){

    $_SESSION["error"] =
    "Profil intervenant introuvable.";

    header("Location: profil.php");
    exit();

}

$candidate = mysqli_fetch_assoc($result);

$candidate_id = (int)$candidate['id'];

/* =========================================
   MISE A JOUR
========================================= */

$sql = "

UPDATE candidates

SET

    birth_date = ?,
    gender = ?,
    address = ?,
    city = ?,
    nationality = ?,
    marital_status = ?,
    education_level = ?,
    experience_years = ?,
    bio = ?,
    emergency_contact = ?

WHERE id = ?

";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur préparation UPDATE : "
        . mysqli_error($conn));

}

mysqli_stmt_bind_param(

    $stmt,

    "sssssssissi",

    $birth_date,
    $gender,
    $address,
    $city,
    $nationality,
    $marital_status,
    $education_level,
    $experience_years,
    $bio,
    $emergency_contact,
    $candidate_id

);

if(mysqli_stmt_execute($stmt)){

    $_SESSION["success"] =
    "Profil mis à jour avec succès.";

}else{

    $_SESSION["error"] =
    "Erreur UPDATE : "
    . mysqli_stmt_error($stmt);

}

/* =========================================
   REDIRECTION
========================================= */

header("Location: profil.php");
exit();

?>