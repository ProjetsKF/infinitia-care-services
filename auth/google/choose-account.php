<?php

require_once(dirname(dirname(__DIR__)) . "/config/google-oauth.php");
infinitia_session_start();

$pending = infinitia_google_pending_identity();

if($pending === false || $pending["intent"] !== "login"){
    $_SESSION["error"] = "Aucune identite Google valide n'est en attente.";
    header("Location: " . app_url("login"));
    exit();
}

$choice_csrf = infinitia_csrf_token("google_role_choice_csrf");
$email = $pending["identity"]["email"];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte Google non associé | INFINITIA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo app_url_html("assets/css/style.css"); ?>">
</head>
<body class="google-choice-page">
    <main class="container google-choice-container">
        <div class="card google-choice-card">
            <div class="card-content center-align">
                <i class="material-icons google-choice-icon">account_circle</i>
                <h4>Aucun compte Google associé</h4>
                <p>
                    L’adresse <strong><?php echo htmlspecialchars($email, ENT_QUOTES, "UTF-8"); ?></strong>
                    n’est liée à aucun compte INFINITIA.
                </p>
                <p>Choisissez le type de compte à créer. Ce choix sera vérifié côté serveur.</p>

                <div class="google-choice-actions">
                    <form action="<?php echo app_url_html("auth/google/select-role.php"); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($choice_csrf, ENT_QUOTES, "UTF-8"); ?>">
                        <input type="hidden" name="account_type" value="client">
                        <button class="btn-large blue darken-4 waves-effect waves-light" type="submit">
                            <i class="material-icons left">business_center</i>
                            Créer un compte Client
                        </button>
                    </form>

                    <form action="<?php echo app_url_html("auth/google/select-role.php"); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($choice_csrf, ENT_QUOTES, "UTF-8"); ?>">
                        <input type="hidden" name="account_type" value="candidate">
                        <button class="btn-large pink waves-effect waves-light" type="submit">
                            <i class="material-icons left">support_agent</i>
                            Créer un compte Intervenante
                        </button>
                    </form>

                    <a class="btn-flat" href="<?php echo app_url_html("login"); ?>">Revenir à la connexion</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
