<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once("config/database.php");

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if(empty($email) || empty($password)){

        $_SESSION["error"] =
        "Veuillez remplir tous les champs.";

    }else{

        $stmt = $conn->prepare(

            "SELECT *
             FROM users
             WHERE email = ?"

        );

        if(!$stmt){

            $_SESSION["error"] =
            "Erreur de connexion à la base de données.";

        }else{

            $stmt->bind_param(
                "s",
                $email
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if($result->num_rows > 0){

                $user = $result->fetch_assoc();

                if(password_verify(
                    $password,
                    $user["password"]
                )){

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

                        $_SESSION["user_id"]    = $user["id"];
                        $_SESSION["role_id"]    = $user["role_id"];
                        $_SESSION["first_name"] = $user["first_name"];
                        $_SESSION["last_name"]  = $user["last_name"];
                        $_SESSION["email"]      = $user["email"];

                        /* =====================================
                           DERNIERE CONNEXION
                        ====================================== */

                        $update = $conn->prepare(

                            "UPDATE users
                             SET last_login = NOW()
                             WHERE id = ?"

                        );

                        if($update){

                            $update->bind_param(
                                "i",
                                $user["id"]
                            );

                            $update->execute();
                            $update->close();

                        }

                        /* =====================================
                           REDIRECTION SELON LE ROLE
                        ====================================== */

                        if($user["role_id"] == 1){

                            header(
                                "Location: admin/dashboard.php"
                            );
                            exit();

                        }
                        elseif($user["role_id"] == 2){

                            header(
                                "Location: clients/clidashboard.php"
                            );
                            exit();

                        }
                        elseif($user["role_id"] == 3){

                            header(
                                "Location: intervenants/candidashboard.php"
                            );
                            exit();

                        }
                        else{

                            $_SESSION["error"] =
                            "Rôle utilisateur invalide.";

                        }

                    }

                }else{

                    $_SESSION["error"] =
                    "Mot de passe incorrect.";

                }

            }else{

                $_SESSION["error"] =
                "Adresse email introuvable.";

            }

            $stmt->close();

        }

    }

}

?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>Connexion | Infinitia Care Services</title>

    <link rel="icon"
    type="image/x-icon"
    href="assets/images/ico.ico">

    <!-- MATERIALIZE -->

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <!-- ICONS -->

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <!-- FONT -->

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">
     <link rel="stylesheet" href="assets/css/style.css">

    <!-- CSS -->

    <link rel="stylesheet"
    href="assets/css/style.css">


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
        echo $_SESSION['error'];
        unset($_SESSION['error']);
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

                        <!-- FORM -->

                        <form action="" method="POST">

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

                                    <input type="checkbox">

                                    <span>

                                        Se souvenir de moi

                                    </span>

                                </label>

                                <a href="#"
                                class="forgot-link">

                                    Mot de passe oublié ?

                                </a>

                            </div>

               

                           <!-- ACTIONS -->

                            <div class="login-actions">

                                <!-- CANCEL -->

                                <a href="index.php"
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

                                <a href="register.php">

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