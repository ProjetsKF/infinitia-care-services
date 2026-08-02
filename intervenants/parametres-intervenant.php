<?php

session_start();

require_once("../config/database.php");

function redirect_parameters()
{
    header("Location: " . app_url("intervenant/parametres"));
    exit();
}

function csrf_values_match($known, $submitted)
{
    if(function_exists("hash_equals")){
        return hash_equals($known, $submitted);
    }

    if(strlen($known) != strlen($submitted)){
        return false;
    }

    $result = 0;
    $i = 0;

    for($i = 0; $i < strlen($known); $i++){
        $result |= ord($known[$i]) ^ ord($submitted[$i]);
    }

    return $result === 0;
}

if(!isset($_SESSION["user_id"])){
    header("Location: " . app_url("login"));
    exit();
}

$user_id = (int)$_SESSION["user_id"];

if(!isset($_SESSION["candidate_settings_csrf"]) || $_SESSION["candidate_settings_csrf"] == ""){
    $csrf_bytes = openssl_random_pseudo_bytes(32);

    if($csrf_bytes === false){
        $_SESSION["candidate_settings_csrf"] = hash("sha256", uniqid((string)mt_rand(), true));
    }else{
        $_SESSION["candidate_settings_csrf"] = bin2hex($csrf_bytes);
    }
}

$csrf_token = $_SESSION["candidate_settings_csrf"];

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $submitted_token = isset($_POST["csrf_token"]) ? (string)$_POST["csrf_token"] : "";

    if($submitted_token == "" || !csrf_values_match($csrf_token, $submitted_token)){
        $_SESSION["error"] = "La session du formulaire a expiré. Veuillez réessayer.";
        redirect_parameters();
    }
}

$availability_status_db = "";
$photo_consent_db = 0;
$photo_consent_date_db = NULL;
$candidate_exists = false;

$sql = "
    SELECT
        availability_status,
        photo_consent,
        photo_consent_date
    FROM candidates
    WHERE user_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if($stmt){
    mysqli_stmt_bind_param($stmt, "i", $user_id);

    if(mysqli_stmt_execute($stmt)){
        mysqli_stmt_bind_result(
            $stmt,
            $availability_status_db,
            $photo_consent_db,
            $photo_consent_date_db
        );

        $candidate_exists = mysqli_stmt_fetch($stmt) ? true : false;
    }

    mysqli_stmt_close($stmt);
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!$candidate_exists){
        $_SESSION["error"] = "Profil intervenant introuvable.";
        redirect_parameters();
    }

    if(isset($_POST["update_availability"])){
        $availability_status = isset($_POST["availability_status"])
            ? trim($_POST["availability_status"])
            : "";
        $allowed_statuses = array("disponible", "occupé", "hors_ligne");

        if(!in_array($availability_status, $allowed_statuses, true)){
            $_SESSION["error"] = "Statut de disponibilité invalide.";
            redirect_parameters();
        }

        $sql = "UPDATE candidates SET availability_status = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if($stmt){
            mysqli_stmt_bind_param($stmt, "si", $availability_status, $user_id);

            if(mysqli_stmt_execute($stmt)){
                $_SESSION["success"] = "Votre disponibilité a été mise à jour.";
            }else{
                $_SESSION["error"] = "Une erreur est survenue lors de la mise à jour de votre disponibilité.";
            }

            mysqli_stmt_close($stmt);
        }else{
            $_SESSION["error"] = "Une erreur est survenue lors de la mise à jour de votre disponibilité.";
        }

        redirect_parameters();
    }

    if(isset($_POST["update_photo_consent"])){
        $photo_consent_raw = isset($_POST["photo_consent"])
            ? (string)$_POST["photo_consent"]
            : "";

        if($photo_consent_raw !== "0" && $photo_consent_raw !== "1"){
            $_SESSION["error"] = "Choix de confidentialité invalide.";
            redirect_parameters();
        }

        $photo_consent = (int)$photo_consent_raw;
        $sql = "
            UPDATE candidates
            SET
                photo_consent = ?,
                photo_consent_date = CASE
                    WHEN ? = 1 THEN NOW()
                    ELSE NULL
                END
            WHERE user_id = ?
        ";
        $stmt = mysqli_prepare($conn, $sql);

        if($stmt){
            mysqli_stmt_bind_param(
                $stmt,
                "iii",
                $photo_consent,
                $photo_consent,
                $user_id
            );

            if(mysqli_stmt_execute($stmt)){
                if($photo_consent === 1){
                    $_SESSION["success"] = "L’affichage public de votre photo a été autorisé.";
                }else{
                    $_SESSION["success"] = "Votre photo est maintenant masquée sur la page publique. Une image générique sera affichée à sa place.";
                }
            }else{
                $_SESSION["error"] = "Une erreur est survenue lors de la mise à jour de la confidentialité de votre photo.";
            }

            mysqli_stmt_close($stmt);
        }else{
            $_SESSION["error"] = "Une erreur est survenue lors de la mise à jour de la confidentialité de votre photo.";
        }

        redirect_parameters();
    }

    $_SESSION["error"] = "Action invalide.";
    redirect_parameters();
}

$current_status = $candidate_exists && $availability_status_db != ""
    ? $availability_status_db
    : "hors_ligne";
$current_photo_consent = $candidate_exists ? (int)$photo_consent_db : 0;
$current_photo_consent_date = $candidate_exists ? $photo_consent_date_db : NULL;

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>

        Mes paramètres | INFINITIA

    </title>

    <link rel="icon"
          type="image/x-icon"
          href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
          rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="<?php echo app_url_html("assets/css/style.css"); ?>">

</head>

<body>

<div class="dashboard">

    <?php

    $current_page = "parametres";

    include("menuin.php");

    ?>


<div class="main-content">

    <div class="topbar">

        <div class="page-title">
            Paramètres
        </div>

    </div>

    <?php if(isset($_SESSION["success"])){ ?>
        <div class="card-panel green lighten-4 green-text text-darken-4">
            <i class="material-icons left">check_circle</i>
            <?php echo htmlspecialchars($_SESSION["success"], ENT_QUOTES, "UTF-8"); ?>
        </div>
        <?php unset($_SESSION["success"]); ?>
    <?php } ?>

    <?php if(isset($_SESSION["error"])){ ?>
        <div class="card-panel red lighten-4 red-text text-darken-4">
            <i class="material-icons left">error</i>
            <?php echo htmlspecialchars($_SESSION["error"], ENT_QUOTES, "UTF-8"); ?>
        </div>
        <?php unset($_SESSION["error"]); ?>
    <?php } ?>

    <?php if(!$candidate_exists){ ?>
        <div class="card-panel orange lighten-4 orange-text text-darken-4">
            Profil intervenant introuvable. Les paramètres ne peuvent pas être modifiés.
        </div>
    <?php } ?>

    <div class="row">

        <div class="col s12 m6">

            <div class="card blue darken-3 white-text">

                <div class="card-content">

                    <span class="card-title">
                        Statut actuel
                    </span>

                    <h4>

                        <?php

                        echo ucfirst(
                            str_replace(
                                '_',
                                ' ',
                                $current_status
                            )
                        );

                        ?>

                    </h4>

                </div>

            </div>

        </div>

        <div class="col s12 m6">

            <div class="card green darken-2 white-text">

                <div class="card-content">

                    <span class="card-title">
                        Dernière mise à jour
                    </span>

                    <h4>
                        Aujourd'hui
                    </h4>

                </div>

            </div>

        </div>

    </div>

    <div class="table-card">

        <div class="table-title">
            Gestion de la disponibilité
        </div>

        <div class="row">

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="input-field col s12 m6">

                    <select name="availability_status">

                        <option
                            value="disponible"
                            <?php

                            if($current_status == 'disponible')
                            {
                                echo 'selected="selected"';
                            }

                            ?>
                        >
                            Disponible
                        </option>

                        <option
                            value="occupé"
                            <?php

                            if($current_status == 'occupé')
                            {
                                echo 'selected="selected"';
                            }

                            ?>
                        >
                            Occupé
                        </option>

                        <option
                            value="hors_ligne"
                            <?php

                            if($current_status == 'hors_ligne')
                            {
                                echo 'selected="selected"';
                            }

                            ?>
                        >
                            Hors ligne
                        </option>

                    </select>

                    <label>
                        Statut de disponibilité
                    </label>

                </div>

                <div class="col s12">

                    <button
                        type="submit"
                        name="update_availability"
                        class="btn-large blue"
                        <?php echo !$candidate_exists ? "disabled" : ""; ?>>

                        <i class="material-icons left">
                            save
                        </i>

                        Mettre à jour

                    </button>

                </div>

            </form>

        </div>

    </div>

    <div class="table-card" style="margin-top:30px;">

        <div class="table-title">
            Confidentialité de la photo de profil
        </div>

        <div class="row">

            <div class="col s12">
                <p>
                    Vous pouvez choisir si votre vraie photo est visible sur la page publique des intervenants.
                    Si vous la masquez, une image générique sera affichée à la place. Votre profil restera visible.
                </p>

                <?php if($current_photo_consent === 1){ ?>
                    <span class="new badge green" data-badge-caption="">
                        Photo publique autorisée
                    </span>
                <?php }else{ ?>
                    <span class="new badge orange" data-badge-caption="">
                        Photo publique masquée
                    </span>
                <?php } ?>

                <?php if($current_photo_consent === 1 && $current_photo_consent_date){ ?>
                    <p class="grey-text" style="margin-top:20px;">
                        Consentement enregistré le
                        <?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($current_photo_consent_date)), ENT_QUOTES, "UTF-8"); ?>.
                    </p>
                <?php } ?>
            </div>

            <form method="POST">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="col s12" style="margin-top:15px;">
                    <p>
                        <label>
                            <input
                                name="photo_consent"
                                type="radio"
                                value="1"
                                <?php echo $current_photo_consent === 1 ? 'checked="checked"' : ''; ?>>
                            <span>Autoriser l’affichage public de ma photo</span>
                        </label>
                    </p>

                    <p>
                        <label>
                            <input
                                name="photo_consent"
                                type="radio"
                                value="0"
                                <?php echo $current_photo_consent === 0 ? 'checked="checked"' : ''; ?>>
                            <span>Masquer ma photo sur la page publique</span>
                        </label>
                    </p>
                </div>

                <div class="col s12" style="margin-top:10px;">
                    <button
                        type="submit"
                        name="update_photo_consent"
                        class="btn-large blue"
                        <?php echo !$candidate_exists ? "disabled" : ""; ?>>
                        <i class="material-icons left">privacy_tip</i>
                        Enregistrer mon choix
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>


</div>





<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>


<script>

document.addEventListener('DOMContentLoaded', function() {

    var modals = document.querySelectorAll('.modal');
    M.Modal.init(modals);

    var selects = document.querySelectorAll('select');
    M.FormSelect.init(selects);

});

</script>

</body>
</html>
