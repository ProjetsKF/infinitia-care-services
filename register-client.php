<?php
session_start();
require_once("config/app.php");

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <?php require_once(__DIR__ . "/includes/pwa-head.php"); ?>

    <base href="<?php echo app_url_html(""); ?>">

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>

        Créer un compte client |
        Infinitia Care Services

    </title>

    <!-- FAVICON -->

    <link rel="icon"
    type="image/x-icon"
    href="<?php echo app_url_html("assets/images/ico.ico"); ?>">

    <!-- MATERIALIZE CSS -->

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

    <!-- GOOGLE ICONS -->

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
    rel="stylesheet">

    <!-- GOOGLE FONT -->

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">

    <style>

        *{
            font-family:'Poppins', sans-serif;
        }

        body{

            background:#f4f6fb;
        }

        .register-section{

            padding:60px 0;
        }

        .register-card{

            border-radius:20px;
            overflow:hidden;
        }

        .card-header{

            background:
            linear-gradient(
            45deg,
            #081f78,
            #e83e8c
            );

            padding:35px;

            color:white;
        }

        .card-header h4{

            margin:0;
            font-weight:700;
        }

        .card-content{

            padding:40px !important;
        }

        .input-field input:focus{

            border-bottom:1px solid #081f78 !important;
            box-shadow:0 1px 0 0 #081f78 !important;
        }

        .input-field textarea:focus{

            border-bottom:1px solid #081f78 !important;
            box-shadow:0 1px 0 0 #081f78 !important;
        }

        .input-field label.active{

            color:#081f78 !important;
        }

        .btn-register{

            width:100%;
            border-radius:10px;
            height:55px;
            line-height:55px;

            background:
            linear-gradient(
            45deg,
            #081f78,
            #e83e8c
            );
        }

        .page-title{

            font-size:42px;
            font-weight:700;

            color:#081f78;
        }

        .subtitle{

            color:#666;
        }

        .form-actions{

    display:flex;

    align-items:center;

    justify-content:space-between;

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

.form-actions .btn-register{

    flex:1;

    display:flex;

    align-items:center;

    justify-content:center;
}

@media(max-width:600px){

    .form-actions{

        flex-direction:column-reverse;
    }

    .cancel-btn,
    .form-actions .btn-register{

        width:100%;
    }

}

.file-info{

    display:flex;

    align-items:center;

    gap:8px;

    background:#eef4ff;

    color:#081f78;

    padding:12px 18px;

    border-radius:12px;

    margin-bottom:10px;

    font-size:14px;

    font-weight:500;
}

.file-info i{

    font-size:20px;
}
.form-message{
     padding: 12px 18px;
    border-radius:8px;
    position:relative;
    font-weight:500;
    margin-bottom: 25px;
}

.error-message{
    background:#ffebee;
    color:#c62828;
    border-left: 4px solid #d32f2f;
    border-radius: 0 0 10px 10px;
}

.close-message{
    position:absolute;
    right:15px;
    top:10px;
    cursor:pointer;
    font-size:22px;
    font-weight:bold;
}
.password-toggle{
    position: absolute;
    right: 10px;
    top: 15px;
    cursor: pointer;
    color: #757575;
    user-select: none;
}

.input-field{
    position: relative;
}
    </style>

</head>

<body>

    <section class="register-section">

        <div class="container">

           

            <!-- TITRE -->

            <div class="center">

                <h2 class="page-title">

                    Créer un compte Client

                </h2>

                <p class="subtitle">

                    Complétez les informations
                    de votre entreprise ou profil.

                </p>

            </div>

         

            <div class="row">

                <div class="col s12 m10 offset-m1 l8 offset-l2">

                    <div class="card register-card">

                        <!-- HEADER -->

                        <div class="card-header">

                            <h4>

                                Informations client

                            </h4>

                        </div>

                      <!-- FORM -->

                     
 <?php if(isset($_SESSION['error'])): ?>

                        <div class="form-message error-message">

                            <i class="material-icons left">error_outline</i>

                            <?php
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                            ?>

                            <span class="close-message"
                            onclick="this.parentElement.style.display='none';">

                                &times;

                            </span>

                        </div>

                        <?php endif; ?>


<div class="card-content">

    <form
action="<?php echo app_url_html("inscription/client/traiter"); ?>"
method="POST"
enctype="multipart/form-data">

        <!-- ROLE -->

        <!-- PHOTO PROFIL -->



        <input
        type="hidden"
        name="role_id"
        value="2">

        <!-- NOM + PRENOM -->

        <div class="row">

            <!-- FIRST NAME -->

            <div class="input-field col s12 m6">

                <input
                type="text"
                name="first_name"
                id="first_name"
                required>

                <label for="first_name">

                    Prénom

                </label>

            </div>

            <!-- LAST NAME -->

            <div class="input-field col s12 m6">

                <input
                type="text"
                name="last_name"
                id="last_name"
                required>

                <label for="last_name">

                    Nom

                </label>

            </div>

        </div>

        <!-- EMAIL + PHONE -->

        <div class="row">

            <!-- EMAIL -->

            <div class="input-field col s12 m6">

                <input
                type="email"
                name="email"
                id="email"
                required>

                <label for="email">

                    Adresse Email

                </label>

            </div>

            <!-- PHONE -->

            <div class="input-field col s12 m6">

                <input
                type="text"
                name="phone"
                id="phone"
                required>

                <label for="phone">

                    Téléphone

                </label>

            </div>

        </div>

        <div class="row">

    <!-- MOT DE PASSE -->

    <div class="input-field col s12 m6">

        <i class="material-icons prefix">
            lock
        </i>

        <input
        type="password"
        name="password"
        id="password"
        required>

        <label for="password">
            Mot de passe
        </label>

        <i class="material-icons password-toggle"
        onclick="togglePassword('password', this)">

            visibility

        </i>

    </div>

    <!-- CONFIRMATION -->

    <div class="input-field col s12 m6">

        <i class="material-icons prefix">
            lock
        </i>

        <input
        type="password"
        name="confirm_password"
        id="confirm_password"
        required>

        <label for="confirm_password">
            Confirmer mot de passe
        </label>

        <i class="material-icons password-toggle"
        onclick="togglePassword('confirm_password', this)">

            visibility

        </i>

    </div>

</div>

        <!-- TYPE CLIENT -->

        <div class="row">

            <div class="input-field col s12">

                <select
                name="client_type"
                id="client_type"
                required>

                    <option value=""
                    disabled
                    selected>

                        Choisir le type de client

                    </option>

                    <option value="individual">

                        Particulier

                    </option>

                    <option value="company">

                        Entreprise / Société

                    </option>

                    <option value="expatriate">

                        Expatrié

                    </option>

                </select>

                <label>

                    Type de client

                </label>

            </div>

        </div>

        <!-- ENTREPRISE -->

        <div class="row"
        id="company-field"
        style="display:none;">

            <div class="input-field col s12">

                <input
                type="text"
                name="company_name"
                id="company_name">

                <label for="company_name">

                    Nom de l’entreprise

                </label>

            </div>

        </div>

        <!-- ADRESSE -->

        <div class="row">

            <div class="input-field col s12">

                <textarea
                name="address"
                id="address"
                class="materialize-textarea"
                required></textarea>

                <label for="address">

                    Adresse complète

                </label>

            </div>

        </div>

        <!-- VILLE + GPS -->

        <div class="row">

            <!-- CITY -->

            <div class="input-field col s12 m6">

                <input
                type="text"
                name="city"
                id="city"
                required>

                <label for="city">

                    Ville

                </label>

            </div>

            <!-- GPS -->

          <div class="row">

    <!-- CHAMP GPS -->

    <div class="input-field col s12 m8">

        <input
        type="text"
        name="gps_location"
        id="gps_location">

        <label for="gps_location">

            Position GPS

        </label>

    </div>

    <!-- BOUTON GPS -->

    <div class="col s12 m4 gps-actions">

        <button
        type="button"
        class="btn blue darken-4 waves-effect waves-light"
        onclick="getLocation()">

            <i class="material-icons left">

                my_location

            </i>

            Ma position

        </button>

    </div>

</div>

        </div>

        <div class="row">

    <!-- PHOTO PROFIL -->

<div class="row">

    <div class="col s12">

        <!-- INFO -->

        <div class="file-info">

            <i class="material-icons left">

                info

            </i>

            Ajouter une photo de profil ou un logo d'entreprise (JPG, PNG - max 5 MB)


        </div>

    </div>

    <!-- FILE FIELD -->

    <div class="file-field input-field col s12">

        <div class="btn blue darken-4">

            <span>

                <i class="material-icons left">

                    photo_camera

                </i>

                Photo

            </span>

            <input
            type="file"
            name="profile_photo"
            accept="image/*">

        </div>

        <div class="file-path-wrapper">

            <input
            class="file-path validate"
            type="text"
            placeholder="Choisir une photo de profil">

        </div>

    </div>

</div>

</div>

        <!-- ACTIONS -->

        <div class="form-actions">

            <!-- CANCEL -->

            <a href="<?php echo app_url_html("inscription"); ?>"
            class="cancel-btn waves-effect">

                <i class="material-icons left">

                    close

                </i>

                Annuler

            </a>

            <!-- SUBMIT -->

            <button
            type="submit"
            class="btn-large btn-register waves-effect waves-light">

                soumettre

                <i class="material-icons right">

                    arrow_forward

                </i>

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

    <!-- MATERIALIZE -->

    <script
    src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js">
    </script>

    <script>

        document.addEventListener('DOMContentLoaded', function(){

            M.AutoInit();

        });

    </script>

    <!-- SCRIPT -->

<script>

    document.addEventListener('DOMContentLoaded', function(){

        const elems =
        document.querySelectorAll('select');

        M.FormSelect.init(elems);

        const clientType =
        document.getElementById('client_type');

        const companyField =
        document.getElementById('company-field');

        clientType.addEventListener('change', function(){

            if(this.value === 'company'){

                companyField.style.display = 'block';

            }else{

                companyField.style.display = 'none';

            }

        });

    });

</script>


<script>

function getLocation(){

    var gpsField = document.getElementById("gps_location");
    var locationButton = document.querySelector(".gps-actions button");

    function finish(){

        if(locationButton){

            locationButton.disabled = false;

        }

    }

    function showError(message){

        gpsField.value = "";
        M.updateTextFields();
        finish();
        alert(message);

    }

    if(locationButton){

        locationButton.disabled = true;

    }

    gpsField.value = "Recherche de votre position...";
    M.updateTextFields();

    if(!navigator.geolocation){

        showError("Erreur inconnue.");
        return;

    }

    navigator.geolocation.getCurrentPosition(

        function(position){

            if(!position || !position.coords){

                showError("Erreur inconnue.");
                return;

            }

            var latitude = position.coords.latitude;
            var longitude = position.coords.longitude;

            if(
                typeof latitude !== "number" ||
                typeof longitude !== "number" ||
                !isFinite(latitude) ||
                !isFinite(longitude) ||
                latitude < -90 ||
                latitude > 90 ||
                longitude < -180 ||
                longitude > 180
            ){

                showError("Erreur inconnue.");
                return;

            }

            gpsField.value =
                latitude.toFixed(6) + "," +
                longitude.toFixed(6);

            M.updateTextFields();
            finish();

        },

        function(error){

            var message = "Erreur inconnue.";

            if(error && error.code === 1){

                message = "Permission refusée.";

            }else if(error && error.code === 2){

                message = "Position indisponible.";

            }else if(error && error.code === 3){

                message = "Délai dépassé.";

            }

            showError(message);

        },

        {
            enableHighAccuracy: true,
            timeout: 20000,
            maximumAge: 0
        }
    );

}

</script>

<script>

function togglePassword(inputId, icon){

    var input = document.getElementById(inputId);

    if(input.type === "password"){

        input.type = "text";
        icon.textContent = "visibility_off";

    }else{

        input.type = "password";
        icon.textContent = "visibility";

    }

}

</script>

</body>

</html>
