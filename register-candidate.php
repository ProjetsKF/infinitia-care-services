<?php


session_start();
require_once("config/app.php");

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <base href="<?php echo app_url_html(""); ?>">

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>

        Devenir Intervenant |
        Infinitia Care Services

    </title>
 <link rel="icon" type="image/x-icon" href="<?php echo app_url_html("assets/images/ico.ico"); ?>">
    <!-- MATERIALIZE CSS -->

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <!-- GOOGLE ICONS -->

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <!-- GOOGLE FONT -->

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"rel="stylesheet">

      <link rel="stylesheet" href="<?php echo app_url_html("assets/css/style.css"); ?>">

</head>

<body>

    <section class="register-section">

        <div class="container">

            <!-- TITRE -->
        
            <div class="center">

                <h2 class="page-title">

                    Devenir Intervenant

                </h2>

                <p class="subtitle">

                    Complétez votre profil professionnel.

                </p>

            </div>

            <br>

            <div class="row">

                <div class="col s12 m10 offset-m1 l8 offset-l2">

                    <div class="card register-card">

                        <!-- HEADER -->

                        <div class="card-header">

                            <h4>

                              Informations du compte

                            </h4>

                        </div>

                        <!-- FORM -->

                        <div class="card-content">

 <!-- AFFICHAGE DES MESSAGES -->

                            <?php if(isset($_SESSION['error'])): ?>

                            <div class="card-panel red lighten-4 red-text text-darken-4"
                                 style="border-radius:10px;margin-bottom:20px;">

                                <i class="material-icons left">error</i>

                                <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); ?>

                            </div>

                            <?php unset($_SESSION['error']); ?>

                            <?php endif; ?>

                            <?php if(isset($_SESSION['success'])): ?>

                            <div class="card-panel green lighten-4 green-text text-darken-4"
                                 style="border-radius:10px;margin-bottom:20px;">

                                <i class="material-icons left">check_circle</i>

                                <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); ?>

                            </div>

                            <?php unset($_SESSION['success']); ?>

                            <?php endif; ?>

<form
action="<?php echo app_url_html("inscription/intervenant/traiter"); ?>"
method="POST"
enctype="multipart/form-data">

    <!-- =========================
         INFORMATIONS COMPTE
    ========================== -->

    <div class="row">

        <div class="input-field col s12 m6">

            <input
            type="text"
            name="first_name"
            required>

            <label>Prénom</label>

        </div>

        <div class="input-field col s12 m6">

            <input
            type="text"
            name="last_name"
            required>

            <label>Nom</label>

        </div>

    </div>

    <div class="row">

        <div class="input-field col s12">

            <input
            type="email"
            name="email"
            required>

            <label>Adresse e-mail</label>

        </div>

    </div>

    <div class="row">

        <div class="input-field col s12">

            <input
            type="tel"
            name="phone"
            required>

            <label>Téléphone</label>

        </div>

    </div>

    <div class="row">

        <div class="input-field col s12 m6">

            <input
            type="password"
            name="password"
            id="password"
            required>

            <label for="password">
                Mot de passe
            </label>

        </div>

        <div class="input-field col s12 m6">

            <input
            type="password"
            name="confirm_password"
            id="confirm_password"
            required>

            <label for="confirm_password">
                Confirmer le mot de passe
            </label>

        </div>

    </div>

    <!-- =========================
         PHOTO
    ========================== -->

    <div class="file-field input-field">

        <div class="btn">

            <span>Photo</span>

            <input
            type="file"
            name="profile_photo"
            accept="image/*">

        </div>

        <div class="file-path-wrapper">

            <input
            class="file-path validate"
            type="text"
            placeholder="Photo de profil">

        </div>

    </div>

<!-- =========================
     CONSENTEMENT À L'IMAGE
========================== -->

<div class="row">

    <div class="col s12">

        <div class="card-panel blue lighten-5 consent-photo-box">

            <div class="consent-photo-title">

                <i class="material-icons">
                    photo_camera
                </i>

                <strong>
                    Consentement à l’utilisation de la photographie
                </strong>

            </div>

            <p class="consent-photo-text">

                Vous êtes libre d’autoriser ou de refuser l’affichage public de
                votre photographie. En cas de refus, votre profil professionnel
                restera visible dans la rubrique « Nos intervenants », avec une
                image générique à la place de votre vraie photo.

            </p>

            <label>

                <input
                    type="checkbox"
                    name="photo_consent"
                    value="1">

                <span>

                    J’autorise expressément INFINITIA CARE SERVICES à publier
                    et à utiliser ma photographie dans le cadre de la
                    présentation de mon profil et des services proposés sur
                    la plateforme.

                </span>

            </label>

            <p class="consent-photo-legal">

                Ce consentement est donné conformément à l’article 23 de
                l’Ordonnance-loi n° 86-033 du 5 avril 1986 et aux dispositions
                applicables du Code du numérique de la République
                Démocratique du Congo relatives à la protection des données
                personnelles.
                Vous pourrez modifier ou retirer ce consentement ultérieurement
                depuis les paramètres de votre compte.

            </p>

        </div>

    </div>

</div>

    <!-- =========================
         INFORMATIONS PERSONNELLES
    ========================== -->

    <div class="row">

        <div class="input-field col s12 m6">

            <input
            type="date"
            name="birth_date"
            required>

            <label class="active">
                Date de naissance
            </label>

        </div>

        <div class="input-field col s12 m6">

            <select name="gender" required>

                <option value="" disabled selected>
                    Choisir
                </option>

                <option value="Homme">
                    Homme
                </option>

                <option value="Femme">
                    Femme
                </option>

            </select>

            <label>Sexe</label>

        </div>

    </div>

    <div class="row">

        <div class="input-field col s12">

            <textarea
            name="address"
            class="materialize-textarea"
            required></textarea>

            <label>Adresse</label>

        </div>

    </div>

    <div class="row">

        <div class="input-field col s12 m6">

            <input
            type="text"
            name="city"
            required>

            <label>Ville</label>

        </div>

        <div class="input-field col s12 m6">

            <input
            type="text"
            name="nationality"
            required>

            <label>Nationalité</label>

        </div>

    </div>

    <div class="row">

        <div class="input-field col s12 m6">

            <input
            type="text"
            name="marital_status"
            required>

            <label>État civil</label>

        </div>

        <div class="input-field col s12 m6">

            <input
            type="text"
            name="education_level"
            required>

            <label>Niveau d'études</label>

        </div>

    </div>

    <div class="row">

        <div class="input-field col s12 m6">

            <input
            type="number"
            min="0"
            name="experience_years"
            value="0">

            <label class="active">
                Années d'expérience
            </label>

        </div>

        <div class="input-field col s12 m6">

            <input
            type="text"
            name="emergency_contact"
            required>

            <label>
                Contact d'urgence
            </label>

        </div>

    </div>

    <div class="row">

        <div class="input-field col s12">

            <textarea
            name="bio"
            class="materialize-textarea"></textarea>

            <label>
                Présentation / Biographie
            </label>

        </div>

    </div>

 <!-- =========================
     CONDITIONS D'UTILISATION
========================== -->

<div class="row">

    <div class="col s12">

        <label>

            <input
                type="checkbox"
                name="terms_accepted"
                value="1"
                required>

            <span>
                J’ai lu et j’accepte les conditions d’utilisation ainsi que
                la politique de confidentialité d’INFINITIA CARE SERVICES.
            </span>

        </label>

    </div>

</div>

    <!-- =========================
         ACTIONS
    ========================== -->

    <div class="form-actions">

        <a href="<?php echo app_url_html("inscription"); ?>"
           class="cancel-btn waves-effect">

            Annuler

        </a>

        <button
        type="submit"
        class="btn-large btn-register waves-effect waves-light">

            Créer mon compte

        </button>

    </div>

</form>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

    <!-- JQUERY -->

    <script
    src="https://code.jquery.com/jquery-3.7.1.min.js">
    </script>

    <!-- MATERIALIZE JS -->

    <script
    src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js">
    </script>

    <script>

        document.addEventListener('DOMContentLoaded', function(){

            M.AutoInit();

        });

    </script>

    <script>

function togglePassword(inputId, icon){

    let input = document.getElementById(inputId);

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
