<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once("../config/database.php");

/* =========================================
   VERIFICATION CONNEXION
========================================= */

if(!isset($_SESSION["user_id"]))
{
    header("Location: " . app_url("login"));
    exit();
}

if($_SERVER["REQUEST_METHOD"] != "POST")
{
    header("Location: " . app_url("intervenant/profil"));
    exit();
}

$user_id = (int)$_SESSION["user_id"];

/* =========================================
   RECUPERATION DONNEES
========================================= */

$first_name = isset($_POST['first_name'])
    ? trim($_POST['first_name'])
    : '';

$last_name = isset($_POST['last_name'])
    ? trim($_POST['last_name'])
    : '';

$phone = isset($_POST['phone'])
    ? trim($_POST['phone'])
    : '';

$birth_date = isset($_POST['birth_date'])
    ? trim($_POST['birth_date'])
    : NULL;

$gender = isset($_POST['gender'])
    ? trim($_POST['gender'])
    : '';

$city = isset($_POST['city'])
    ? trim($_POST['city'])
    : '';

$address = isset($_POST['address'])
    ? trim($_POST['address'])
    : '';

/* =========================================
   PHOTO PROFIL
========================================= */

$photo_sql = "";

if(
    isset($_FILES['profile_photo']) &&
    $_FILES['profile_photo']['error'] == 0
)
{
    $extension = strtolower(
        pathinfo(
            $_FILES['profile_photo']['name'],
            PATHINFO_EXTENSION
        )
    );

    $allowed = array(
        'jpg',
        'jpeg',
        'png',
        'gif'
    );

    if(in_array($extension, $allowed))
    {
        $new_name =
            "profile_" .
            $user_id .
            "_" .
            time() .
            "." .
            $extension;

        $upload_dir = "../uploads/profiles/";

        if(!is_dir($upload_dir))
        {
            mkdir($upload_dir,0777,true);
        }

        $destination =
            $upload_dir .
            $new_name;

        if(
            move_uploaded_file(
                $_FILES['profile_photo']['tmp_name'],
                $destination
            )
        )
        {
            $photo_path =
                "uploads/profiles/" .
                $new_name;

            $photo_sql =
                ", profile_photo='" .
                mysqli_real_escape_string(
                    $conn,
                    $photo_path
                ) .
                "'";
        }
    }
}

/* =========================================
   UPDATE USERS
========================================= */

$sql = "

UPDATE users

SET

    first_name = ?,
    last_name = ?,
    phone = ?

    $photo_sql

WHERE id = ?

";

$stmt = mysqli_prepare($conn,$sql);

if(!$stmt)
{
    die(
        "Erreur SQL USERS : " .
        mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(

    $stmt,

    "sssi",

    $first_name,
    $last_name,
    $phone,
    $user_id

);

mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);

/* =========================================
   UPDATE CANDIDATES
========================================= */

$sql = "

UPDATE candidates

SET

    birth_date = ?,
    gender = ?,
    city = ?,
    address = ?

WHERE user_id = ?

";

$stmt = mysqli_prepare($conn,$sql);

if(!$stmt)
{
    die(
        "Erreur SQL CANDIDATES : " .
        mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(

    $stmt,

    "ssssi",

    $birth_date,
    $gender,
    $city,
    $address,
    $user_id

);

mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);

/* =========================================
   MESSAGE
========================================= */

$_SESSION["success"] =
"Profil mis à jour avec succès.";

/* =========================================
   REDIRECTION
========================================= */

header("Location: " . app_url("intervenant/profil"));
exit();

?>