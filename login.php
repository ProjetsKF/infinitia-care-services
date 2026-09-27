<?php

require_once("config/auth.php");
require_once("config/google-oauth.php");
infinitia_session_start();
require_once("config/database.php");

infinitia_delete_expired_tokens($conn);

if(!isset($_SESSION["user_id"])){

    infinitia_auto_login($conn);

}

if(isset($_SESSION["user_id"]) && isset($_SESSION["role_id"])){

    infinitia_redirect_by_role((int)$_SESSION["role_id"]);

}

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $csrf_token = isset($_POST["csrf_token"]) ? $_POST["csrf_token"] : "";

    $email = isset($_POST["email"])
        ? trim($_POST["email"])
        : "";

    $password = isset($_POST["password"])
        ? $_POST["password"]
        : "";

    $remember_me = isset($_POST["remember_me"])
        ? 1
        : 0;

    if(!infinitia_consume_csrf_token("login_csrf", $csrf_token)){

        $_SESSION["error"] = "La demande a expire. Veuillez reessayer.";

    }elseif(empty($email) || empty($password)){

        $_SESSION["error"] =
        "Veuillez remplir tous les champs.";

    }else{

        $rate_limit = infinitia_login_rate_limit_status($conn, $email);

        if($rate_limit["limited"]){

            $_SESSION["error"] = "Trop de tentatives de connexion. Veuillez reessayer dans quelques minutes.";
            error_log("Login rate limit reached for hashed identifiers.");

        }else{

        $stmt = mysqli_prepare(
            $conn,

            "SELECT
                id,
                role_id,
                first_name,
                last_name,
                email,
                password,
                status
             FROM users
             WHERE email = ?"

        );

        if(!$stmt){

            error_log("Login user lookup prepare error: " . mysqli_error($conn));
            $_SESSION["error"] = "Une erreur est survenue. Veuillez reessayer.";

        }else{

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );

            if(!mysqli_stmt_execute($stmt)){
                error_log("Login user lookup execute error: " . mysqli_stmt_error($stmt));
                $_SESSION["error"] = "Une erreur est survenue. Veuillez reessayer.";
                mysqli_stmt_close($stmt);
                $stmt = false;
            }

            if($stmt){
            mysqli_stmt_store_result($stmt);
            mysqli_stmt_bind_result(
                $stmt,
                $db_user_id,
                $db_role_id,
                $db_first_name,
                $db_last_name,
                $db_email,
                $db_password,
                $db_status
            );

            if(mysqli_stmt_num_rows($stmt) > 0 && mysqli_stmt_fetch($stmt)){

                $user = array(
                    "id" => $db_user_id,
                    "role_id" => $db_role_id,
                    "first_name" => $db_first_name,
                    "last_name" => $db_last_name,
                    "email" => $db_email,
                    "password" => $db_password,
                    "status" => $db_status
                );

                $has_local_password = $user["password"] !== NULL && $user["password"] !== "";
                $password_hash_to_verify = $has_local_password
                    ? $user["password"]
                    : '$2y$10$CBe6j3vSmTOfq7Q3ZqAxcOc9Cv2Av6HO.NShhEec2Uhpor4E1gSmm';
                $local_password_valid = password_verify($password, $password_hash_to_verify);

                if($has_local_password && $local_password_valid){

                    /* =====================================
                       VERIFICATION STATUT DU COMPTE
                    ====================================== */

                    if($user["status"] == "inactive"){

                        $_SESSION["error"] =
                        "Votre compte est en attente d'activation par l'administrateur.";

                    }
                    elseif($user["status"] == "suspended"){

                        $_SESSION["error"] =
                        "Votre compte est suspendu. Veuillez contacter l'administrateur.";

                    }
                    elseif($user["status"] != "active"){

                        $_SESSION["error"] =
                        "Votre compte est indisponible.";

                    }
                    else{

                        /* =====================================
                           CREATION SESSION
                        ====================================== */

                        infinitia_clear_successful_login_attempts($conn, $email);
                        infinitia_apply_user_session($user);

                        /* =====================================
                           DERNIERE CONNEXION
                        ====================================== */

                        $update = mysqli_prepare(
                            $conn,

                            "UPDATE users
                             SET last_login = NOW()
                             WHERE id = ?"

                        );

                        if($update){

                            mysqli_stmt_bind_param(
                                $update,
                                "i",
                                $user["id"]
                            );

                            mysqli_stmt_execute($update);
                            mysqli_stmt_close($update);

                        }

                        if($remember_me){

                            if(!infinitia_create_remember_token($conn, (int)$user["id"])){

                                $_SESSION["error"] =
                                "Connexion reussie, mais la fonction se souvenir de moi n'a pas pu etre activee.";

                            }

                        }else{

                            infinitia_delete_user_tokens($conn, (int)$user["id"]);
                            infinitia_delete_remember_cookie();

                        }

                        /* =====================================
                           REDIRECTION SELON LE ROLE
                        ====================================== */

                        if(in_array((int)$user["role_id"], array(1, 2, 3), true)){

                            infinitia_redirect_by_role((int)$user["role_id"]);

                        }else{

                            $_SESSION["error"] =
                            "Rôle utilisateur invalide.";

                        }

                    }

                }else{

                    infinitia_record_failed_login($conn, $email);
                    $_SESSION["error"] = "Adresse email ou mot de passe incorrect.";

                }

            }else{

                password_verify(
                    $password,
                    '$2y$10$CBe6j3vSmTOfq7Q3ZqAxcOc9Cv2Av6HO.NShhEec2Uhpor4E1gSmm'
                );
                infinitia_record_failed_login($conn, $email);
                $_SESSION["error"] = "Adresse email ou mot de passe incorrect.";

            }

            mysqli_stmt_close($stmt);

            }

        }

        }

    }

}

$login_csrf_token = infinitia_csrf_token("login_csrf");
$google_start_csrf = infinitia_csrf_token("google_oauth_start_csrf");

?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <?php require_once(__DIR__ . "/includes/pwa-head.php"); ?>

    <base href="<?php echo app_url_html(""); ?>">

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>Connexion | Infinitia Care Services</title>

    <link rel="icon"
    type="image/x-icon"
    href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <!-- MATERIALIZE -->

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <!-- ICONS -->

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <!-- FONT -->

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">
    <!-- CSS -->

    <link rel="stylesheet"
    href="<?php echo app_url_html("assets/css/style.css"); ?>">


</head>

<body>

   

   <section class="register-section">

    <div class="container">

        <!-- TITLE -->

       <h1 class="main-title">

                Connexion

            </h1>

            <p class="subtitle">

                Accédez à votre espace professionnel
                INFINITIA CARE SERVICES.

            </p>

        <br><br>

        <div class="row">

           <div class="col s12 m8 l6 offset-m2 offset-l3">

                <!-- LOGIN CARD -->
            <?php if(isset($_SESSION['error'])): ?>

    <div class="login-error">

        <i class="material-icons tiny">error_outline</i>

        <?php
        echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8');
        unset($_SESSION['error']);
        ?>

    </div>

<?php endif; ?>

            <?php if(isset($_SESSION['success'])): ?>

    <div class="card-panel green white-text">

        <?php
        echo htmlspecialchars($_SESSION['success']);
        unset($_SESSION['success']);
        ?>

    </div>

<?php endif; ?>

                <div class="card register-card">

                    <!-- FORM HEADER -->

                    <div class="form-header">

                        <h4>

                            Connexion à votre compte

                        </h4>

                    </div>

                    <!-- CARD CONTENT -->

                    <div class="card-content">

                        <!-- ICON -->

                        <div class="icon-circle client-bg">

                            <i class="material-icons">

                                lock_open

                            </i>

                        </div>

                        <br>

                        <div class="google-auth-block">
                            <form class="google-auth-form"
                                  action="<?php echo app_url_html("auth/google/start.php"); ?>"
                                  method="POST">
                                <input type="hidden" name="csrf_token"
                                       value="<?php echo htmlspecialchars($google_start_csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="intent" value="login">
                                <button type="submit" class="google-auth-button">
                                    <img src="<?php echo app_url_html("assets/images/google-g-logo.svg"); ?>" alt="">
                                    <span>Continuer avec Google</span>
                                </button>
                            </form>
                            <div class="google-auth-separator"><span>ou</span></div>
                        </div>

                        <!-- FORM -->

                        <form action="" method="POST">

                            <input type="hidden"
                                   name="csrf_token"
                                   value="<?php echo htmlspecialchars($login_csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                            <!-- EMAIL -->

                            <div class="input-field">

                                <i class="material-icons prefix">

                                    email

                                </i>

                                <input
                                type="email"
                                id="email"
                                name="email"
                                required>

                                <label for="email">

                                    Adresse Email

                                </label>

                            </div>

                           <!-- PASSWORD -->

                            <div class="input-field password-field">

                                <i class="material-icons prefix">

                                    lock

                                </i>

                                <input
                                type="password"
                                id="password"
                                name="password"
                                required>

                                <label for="password">

                                    Mot de passe

                                </label>

                                <i class="material-icons password-toggle"
                                onclick="togglePassword('password', this)">

                                    visibility

                                </i>

                            </div>

                            <!-- OPTIONS -->

                            <div class="login-options">

                                <label>

                                    <input type="checkbox"
                                    name="remember_me"
                                    value="1">

                                    <span>

                                        Se souvenir de moi

                                    </span>

                                </label>

                                <a href="<?php echo app_url_html("mot-de-passe-oublie"); ?>"
                                class="forgot-link">

                                    Mot de passe oublié ?

                                </a>

                            </div>

               

                           <!-- ACTIONS -->

                            <div class="login-actions">

                                <!-- CANCEL -->

                                <a href="<?php echo app_url_html(""); ?>"
                                class="cancel-btn waves-effect">

                                    <i class="material-icons left">

                                        close

                                    </i>

                                    Annuler

                                </a>

                                <!-- LOGIN -->

                               <button
                                type="submit"
                                class="btn-large btn-register waves-effect waves-light">

                                    Connecter

                                    <i class="material-icons right">

                                        login

                                    </i>

                                </button>

                            </div>

                        </form>

                        <!-- REGISTER -->

                        <div class="center register-link">
                            <br>

                            <p>

                                Vous n’avez pas de compte ?

                                <a href="<?php echo app_url_html("inscription"); ?>">

                                    S’inscrire

                                </a>

                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

    <!-- MATERIALIZE JS -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

<script>

function togglePassword(inputId, icon){

    var input = document.getElementById(inputId);

    if(input.type === "password"){

        input.type = "text";
        icon.innerHTML = "visibility_off";

    }else{

        input.type = "password";
        icon.innerHTML = "visibility";

    }

}

</script>
</body>

</html>
