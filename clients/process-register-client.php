<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// ========================================
// CONNEXION BASE DE DONNEES
// ========================================

require_once("../config/database.php");

// ========================================
// VERIFIER SI LE FORMULAIRE EST ENVOYE
// ========================================

if($_SERVER["REQUEST_METHOD"] == "POST"){

    // ========================================
    // RECUPERATION DES DONNEES
    // ========================================

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $client_type = $_POST['client_type'];
    $company_name = trim($_POST['company_name']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $gps_location = trim($_POST['gps_location']);

    // ROLE CLIENT = 2

    $role_id = 2;

    // STATUS PAR DEFAUT

    $status = "active";

    // ========================================
    // VALIDATION MOT DE PASSE
    // ========================================

    if($password !== $confirm_password){

        $_SESSION['error'] =
                "Les mots de passe ne correspondent pas.";

                header(
                    "Location: ../register-client.php"
                );

                exit();

    }

    // ========================================
    // VERIFIER SI EMAIL EXISTE
    // ========================================

    $check_email = $conn->prepare(
        "SELECT id FROM users WHERE email = ?"
    );

    $check_email->bind_param(
        "s",
        $email
    );

    $check_email->execute();

    $result = $check_email->get_result();

    if($result->num_rows > 0){

        $_SESSION['error'] =
            "Cette adresse email existe déjà.";

            header(
                "Location: ../register-client.php"
            );

            exit();

    }

    // ========================================
    // HASH PASSWORD
    // ========================================

    $hashed_password = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    // ========================================
    // GESTION PHOTO
    // ========================================

    $profile_photo = "";

    if(isset($_FILES['profile_photo']) &&
       $_FILES['profile_photo']['error'] == 0){

        $allowed_types = [

            "image/jpeg",
            "image/png",
            "image/jpg"

        ];

        $max_size = 5 * 1024 * 1024;

        $file_type =
        $_FILES['profile_photo']['type'];

        $file_size =
        $_FILES['profile_photo']['size'];

        // VERIFIER TYPE

        if(!in_array($file_type, $allowed_types)){

            die("Format image non autorisé.");

        }

        // VERIFIER TAILLE

        if($file_size > $max_size){

            die("Image supérieure à 5 MB.");

        }

        // DOSSIER UPLOAD

        $upload_dir =
        "../uploads/profiles/";

        // CREER DOSSIER SI N'EXISTE PAS

        if(!is_dir($upload_dir)){

            mkdir(
                $upload_dir,
                0777,
                true
            );

        }

        // GENERER NOM UNIQUE

        $file_name =
        time() . "_" .
        basename(
            $_FILES['profile_photo']['name']
        );

        $target_file =
        $upload_dir . $file_name;

        // UPLOAD IMAGE

        if(move_uploaded_file(

            $_FILES['profile_photo']['tmp_name'],
            $target_file

        )){

            $profile_photo = $file_name;

        }

    }

   // ========================================
// INSERTION USERS
// ========================================

$insert_user = $conn->prepare(

    "INSERT INTO users(

        role_id,
        first_name,
        last_name,
        email,
        phone,
        password,
        profile_photo,
        status

    )

    VALUES(

        ?, ?, ?, ?, ?, ?, ?, ?

    )"

);

$insert_user->bind_param(

    "isssssss",

    $role_id,
    $first_name,
    $last_name,
    $email,
    $phone,
    $hashed_password,
    $profile_photo,
    $status

);

// ========================================
// EXECUTION USER
// ========================================

if($insert_user->execute()){

    // ========================================
    // RECUPERATION ID USER
    // ========================================

    $user_id = $conn->insert_id;

    // ========================================
    // SI CE N'EST PAS UNE ENTREPRISE
    // ========================================

    if($client_type != "company"){

        $company_name = NULL;

    }

    // ========================================
    // INSERTION CLIENT
    // ========================================

    $insert_client = $conn->prepare(

        "INSERT INTO clients(

            user_id,
            client_type,
            company_name,
            address,
            city,
            gps_location

        )

        VALUES(

            ?, ?, ?, ?, ?, ?

        )"

    );

    $insert_client->bind_param(

        "isssss",

        $user_id,
        $client_type,
        $company_name,
        $address,
        $city,
        $gps_location

    );

    // ========================================
    // EXECUTION CLIENT
    // ========================================

    if($insert_client->execute()){

        $_SESSION['success'] =
        "Compte créé avec succès. Vous pouvez maintenant vous connecter.";

        header(
            "Location: ../login.php"
        );

        exit();

    }else{

        $_SESSION['error'] =
        "Erreur lors de l'enregistrement du profil client : " .
        $insert_client->error;

        header(
            "Location: register-client.php"
        );

        exit();

    }

}else{

    $_SESSION['error'] =
    "Erreur lors de la création du compte utilisateur : " .
    $insert_user->error;

    header(
        "Location: ../register-client.php"
    );

    exit();

}

}else{

    header(
        "Location: ../register-client.php"
    );

    exit();

}

?>