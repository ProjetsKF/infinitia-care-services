<?php

session_start();

require_once("../config/database.php");

if(!isset($_SESSION["user_id"])){

    header("Location: ../login.php");
    exit();

}

if(!isset($_SESSION["role_id"]) || $_SESSION["role_id"] != 2){

    header("Location: ../login.php");
    exit();

}

$user_id = (int)$_SESSION["user_id"];

function safe_text($value)
{
    if($value === NULL || $value === ""){

        return "";

    }

    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function display_value($value)
{
    if($value === NULL || $value === ""){

        return "Non renseigne";

    }

    return (string)$value;
}

function format_date_fr($value)
{
    if($value === NULL || $value === ""){

        return "Non renseigne";

    }

    $timestamp = strtotime($value);

    if($timestamp === false){

        return "Non renseigne";

    }

    return date("d/m/Y H:i", $timestamp);
}

function profile_photo_path($profile_photo)
{
    if($profile_photo === NULL || $profile_photo === ""){

        return "../assets/images/default-user.png";

    }

    if(strpos($profile_photo, "uploads/") === 0){

        return "../" . $profile_photo;

    }

    return "../uploads/profiles/" . $profile_photo;
}

function redirect_settings()
{
    header("Location: parametres.php");
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $action = isset($_POST["action"])
        ? $_POST["action"]
        : "";

    if($action == "update_profile"){

        $first_name = isset($_POST["first_name"])
            ? trim($_POST["first_name"])
            : "";

        $last_name = isset($_POST["last_name"])
            ? trim($_POST["last_name"])
            : "";

        $email = isset($_POST["email"])
            ? trim($_POST["email"])
            : "";

        $phone = isset($_POST["phone"])
            ? trim($_POST["phone"])
            : "";

        if($first_name == "" || $last_name == "" || $email == ""){

            $_SESSION["error"] = "Veuillez remplir les champs obligatoires.";
            redirect_settings();

        }

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){

            $_SESSION["error"] = "Adresse email invalide.";
            redirect_settings();

        }

        $existing_id = 0;

        $sql = "
        SELECT id
        FROM users
        WHERE email = ?
        AND id != ?
        LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $email,
            $user_id
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $existing_id);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if($existing_id > 0){

            $_SESSION["error"] = "Cette adresse email est deja utilisee.";
            redirect_settings();

        }

        if($phone != ""){

            $existing_id = 0;

            $sql = "
            SELECT id
            FROM users
            WHERE phone = ?
            AND id != ?
            LIMIT 1
            ";

            $stmt = mysqli_prepare($conn, $sql);

            if(!$stmt){

                die("Erreur SQL : " . mysqli_error($conn));

            }

            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $phone,
                $user_id
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $existing_id);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if($existing_id > 0){

                $_SESSION["error"] = "Ce numero de telephone est deja utilise.";
                redirect_settings();

            }

        }

        $profile_photo = "";

        if(
            isset($_FILES["profile_photo"]) &&
            $_FILES["profile_photo"]["error"] == 0
        ){

            $allowed_extensions = array(
                "jpg",
                "jpeg",
                "png",
                "gif"
            );

            $extension = strtolower(
                pathinfo(
                    $_FILES["profile_photo"]["name"],
                    PATHINFO_EXTENSION
                )
            );

            $file_size = (int)$_FILES["profile_photo"]["size"];

            if(!in_array($extension, $allowed_extensions)){

                $_SESSION["error"] = "Format de photo non autorise.";
                redirect_settings();

            }

            if($file_size > 5 * 1024 * 1024){

                $_SESSION["error"] = "La photo ne doit pas depasser 5 MB.";
                redirect_settings();

            }

            $upload_dir = "../uploads/profiles/";

            if(!is_dir($upload_dir)){

                mkdir($upload_dir, 0777, true);

            }

            $file_name =
                "profile_" .
                $user_id .
                "_" .
                time() .
                "." .
                $extension;

            $destination = $upload_dir . $file_name;

            if(!move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $destination)){

                $_SESSION["error"] = "Erreur lors du televersement de la photo.";
                redirect_settings();

            }

            $profile_photo = "uploads/profiles/" . $file_name;

        }

        if($phone == ""){

            $phone = NULL;

        }

        if($profile_photo != ""){

            $sql = "
            UPDATE users
            SET
                first_name = ?,
                last_name = ?,
                email = ?,
                phone = ?,
                profile_photo = ?
            WHERE id = ?
            ";

            $stmt = mysqli_prepare($conn, $sql);

            if(!$stmt){

                die("Erreur SQL : " . mysqli_error($conn));

            }

            mysqli_stmt_bind_param(
                $stmt,
                "sssssi",
                $first_name,
                $last_name,
                $email,
                $phone,
                $profile_photo,
                $user_id
            );

        }else{

            $sql = "
            UPDATE users
            SET
                first_name = ?,
                last_name = ?,
                email = ?,
                phone = ?
            WHERE id = ?
            ";

            $stmt = mysqli_prepare($conn, $sql);

            if(!$stmt){

                die("Erreur SQL : " . mysqli_error($conn));

            }

            mysqli_stmt_bind_param(
                $stmt,
                "ssssi",
                $first_name,
                $last_name,
                $email,
                $phone,
                $user_id
            );

        }

        if(mysqli_stmt_execute($stmt)){

            $_SESSION["first_name"] = $first_name;
            $_SESSION["last_name"] = $last_name;
            $_SESSION["email"] = $email;
            $_SESSION["success"] = "Vos informations ont ete mises a jour.";

        }else{

            $_SESSION["error"] = "Erreur lors de la mise a jour des informations.";

        }

        mysqli_stmt_close($stmt);
        redirect_settings();

    }

    if($action == "update_password"){

        $current_password = isset($_POST["current_password"])
            ? $_POST["current_password"]
            : "";

        $new_password = isset($_POST["new_password"])
            ? $_POST["new_password"]
            : "";

        $confirm_password = isset($_POST["confirm_password"])
            ? $_POST["confirm_password"]
            : "";

        if($current_password == "" || $new_password == "" || $confirm_password == ""){

            $_SESSION["error"] = "Veuillez remplir tous les champs du mot de passe.";
            redirect_settings();

        }

        if($new_password != $confirm_password){

            $_SESSION["error"] = "Les nouveaux mots de passe ne correspondent pas.";
            redirect_settings();

        }

        if(strlen($new_password) < 6){

            $_SESSION["error"] = "Le nouveau mot de passe doit contenir au moins 6 caracteres.";
            redirect_settings();

        }

        $password_hash_current = "";

        $sql = "
        SELECT password
        FROM users
        WHERE id = ?
        LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $password_hash_current);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if(!password_verify($current_password, $password_hash_current)){

            $_SESSION["error"] = "Le mot de passe actuel est incorrect.";
            redirect_settings();

        }

        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        $sql = "
        UPDATE users
        SET password = ?
        WHERE id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            die("Erreur SQL : " . mysqli_error($conn));

        }

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $new_password_hash,
            $user_id
        );

        if(mysqli_stmt_execute($stmt)){

            $_SESSION["success"] = "Votre mot de passe a ete modifie.";

        }else{

            $_SESSION["error"] = "Erreur lors de la modification du mot de passe.";

        }

        mysqli_stmt_close($stmt);
        redirect_settings();

    }

}

$user = array(
    "first_name" => "",
    "last_name" => "",
    "email" => "",
    "phone" => "",
    "profile_photo" => "",
    "status" => "",
    "last_login" => "",
    "created_at" => ""
);

$sql = "

SELECT
    first_name,
    last_name,
    email,
    phone,
    profile_photo,
    status,
    last_login,
    created_at
FROM users
WHERE id = ?
LIMIT 1

";

$stmt = mysqli_prepare($conn, $sql);

if(!$stmt){

    die("Erreur SQL : " . mysqli_error($conn));

}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result(
    $stmt,
    $first_name,
    $last_name,
    $email,
    $phone,
    $profile_photo,
    $status,
    $last_login,
    $created_at
);

if(mysqli_stmt_fetch($stmt)){

    $user["first_name"] = $first_name;
    $user["last_name"] = $last_name;
    $user["email"] = $email;
    $user["phone"] = $phone;
    $user["profile_photo"] = $profile_photo;
    $user["status"] = $status;
    $user["last_login"] = $last_login;
    $user["created_at"] = $created_at;

}

mysqli_stmt_close($stmt);

$photo_path = profile_photo_path($user["profile_photo"]);

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>
        Parametres | INFINITIA
    </title>

    <link rel="icon" type="image/x-icon" href="../assets/images/ico.ico">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <link rel="preconnect"
    href="https://fonts.googleapis.com">

    <link rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<div class="dashboard">

    <?php

    $current_page = "parametres";

    include("menucli.php");

    ?>

    <div class="main-content">

        <div class="topbar">

            <div>

                <div class="page-title">

                    <i class="material-icons left">
                        settings
                    </i>

                    Parametres

                </div>

                <div class="welcome-text">
                    Consultez et mettez a jour les informations de votre compte.
                </div>

            </div>

        </div>

        <?php if(isset($_SESSION["success"])){ ?>

            <div class="card-panel green white-text">
                <?php echo safe_text($_SESSION["success"]); ?>
            </div>

            <?php unset($_SESSION["success"]); ?>

        <?php } ?>

        <?php if(isset($_SESSION["error"])){ ?>

            <div class="card-panel red white-text">
                <?php echo safe_text($_SESSION["error"]); ?>
            </div>

            <?php unset($_SESSION["error"]); ?>

        <?php } ?>

        <div class="row">

            <div class="col s12 l7">

                <div class="card">

                    <div class="card-content">

                        <span class="card-title">
                            Informations personnelles
                        </span>

                        <div class="row" style="margin-top:25px;">

                            <div class="col s12 m4 center">

                                <img src="<?php echo safe_text($photo_path); ?>"
                                     alt="Photo de profil"
                                     width="130"
                                     height="130"
                                     style="border-radius:50%; object-fit:cover;">

                            </div>

                            <div class="col s12 m8">

                                <table class="striped">

                                    <tbody>

                                        <tr>
                                            <th>Prenom</th>
                                            <td><?php echo safe_text(display_value($user["first_name"])); ?></td>
                                        </tr>

                                        <tr>
                                            <th>Nom</th>
                                            <td><?php echo safe_text(display_value($user["last_name"])); ?></td>
                                        </tr>

                                        <tr>
                                            <th>Email</th>
                                            <td><?php echo safe_text(display_value($user["email"])); ?></td>
                                        </tr>

                                        <tr>
                                            <th>Telephone</th>
                                            <td><?php echo safe_text(display_value($user["phone"])); ?></td>
                                        </tr>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                    <div class="card-action">

                        <a href="#modalProfile"
                           class="btn waves-effect waves-light modal-trigger">

                            Modifier mes informations

                        </a>

                    </div>

                </div>

            </div>

            <div class="col s12 l5">

                <div class="card">

                    <div class="card-content">

                        <span class="card-title">
                            Securite du compte
                        </span>

                        <p class="grey-text text-darken-1">
                            Modifiez votre mot de passe regulierement pour renforcer la securite de votre compte.
                        </p>

                    </div>

                    <div class="card-action">

                        <a href="#modalPassword"
                           class="btn waves-effect waves-light modal-trigger">

                            Changer mon mot de passe

                        </a>

                    </div>

                </div>

                <div class="card">

                    <div class="card-content">

                        <span class="card-title">
                            Informations du compte
                        </span>

                        <table class="striped">

                            <tbody>

                                <tr>
                                    <th>Statut</th>
                                    <td><?php echo safe_text(display_value($user["status"])); ?></td>
                                </tr>

                                <tr>
                                    <th>Date d'inscription</th>
                                    <td><?php echo safe_text(format_date_fr($user["created_at"])); ?></td>
                                </tr>

                                <tr>
                                    <th>Derniere connexion</th>
                                    <td><?php echo safe_text(format_date_fr($user["last_login"])); ?></td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<div id="modalProfile" class="modal modal-fixed-footer">

    <form action="parametres.php"
          method="POST"
          enctype="multipart/form-data">

        <input type="hidden"
               name="action"
               value="update_profile">

        <div class="modal-content">

            <h4>Modifier mes informations</h4>

            <div class="row">

                <div class="input-field col s12 m6">

                    <input type="text"
                           name="first_name"
                           id="first_name"
                           value="<?php echo safe_text($user["first_name"]); ?>"
                           required>

                    <label class="active" for="first_name">
                        Prenom
                    </label>

                </div>

                <div class="input-field col s12 m6">

                    <input type="text"
                           name="last_name"
                           id="last_name"
                           value="<?php echo safe_text($user["last_name"]); ?>"
                           required>

                    <label class="active" for="last_name">
                        Nom
                    </label>

                </div>

                <div class="input-field col s12 m6">

                    <input type="email"
                           name="email"
                           id="email"
                           value="<?php echo safe_text($user["email"]); ?>"
                           required>

                    <label class="active" for="email">
                        Email
                    </label>

                </div>

                <div class="input-field col s12 m6">

                    <input type="text"
                           name="phone"
                           id="phone"
                           value="<?php echo safe_text($user["phone"]); ?>">

                    <label class="active" for="phone">
                        Telephone
                    </label>

                </div>

                <div class="file-field input-field col s12">

                    <div class="btn">
                        <span>Photo</span>
                        <input type="file"
                               name="profile_photo"
                               accept=".jpg,.jpeg,.png,.gif">
                    </div>

                    <div class="file-path-wrapper">
                        <input class="file-path validate"
                               type="text"
                               placeholder="Choisir une nouvelle photo de profil">
                    </div>

                </div>

            </div>

        </div>

        <div class="modal-footer">

            <a href="#!"
               class="modal-close btn-flat">
                Annuler
            </a>

            <button type="submit"
                    class="btn waves-effect waves-light">
                Enregistrer
            </button>

        </div>

    </form>

</div>

<div id="modalPassword" class="modal modal-fixed-footer">

    <form action="parametres.php"
          method="POST">

        <input type="hidden"
               name="action"
               value="update_password">

        <div class="modal-content">

            <h4>Changer mon mot de passe</h4>

            <div class="input-field">

                <input type="password"
                       name="current_password"
                       id="current_password"
                       required>

                <label for="current_password">
                    Mot de passe actuel
                </label>

            </div>

            <div class="input-field">

                <input type="password"
                       name="new_password"
                       id="new_password"
                       required>

                <label for="new_password">
                    Nouveau mot de passe
                </label>

            </div>

            <div class="input-field">

                <input type="password"
                       name="confirm_password"
                       id="confirm_password"
                       required>

                <label for="confirm_password">
                    Confirmation du nouveau mot de passe
                </label>

            </div>

        </div>

        <div class="modal-footer">

            <a href="#!"
               class="modal-close btn-flat">
                Annuler
            </a>

            <button type="submit"
                    class="btn waves-effect waves-light">
                Modifier
            </button>

        </div>

    </form>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function() {

    M.Modal.init(
        document.querySelectorAll('.modal')
    );

    M.updateTextFields();

});

</script>

</body>
</html>
