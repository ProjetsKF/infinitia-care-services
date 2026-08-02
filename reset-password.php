<?php

session_start();

require_once("config/database.php");
require_once("config/auth.php");

infinitia_delete_expired_password_reset_tokens($conn);

$selector = isset($_GET["selector"]) ? trim($_GET["selector"]) : "";
$validator = isset($_GET["validator"]) ? trim($_GET["validator"]) : "";
$link_error = "";
$form_error = "";
$token_data = array();

function infinitia_reset_token_parameters_valid($selector, $validator)
{
    return strlen($selector) === 32
        && strlen($validator) === 64
        && ctype_xdigit($selector)
        && ctype_xdigit($validator);
}

function infinitia_load_reset_token($conn, $selector, $validator)
{
    $data = array();
    $sql = "
    SELECT
        prt.id,
        prt.user_id,
        prt.validator_hash,
        prt.expires_at,
        prt.used,
        u.status
    FROM password_reset_tokens prt
    INNER JOIN users u
    ON u.id = prt.user_id
    WHERE prt.selector = ?
    LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if(!$stmt){

        error_log("Password reset token lookup prepare error: " . mysqli_error($conn));
        return $data;

    }

    mysqli_stmt_bind_param($stmt, "s", $selector);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result(
        $stmt,
        $token_id,
        $user_id,
        $validator_hash,
        $expires_at,
        $used,
        $status
    );

    if(mysqli_stmt_fetch($stmt)){

        $candidate_hash = hash("sha256", $validator);

        if((int)$used === 0
            && strtotime($expires_at) > time()
            && $status == "active"
            && infinitia_hash_equals($validator_hash, $candidate_hash)){

            $data = array(
                "token_id" => $token_id,
                "user_id" => $user_id,
                "validator_hash" => $validator_hash,
                "expires_at" => $expires_at
            );

        }

    }

    mysqli_stmt_close($stmt);
    return $data;
}

if(!infinitia_reset_token_parameters_valid($selector, $validator)){

    $link_error = "Ce lien de reinitialisation est invalide ou a expire.";

}else{

    $token_data = infinitia_load_reset_token($conn, $selector, $validator);

    if(count($token_data) <= 0){

        $link_error = "Ce lien de reinitialisation est invalide ou a expire.";

    }

}

if($_SERVER["REQUEST_METHOD"] == "POST" && $link_error == ""){

    $csrf_token = isset($_POST["csrf_token"]) ? $_POST["csrf_token"] : "";
    $password = isset($_POST["password"]) ? $_POST["password"] : "";
    $password_confirmation = isset($_POST["password_confirmation"]) ? $_POST["password_confirmation"] : "";

    if(!infinitia_verify_csrf_token("reset_password_csrf", $csrf_token)){

        $form_error = "La demande a expire. Veuillez reessayer.";

    }elseif($password == "" || $password_confirmation == ""){

        $form_error = "Veuillez remplir tous les champs.";

    }elseif($password != $password_confirmation){

        $form_error = "Les mots de passe ne correspondent pas.";

    }else{

        $strength_error = infinitia_validate_password_strength($password);

        if($strength_error != ""){

            $form_error = $strength_error;

        }else{

            if(function_exists("mysqli_begin_transaction")){

                mysqli_begin_transaction($conn);

            }else{

                mysqli_autocommit($conn, false);

            }

            $fresh_token = infinitia_load_reset_token($conn, $selector, $validator);

            if(count($fresh_token) <= 0){

                mysqli_rollback($conn);
                mysqli_autocommit($conn, true);
                $link_error = "Ce lien de reinitialisation est invalide ou a expire.";

            }else{

                $user_id = (int)$fresh_token["user_id"];
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $ok = true;

                $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);

                if(!$stmt){

                    error_log("Password reset user update prepare error: " . mysqli_error($conn));
                    $ok = false;

                }else{

                    mysqli_stmt_bind_param($stmt, "si", $password_hash, $user_id);

                    if(!mysqli_stmt_execute($stmt)){

                        error_log("Password reset user update execute error: " . mysqli_stmt_error($stmt));
                        $ok = false;

                    }

                    mysqli_stmt_close($stmt);

                }

                if($ok){

                    $sql = "UPDATE password_reset_tokens SET used = 1 WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);

                    if(!$stmt){

                        error_log("Password reset token mark used prepare error: " . mysqli_error($conn));
                        $ok = false;

                    }else{

                        $token_id = (int)$fresh_token["token_id"];
                        mysqli_stmt_bind_param($stmt, "i", $token_id);

                        if(!mysqli_stmt_execute($stmt)){

                            error_log("Password reset token mark used execute error: " . mysqli_stmt_error($stmt));
                            $ok = false;

                        }

                        mysqli_stmt_close($stmt);

                    }

                }

                if($ok){

                    $ok = infinitia_delete_user_password_reset_tokens($conn, $user_id);

                }

                if($ok){

                    $sql = "DELETE FROM remember_tokens WHERE user_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);

                    if(!$stmt){

                        error_log("Password reset remember delete prepare error: " . mysqli_error($conn));
                        $ok = false;

                    }else{

                        mysqli_stmt_bind_param($stmt, "i", $user_id);

                        if(!mysqli_stmt_execute($stmt)){

                            error_log("Password reset remember delete execute error: " . mysqli_stmt_error($stmt));
                            $ok = false;

                        }

                        mysqli_stmt_close($stmt);

                    }

                }

                if($ok){

                    mysqli_commit($conn);
                    mysqli_autocommit($conn, true);
                    unset($_SESSION["reset_password_csrf"]);
                    $_SESSION["success"] = "Votre mot de passe a ete reinitialise avec succes. Vous pouvez maintenant vous connecter.";
                    header("Location: " . app_url("login"));
                    exit();

                }

                mysqli_rollback($conn);
                mysqli_autocommit($conn, true);
                $form_error = "Une erreur est survenue. Veuillez reessayer.";

            }

        }

    }

}

$csrf_token = infinitia_csrf_token("reset_password_csrf");

?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <base href="<?php echo app_url_html(""); ?>">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reinitialiser le mot de passe | Infinitia Care Services</title>
    <link rel="icon" type="image/x-icon" href="<?php echo app_url_html("assets/images/ico.ico"); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo app_url_html("assets/css/style.css"); ?>">

</head>

<body>

<section class="register-section">

    <div class="container">

        <h1 class="main-title">Nouveau mot de passe</h1>
        <p class="subtitle">Choisissez un nouveau mot de passe securise.</p>

        <br><br>

        <div class="row">

            <div class="col s12 m8 l6 offset-m2 offset-l3">

                <?php if($link_error != ""){ ?>
                    <div class="login-error">
                        <i class="material-icons tiny">error_outline</i>
                        <?php echo htmlspecialchars($link_error); ?>
                    </div>
                    <div class="center">
                        <a href="<?php echo app_url_html("mot-de-passe-oublie"); ?>" class="btn blue">Demander un nouveau lien</a>
                    </div>
                <?php }else{ ?>

                    <?php if($form_error != ""){ ?>
                        <div class="login-error">
                            <i class="material-icons tiny">error_outline</i>
                            <?php echo htmlspecialchars($form_error); ?>
                        </div>
                    <?php } ?>

                    <div class="card register-card">

                        <div class="form-header">
                            <h4>Reinitialisation</h4>
                        </div>

                        <div class="card-content">

                            <div class="icon-circle client-bg">
                                <i class="material-icons">lock</i>
                            </div>

                            <br>

                            <form action="<?php echo app_url_with_query_html("reinitialiser-mot-de-passe", array("selector" => $selector, "validator" => $validator)); ?>" method="POST">

                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                                <div class="input-field password-field">
                                    <i class="material-icons prefix">lock</i>
                                    <input type="password"
                                           id="password"
                                           name="password"
                                           autocomplete="new-password"
                                           required>
                                    <label for="password">Nouveau mot de passe</label>
                                    <i class="material-icons password-toggle" onclick="togglePassword('password', this)">visibility</i>
                                </div>

                                <div class="input-field password-field">
                                    <i class="material-icons prefix">lock_outline</i>
                                    <input type="password"
                                           id="password_confirmation"
                                           name="password_confirmation"
                                           autocomplete="new-password"
                                           required>
                                    <label for="password_confirmation">Confirmation</label>
                                    <i class="material-icons password-toggle" onclick="togglePassword('password_confirmation', this)">visibility</i>
                                </div>

                                <p class="grey-text text-darken-1">
                                    Minimum 8 caracteres, une majuscule, une minuscule et un chiffre.
                                </p>

                                <div class="login-actions">
                                    <a href="<?php echo app_url_html("login"); ?>" class="cancel-btn waves-effect">
                                        <i class="material-icons left">arrow_back</i>
                                        Connexion
                                    </a>
                                    <button type="submit" class="btn-large btn-register waves-effect waves-light">
                                        Reinitialiser
                                        <i class="material-icons right">lock_reset</i>
                                    </button>
                                </div>

                            </form>

                        </div>

                    </div>

                <?php } ?>

            </div>

        </div>

    </div>

</section>

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
