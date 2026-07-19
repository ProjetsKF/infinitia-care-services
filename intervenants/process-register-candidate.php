<?php

session_start();

require_once("../config/database.php");

function redirect_with_error($message)
{
    $_SESSION["error"] = $message;
    header("Location: ../register-candidate.php");
    exit();
}

if($_SERVER["REQUEST_METHOD"] != "POST"){

    redirect_with_error("Acces refuse.");

}

$photo_consent = isset($_POST["photo_consent"])
    ? (int)$_POST["photo_consent"]
    : 0;

$terms_accepted = isset($_POST["terms_accepted"])
    ? (int)$_POST["terms_accepted"]
    : 0;

if($photo_consent !== 1){

    redirect_with_error("Vous devez autoriser l'utilisation de votre photographie pour créer votre profil public.");

}

if($terms_accepted !== 1){

    redirect_with_error("Vous devez accepter les conditions d'utilisation.");

}

$photo_consent_date = date("Y-m-d H:i:s");

$first_name = isset($_POST["first_name"]) ? trim($_POST["first_name"]) : "";
$last_name = isset($_POST["last_name"]) ? trim($_POST["last_name"]) : "";
$email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
$phone = isset($_POST["phone"]) ? trim($_POST["phone"]) : "";
$password = isset($_POST["password"]) ? $_POST["password"] : "";
$confirm_password = isset($_POST["confirm_password"]) ? $_POST["confirm_password"] : "";
$birth_date = isset($_POST["birth_date"]) ? trim($_POST["birth_date"]) : "";
$gender = isset($_POST["gender"]) ? trim($_POST["gender"]) : "";
$address = isset($_POST["address"]) ? trim($_POST["address"]) : "";
$city = isset($_POST["city"]) ? trim($_POST["city"]) : "";
$nationality = isset($_POST["nationality"]) ? trim($_POST["nationality"]) : "";
$marital_status = isset($_POST["marital_status"]) ? trim($_POST["marital_status"]) : "";
$education_level = isset($_POST["education_level"]) ? trim($_POST["education_level"]) : "";
$experience_years_raw = isset($_POST["experience_years"]) ? trim($_POST["experience_years"]) : "0";
$bio = isset($_POST["bio"]) ? trim($_POST["bio"]) : "";
$emergency_contact = isset($_POST["emergency_contact"]) ? trim($_POST["emergency_contact"]) : "";

if(
    $first_name == "" ||
    $last_name == "" ||
    $email == "" ||
    $phone == "" ||
    $password == "" ||
    $confirm_password == "" ||
    $birth_date == "" ||
    $gender == "" ||
    $address == "" ||
    $city == "" ||
    $nationality == "" ||
    $marital_status == "" ||
    $education_level == "" ||
    $emergency_contact == ""
){

    redirect_with_error("Tous les champs obligatoires doivent etre remplis.");

}

if(!filter_var($email, FILTER_VALIDATE_EMAIL)){

    redirect_with_error("Adresse email invalide.");

}

if($gender != "Homme" && $gender != "Femme"){

    redirect_with_error("Le sexe selectionne est invalide.");

}

if($password != $confirm_password){

    redirect_with_error("Les mots de passe ne correspondent pas.");

}

if($experience_years_raw == ""){

    $experience_years_raw = "0";

}

if(!is_numeric($experience_years_raw) || (int)$experience_years_raw < 0){

    redirect_with_error("Les annees d'experience doivent etre superieures ou egales a 0.");

}

$experience_years = (int)$experience_years_raw;

$sql = "SELECT id FROM users WHERE email = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    redirect_with_error("Une erreur est survenue pendant la verification de l'email.");

}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if(mysqli_stmt_num_rows($stmt) > 0){

    mysqli_stmt_close($stmt);
    redirect_with_error("Cette adresse email existe deja.");

}

mysqli_stmt_close($stmt);

$profile_photo = NULL;
$uploaded_file_path = "";

if(isset($_FILES["profile_photo"]) && $_FILES["profile_photo"]["error"] != UPLOAD_ERR_NO_FILE){

    if($_FILES["profile_photo"]["error"] != UPLOAD_ERR_OK){

        redirect_with_error("Le telechargement de la photo a echoue.");

    }

    $allowed_extensions = array("jpg", "jpeg", "png", "gif");
    $extension = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));

    if(!in_array($extension, $allowed_extensions)){

        redirect_with_error("Le format de la photo n'est pas autorise.");

    }

    $upload_dir = "../uploads/profiles/";

    if(!is_dir($upload_dir)){

        if(!mkdir($upload_dir, 0777, true)){

            redirect_with_error("Impossible de preparer le dossier de telechargement.");

        }

    }

    $file_name = time() . "_" . uniqid() . "." . $extension;
    $destination = $upload_dir . $file_name;

    if(!move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $destination)){

        redirect_with_error("Le telechargement de la photo a echoue.");

    }

    $uploaded_file_path = $destination;
    $profile_photo = "uploads/profiles/" . $file_name;

}

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$role_id = 3;
$availability_status = "hors_ligne";
$verification_status = "en_attente";
$user_id = 0;
$transaction_ok = true;
$error_message = "";
$stmtUser = false;
$stmtCandidate = false;

mysqli_autocommit($conn, false);

$sql = "
INSERT INTO users(
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
    ?,
    ?,
    ?,
    ?,
    ?,
    ?,
    ?,
    'inactive'
)
";

$stmtUser = mysqli_prepare($conn, $sql);

if(!$stmtUser){

    $transaction_ok = false;
    $error_message = "Une erreur est survenue pendant la creation du compte.";

}

if($transaction_ok){

    mysqli_stmt_bind_param(
        $stmtUser,
        "issssss",
        $role_id,
        $first_name,
        $last_name,
        $email,
        $phone,
        $password_hash,
        $profile_photo
    );

    if(!mysqli_stmt_execute($stmtUser)){

        $transaction_ok = false;
        $error_message = "Une erreur est survenue pendant la creation du compte.";

    }else{

        $user_id = mysqli_insert_id($conn);

    }

}

if($stmtUser){

    mysqli_stmt_close($stmtUser);

}

if($transaction_ok && $user_id <= 0){

    $transaction_ok = false;
    $error_message = "Une erreur est survenue pendant la creation du compte.";

}

if($transaction_ok){

    $sql = "
    INSERT INTO candidates(
        user_id,
        birth_date,
        gender,
        address,
        city,
        nationality,
        marital_status,
        education_level,
        experience_years,
        bio,
        availability_status,
        verification_status,
        emergency_contact,
        photo_consent,
        photo_consent_date
    )
    VALUES(
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
    )
    ";

    $stmtCandidate = mysqli_prepare($conn, $sql);

    if(!$stmtCandidate){

        $transaction_ok = false;
        $error_message = "Une erreur est survenue pendant la creation du profil intervenant.";

    }

    if($transaction_ok){

        mysqli_stmt_bind_param(
            $stmtCandidate,
            "isssssssissssis",
            $user_id,
            $birth_date,
            $gender,
            $address,
            $city,
            $nationality,
            $marital_status,
            $education_level,
            $experience_years,
            $bio,
            $availability_status,
            $verification_status,
            $emergency_contact,
            $photo_consent,
            $photo_consent_date
        );

        if(!mysqli_stmt_execute($stmtCandidate)){

            $transaction_ok = false;
            $error_message = "Une erreur est survenue pendant la creation du profil intervenant.";

        }

    }

    if($stmtCandidate){

        mysqli_stmt_close($stmtCandidate);

    }

}

if($transaction_ok){

    mysqli_commit($conn);
mysqli_autocommit($conn, true);

$_SESSION["success"] =
    "Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.";

header("Location: ../login.php");
exit();

}

mysqli_rollback($conn);
mysqli_autocommit($conn, true);

if($uploaded_file_path != "" && file_exists($uploaded_file_path)){

    unlink($uploaded_file_path);

}

if($error_message == ""){

    $error_message = "Une erreur est survenue pendant la creation du compte.";

}

redirect_with_error($error_message . " Aucun compte incomplet n'a ete cree.");

?>

