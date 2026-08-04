<?php

session_start();

require_once("config/database.php");
require_once("config/auth.php");

infinitia_delete_expired_password_reset_tokens($conn);

$generic_message = "Si cette adresse e-mail correspond a un compte actif, un lien de reinitialisation vient d'etre envoye.";
$email = "";
$development_reset_link = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $csrf_token = isset($_POST["csrf_token"]) ? $_POST["csrf_token"] : "";
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";

    if(!infinitia_verify_csrf_token("forgot_password_csrf", $csrf_token)){

        $_SESSION["error"] = "La demande a expire. Veuillez reessayer.";

    }elseif($email == ""){

        $_SESSION["error"] = "Veuillez saisir votre adresse e-mail.";

    }elseif(strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)){

        $_SESSION["error"] = "Veuillez saisir une adresse e-mail valide.";

    }else{

        $user = array();
        $sql = "
        SELECT
            id,
            email,
            first_name,
            last_name,
            status
        FROM users
        WHERE email = ?
        LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if(!$stmt){

            error_log("Password reset user lookup prepare error: " . mysqli_error($conn));

        }else{

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result(
                $stmt,
                $db_user_id,
                $db_email,
                $db_first_name,
                $db_last_name,
                $db_status
            );

            if(mysqli_stmt_fetch($stmt)){

                $user = array(
                    "id" => $db_user_id,
                    "email" => $db_email,
                    "first_name" => $db_first_name,
                    "last_name" => $db_last_name,
                    "status" => $db_status
                );

            }

            mysqli_stmt_close($stmt);

        }

        if(count($user) > 0 && isset($user["status"]) && $user["status"] == "active"){

            $token = infinitia_create_password_reset_token($conn, (int)$user["id"]);

            if(is_array($token)){

                $reset_link = infinitia_build_reset_url($token["selector"], $token["validator"]);
                $full_name = trim($user["first_name"] . " " . $user["last_name"]);

                if($full_name == ""){

                    $full_name = $user["email"];

                }

                $sent = infinitia_send_password_reset_email($user["email"], $full_name, $reset_link);

                if(!$sent && infinitia_is_development()){

                    $development_reset_link = $reset_link;

                }

            }elseif($token === "limited"){

                error_log("Password reset request limited for user id " . (int)$user["id"]);

            }else{

                error_log("Password reset token creation failed for user id " . (int)$user["id"]);

            }

        }else{

            error_log("Password reset requested for unknown or inactive email.");

        }

        $_SESSION["success"] = $generic_message;

    }

}

$csrf_token = infinitia_csrf_token("forgot_password_csrf");

?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <?php require_once(__DIR__ . "/includes/pwa-head.php"); ?>

    <base href="<?php echo app_url_html(""); ?>">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublie | Infinitia Care Services</title>
    <link rel="icon" type="image/x-icon" href="<?php echo app_url_html("assets/images/ico.ico"); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo app_url_html("assets/css/style.css"); ?>">

</head>

<body>

<section class="register-section">

    <div class="container">

        <h1 class="main-title">Mot de passe oublie</h1>
        <p class="subtitle">Recevez un lien securise pour reinitialiser votre mot de passe.</p>

        <br><br>

        <div class="row">

            <div class="col s12 m8 l6 offset-m2 offset-l3">

                <?php if(isset($_SESSION["error"])){ ?>
                    <div class="login-error">
                        <i class="material-icons tiny">error_outline</i>
                        <?php echo htmlspecialchars($_SESSION["error"]); unset($_SESSION["error"]); ?>
                    </div>
                <?php } ?>

                <?php if(isset($_SESSION["success"])){ ?>
                    <div class="card-panel green white-text">
                        <?php echo htmlspecialchars($_SESSION["success"]); unset($_SESSION["success"]); ?>
                    </div>
                <?php } ?>

                <?php if($development_reset_link != "" && infinitia_is_development()){ ?>
                    <div class="card-panel yellow lighten-4 black-text">
                        <strong>Mode developpement :</strong><br>
                        <a href="<?php echo htmlspecialchars($development_reset_link); ?>">
                            <?php echo htmlspecialchars($development_reset_link); ?>
                        </a>
                    </div>
                <?php } ?>

                <div class="card register-card">

                    <div class="form-header">
                        <h4>Recuperation du compte</h4>
                    </div>

                    <div class="card-content">

                        <div class="icon-circle client-bg">
                            <i class="material-icons">lock_reset</i>
                        </div>

                        <br>

                        <p class="center grey-text text-darken-1">
                            Entrez l'adresse e-mail associee a votre compte.
                        </p>

                        <form action="<?php echo app_url_html("mot-de-passe-oublie"); ?>" method="POST">

                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <div class="input-field">
                                <i class="material-icons prefix">email</i>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       value="<?php echo htmlspecialchars($email); ?>"
                                       autocomplete="email"
                                       required>
                                <label for="email" class="<?php if($email != ""){ echo "active"; } ?>">Adresse Email</label>
                            </div>

                            <div class="login-actions">
                                <a href="<?php echo app_url_html("login"); ?>" class="cancel-btn waves-effect">
                                    <i class="material-icons left">arrow_back</i>
                                    Connexion
                                </a>
                                <button type="submit" class="btn-large btn-register waves-effect waves-light">
                                    Envoyer
                                    <i class="material-icons right">send</i>
                                </button>
                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

</body>
</html>
