```php
<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once("../config/database.php");

/* ==========================
   VERIFICATION METHODE
========================== */

if($_SERVER["REQUEST_METHOD"] != "POST"){

    die("Accès refusé");

}

/* ==========================
   RECUPERATION DONNEES
========================== */

$first_name        = trim($_POST['first_name']);
$last_name         = trim($_POST['last_name']);
$email             = trim($_POST['email']);
$phone             = trim($_POST['phone']);

$password          = $_POST['password'];
$confirm_password  = $_POST['confirm_password'];

$birth_date        = $_POST['birth_date'];
$gender            = $_POST['gender'];

$address           = trim($_POST['address']);
$city              = trim($_POST['city']);
$nationality       = trim($_POST['nationality']);

$marital_status    = trim($_POST['marital_status']);
$education_level   = trim($_POST['education_level']);

$experience_years  = intval($_POST['experience_years']);

$bio               = trim($_POST['bio']);

$emergency_contact = trim($_POST['emergency_contact']);

/* ==========================
   VALIDATION
========================== */

if(
    empty($first_name) ||
    empty($last_name) ||
    empty($email) ||
    empty($phone) ||
    empty($password) ||
    empty($confirm_password) ||
    empty($birth_date) ||
    empty($gender) ||
    empty($address) ||
    empty($city) ||
    empty($nationality) ||
    empty($marital_status) ||
    empty($education_level) ||
    empty($emergency_contact)
){

    die("Tous les champs obligatoires doivent être remplis.");

}

if($password != $confirm_password){

    die("Les mots de passe ne correspondent pas.");

}

/* ==========================
   EMAIL EXISTANT ?
========================== */

$sql = "SELECT id FROM users WHERE email = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "s",
    $email
);

$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows > 0){

    die("Cette adresse email existe déjà.");

}

/* ==========================
   PHOTO PROFIL
========================== */

$profile_photo = NULL;

if(
    isset($_FILES['profile_photo']) &&
    $_FILES['profile_photo']['error'] == 0
){

    $upload_dir = "../uploads/profiles/";

    if(!is_dir($upload_dir)){

        mkdir(
            $upload_dir,
            0777,
            true
        );

    }

    $extension = pathinfo(
        $_FILES['profile_photo']['name'],
        PATHINFO_EXTENSION
    );

    $file_name =
    time() .
    "_" .
    uniqid() .
    "." .
    $extension;

    $destination =
    $upload_dir .
    $file_name;

    if(
        move_uploaded_file(
            $_FILES['profile_photo']['tmp_name'],
            $destination
        )
    ){

        $profile_photo =
        "uploads/profiles/" .
        $file_name;

    }

}

/* ==========================
   HASH PASSWORD
========================== */

$password_hash =
password_hash(
    $password,
    PASSWORD_DEFAULT
);

/* ==========================
   ROLE INTERVENANT
========================== */

$role_id = 3;

/* ==========================
   INSERTION USERS
========================== */

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
    'active'

)

";

$stmt = $conn->prepare($sql);

if(!$stmt){

    die($conn->error);

}

$stmt->bind_param(

    "issssss",

    $role_id,
    $first_name,
    $last_name,
    $email,
    $phone,
    $password_hash,
    $profile_photo

);

if(!$stmt->execute()){

    die($stmt->error);

}

/* ==========================
   USER ID
========================== */

$user_id =
$conn->insert_id;

/* ==========================
   INSERTION CANDIDATE
========================== */

$availability_status =
"hors_ligne";

$verification_status =
"en_attente";

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
    emergency_contact

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
    ?

)

";

$stmt = $conn->prepare($sql);

if(!$stmt){

    die($conn->error);

}

$stmt->bind_param(

    "isssssssissss",

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
    $emergency_contact

);

if(!$stmt->execute()){

    die($stmt->error);

}

/* ==========================
   SESSION
========================== */

$_SESSION['user_id'] = $user_id;

/* ==========================
   REDIRECTION
========================== */

header(
    "Location: candidashboard.php"
);

exit();

?>
```
