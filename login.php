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

        $stmt->bind_param(
            "s",
            $email
        );

        $stmt->execute();

        $result =
        $stmt->get_result();

        if($result->num_rows > 0){

            $user =
            $result->fetch_assoc();

            if(password_verify(
                $password,
                $user["password"]
            )){

                if($user["status"] != "active"){

                    $_SESSION["error"] =
                    "Votre compte est inactif ou suspendu.";

                }else{

                    $_SESSION["user_id"] =
                    $user["id"];

                    $_SESSION["role_id"] =
                    $user["role_id"];

                    $_SESSION["first_name"] =
                    $user["first_name"];

                    $_SESSION["last_name"] =
                    $user["last_name"];

                    $_SESSION["email"] =
                    $user["email"];

                    // Mise à jour dernière connexion

                    $update = $conn->prepare(

                        "UPDATE users
                         SET last_login = NOW()
                         WHERE id = ?"

                    );

                    $update->bind_param(
                        "i",
                        $user["id"]
                    );

                    $update->execute();

                    // REDIRECTION SELON ROLE

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
                            "Location: candidates/candidashboard.php"
                        );

                        exit();

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

    <!-- CSS -->

    <link rel="stylesheet"
    href="assets/css/style.css">

 <style>
/* FORM HEADER */

.form-header{

    background:
    linear-gradient(
    90deg,
    #081f78,
    #e83e8c
    );

    padding:30px 40px;

}

.form-header h4{

    color:white;

    font-size:30px;

    font-weight:700;

    margin:0;
}

/* LOGIN OPTIONS */

.login-options{

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-top:10px;
}

.forgot-link{

    color:#e83e8c;

    font-weight:600;
}

.forgot-link:hover{

    text-decoration:underline;
}

/* BUTTON */

.btn-register{

    margin-top:15px;

    border-radius:10px;

    width:100%;

    background:
    linear-gradient(
    90deg,
    #081f78,
    #e83e8c
    );
}

.btn-register:hover{

    background:
    linear-gradient(
    90deg,
    #e83e8c,
    #081f78
    );
}

/* RESPONSIVE */

@media(max-width:600px){

    .login-options{

        flex-direction:column;

        align-items:flex-start;

        gap:15px;
    }

}
/* CARD BORDER RADIUS */

.register-card{

    border-radius:25px !important;

    overflow:hidden;

    box-shadow:0 10px 25px rgba(0,0,0,0.12);

}
.form-header{

    border-top-left-radius:25px;

    border-top-right-radius:25px;

}
/* =========================================
   PAGE TITLE
========================================= */

.main-title{

    font-size:58px;

    font-weight:800;

    color:#081f78;

    line-height:1.1;

    margin-bottom:15px;

    text-align:center;
}

.subtitle{

    font-size:17px;

    font-weight:400;

    color:#6b7280;

    line-height:1.7;

    max-width:700px;

    margin:auto;

    text-align:center;
}

/* RESPONSIVE */

@media(max-width:992px){

    .main-title{

        font-size:58px;
    }

    .subtitle{

        font-size:20px;
    }

}

@media(max-width:600px){

    .main-title{

        font-size:42px;
    }

    .subtitle{

        font-size:18px;

        line-height:1.6;
    }

}

/* =========================================
   BACK BUTTON
========================================= */

.back-home{

    width:100%;
    max-width:1200px;

    margin:0 auto;

    padding:25px 20px 0 20px;
}

.back-btn{

    display:inline-flex;

    align-items:center;

    gap:8px;

    padding:12px 22px;

    border-radius:12px;

    background:white;

    color:#081f78;

    font-weight:600;

    box-shadow:0 4px 12px rgba(0,0,0,0.08);

    transition:0.3s;
}

.back-btn i{

    font-size:20px;
}

.back-btn:hover{

    background:
    linear-gradient(
    45deg,
    #081f78,
    #e83e8c
    );

    color:white;

    transform:translateY(-2px);
}

.login-actions{

    display:flex;

    align-items:center;

    gap:20px;

    margin-top:35px;

    flex-wrap:wrap;
}

.cancel-btn{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:8px;

    min-width:160px;

    height:58px;

    padding:0 28px;

    border-radius:14px;

    background:white;

    color:#081f78;

    font-weight:600;

    font-size:16px;

    box-shadow:0 4px 12px rgba(0,0,0,0.08);

    transition:0.3s;
}

.cancel-btn:hover{

    background:#f3f6ff;

    transform:translateY(-2px);
}

.login-actions .btn-register{

    flex:1;

    display:flex;

    align-items:center;

    justify-content:center;
}

@media(max-width:600px){

    .login-actions{

        flex-direction:column-reverse;
    }

    .cancel-btn,
    .login-actions .btn-register{

        width:100%;
    }

}

.login-error{
    color: #e53935;
    font-size: 14px;
    font-weight: 500;
    text-align: center;
    margin-bottom: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
}
.password-field{
    position: relative;
}

.password-toggle{
    position: absolute;
    right: 10px;
    top: 15px;
    cursor: pointer;
    color: #757575;
    user-select: none;
    z-index: 10;
}
</style>

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